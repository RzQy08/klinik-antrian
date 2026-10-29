<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\QueueMetric;
use App\Models\User;
use App\Services\QueueTheoryService;
use Carbon\Carbon;

function antrianSelesaiUji(Poli $poli, ?Dokter $dokter, string $tanggal, int $lamaLayananDetik, array $tambahan = []): Antrian
{
    $mulai = Carbon::parse("{$tanggal} 09:00:00");

    return Antrian::create(array_merge([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id,
        'poli_id' => $poli->id,
        'dokter_id' => $dokter?->id,
        'nomor_antrian' => $poli->kode_poli . '001',
        'status' => 'selesai',
        'waktu_daftar' => $mulai->copy()->subMinutes(10),
        'waktu_mulai_dilayani' => $mulai,
        'waktu_selesai' => $mulai->copy()->addSeconds($lamaLayananDetik),
        'tanggal' => $tanggal,
    ], $tambahan));
}

it('dariData menghitung lambda, mu, dan c lewat agregat SQL tanpa limpahan', function () {
    $poli = Poli::factory()->create();
    $dokterA = Dokter::factory()->create(['poli_id' => $poli->id]);
    $dokterB = Dokter::factory()->create(['poli_id' => $poli->id]);

    antrianSelesaiUji($poli, $dokterA, '2026-10-10', 600);
    antrianSelesaiUji($poli, $dokterB, '2026-10-10', 600);
    antrianSelesaiUji($poli, $dokterA, '2026-10-11', 1200);

    // Tidak boleh ikut dihitung: limpahan, belum selesai, dan di luar rentang tanggal
    antrianSelesaiUji($poli, $dokterA, '2026-10-12', 3000, ['tanggal_asal' => '2026-10-11', 'nomor_lama' => 'A009']);
    antrianSelesaiUji($poli, $dokterA, '2026-10-12', 600, ['status' => 'menunggu']);
    antrianSelesaiUji($poli, $dokterA, '2026-09-01', 600);

    $service = QueueTheoryService::dariData($poli->id, Carbon::parse('2026-10-06'), Carbon::parse('2026-10-19'));

    // 3 pasien / (2 hari aktif x 8 jam); rata-rata layanan (600+600+1200)/3 = 800 detik; 2 dokter
    expect($service->lambda)->toBe(round(3 / (2 * 8), 4));
    expect($service->mu)->toBe(round(3600 / 800, 4));
    expect($service->c)->toBe(2);
});

it('dariData melempar InvalidArgumentException kalau belum ada data selesai', function () {
    $poli = Poli::factory()->create();

    expect(fn () => QueueTheoryService::dariData($poli->id, Carbon::parse('2026-10-06'), Carbon::parse('2026-10-19')))
        ->toThrow(InvalidArgumentException::class);
});

it('dariData melapor wajar kalau data selesai belum punya waktu layanan', function () {
    $poli = Poli::factory()->create();

    antrianSelesaiUji($poli, null, '2026-10-10', 600, ['waktu_mulai_dilayani' => null, 'waktu_selesai' => null]);

    expect(fn () => QueueTheoryService::dariData($poli->id, Carbon::parse('2026-10-06'), Carbon::parse('2026-10-19')))
        ->toThrow(InvalidArgumentException::class);
});

it('halaman analitik menghitung per poli dan tidak memasukkan limpahan', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $kemarin = Carbon::yesterday()->toDateString();

    antrianSelesaiUji($poli, $dokter, $kemarin, 600);
    antrianSelesaiUji($poli, $dokter, $kemarin, 600);
    antrianSelesaiUji($poli, $dokter, $kemarin, 600, [
        'tanggal_asal' => Carbon::today()->subDays(2)->toDateString(),
        'nomor_lama' => 'A009',
    ]);

    $response = $this->actingAs($admin)->get(route('antrian.analitik', ['hari' => 7]));

    $response->assertOk();
    $response->assertViewHas('hasil', fn ($hasil) => $hasil->first()['jumlah_sampel'] === 2);
});

it('rentang hari analitik dibatasi maksimal 90 hari', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('antrian.analitik', ['hari' => 100000]))
        ->assertOk()
        ->assertViewHas('jumlahHari', 90);

    $this->actingAs($admin)->get(route('antrian.analitik', ['hari' => 'abc']))
        ->assertOk()
        ->assertViewHas('jumlahHari', 14);
});

it('queue-metrics:generate menyimpan metrik harian dari data agregat tanpa limpahan', function () {
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $tanggal = '2026-10-10';

    antrianSelesaiUji($poli, $dokter, $tanggal, 600);
    antrianSelesaiUji($poli, $dokter, $tanggal, 600);
    antrianSelesaiUji($poli, $dokter, $tanggal, 3000, ['tanggal_asal' => '2026-10-09', 'nomor_lama' => 'A009']);

    $this->artisan('queue-metrics:generate', ['tanggal' => $tanggal])->assertSuccessful();

    $metrik = QueueMetric::where('poli_id', $poli->id)->first();

    // 2 pasien / 8 jam = 0,25 per jam; layanan 600 detik = 6 pasien per jam; menunggu 10 menit
    expect((float) $metrik->lambda)->toBe(0.25);
    expect((float) $metrik->mu)->toBe(6.0);
    expect((float) $metrik->wq_aktual_menit)->toBe(10.0);
});

it('lambda hanya membagi dengan hari yang ada pelayanan, hari libur tidak ikut dihitung', function () {
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    // Senin sampai Sabtu ada pelayanan (2026-10-12 s/d 17), Minggu 2026-10-18 libur
    foreach (['2026-10-12', '2026-10-13', '2026-10-14', '2026-10-15', '2026-10-16', '2026-10-17'] as $tanggal) {
        antrianSelesaiUji($poli, $dokter, $tanggal, 600);
        antrianSelesaiUji($poli, $dokter, $tanggal, 600);
    }

    // Rentang 7 hari memuat satu hari Minggu: 12 pasien / (6 hari aktif x 8 jam), bukan / (7 x 8)
    $service = QueueTheoryService::dariData($poli->id, Carbon::parse('2026-10-12'), Carbon::parse('2026-10-18'));

    expect($service->lambda)->toBe(round(12 / (6 * 8), 4));
});
