<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Pasien;
use App\Models\Poli;
use App\Services\JamOperasional;
use App\Services\PemanggilOtomatis;
use App\Services\RingkasanAntrian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    config(['whatsapp.driver' => 'log', 'whatsapp.redirect_ke' => null, 'operasional.aktif' => true]);
    Carbon::setTestNow('2026-10-20 09:00:00'); // Selasa
});

afterEach(function () {
    Carbon::setTestNow();
});

function siapkanDokterPraktik(): array
{
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => true]);
    JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => 'selasa', 'jam_mulai' => '08:00:00', 'jam_selesai' => '16:00:00', 'kuota' => 20]);

    return [$poli, $dokter];
}

function antrianUji(Poli $poli, string $status, ?Dokter $dokter = null): Antrian
{
    static $no = 0;

    return Antrian::create([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $poli->kode_poli.str_pad((string) ++$no, 3, '0', STR_PAD_LEFT),
        'status' => $status,
        'waktu_daftar' => now(),
        'waktu_dipanggil' => $status === 'menunggu' ? null : now(),
        'dokter_id' => $dokter?->id,
        'tanggal' => now()->toDateString(),
    ]);
}

function statusDokter(Dokter $dokter): string
{
    return collect(RingkasanAntrian::susun()['dokter'])->firstWhere('id', $dokter->id)['praktik'];
}

it('dokter yang jam praktiknya berjalan dan tidak punya pasien berstatus aktif', function () {
    [$poli, $dokter] = siapkanDokterPraktik();
    antrianUji($poli, 'menunggu'); // sudah mendaftar tapi belum di ruangan

    expect(statusDokter($dokter))->toBe('aktif');
});

it('dokter berstatus sedang praktik hanya saat pasien benar-benar di ruangan', function () {
    [$poli, $dokter] = siapkanDokterPraktik();
    $antrian = antrianUji($poli, 'dipanggil', $dokter);

    expect(statusDokter($dokter))->toBe('memanggil');

    $antrian->update(['status' => 'dilayani', 'waktu_mulai_dilayani' => now()]);
    expect(statusDokter($dokter))->toBe('melayani');

    $antrian->update(['status' => 'selesai', 'waktu_selesai' => now()]);
    expect(statusDokter($dokter))->toBe('aktif');
});

it('pendaftaran tutup pukul 14.00 dan layanan berakhir pukul 16.00 menurut jadwal bawaan', function () {
    Carbon::setTestNow('2026-10-20 13:59:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();

    Carbon::setTestNow('2026-10-20 14:00:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();
    expect(JamOperasional::layananBerjalan())->toBeTrue();

    Carbon::setTestNow('2026-10-20 16:00:00');
    expect(JamOperasional::layananBerjalan())->toBeFalse();
});

it('panggil otomatis tidak memanggil pasien kedua selama dokter masih punya pasien', function () {
    [$poli, $dokter] = siapkanDokterPraktik();
    antrianUji($poli, 'dilayani', $dokter);
    $menunggu = antrianUji($poli, 'menunggu');

    PemanggilOtomatis::jalankan();

    expect($menunggu->refresh()->status)->toBe('menunggu');
});

it('panggil otomatis berulang tetap hanya memanggil satu pasien per dokter', function () {
    [$poli, $dokter] = siapkanDokterPraktik();
    antrianUji($poli, 'menunggu');
    antrianUji($poli, 'menunggu');
    antrianUji($poli, 'menunggu');

    PemanggilOtomatis::jalankan();
    PemanggilOtomatis::jalankan();
    PemanggilOtomatis::jalankan();

    expect(Antrian::where('dokter_id', $dokter->id)->where('status', 'dipanggil')->count())->toBe(1);
    expect(Antrian::where('status', 'menunggu')->count())->toBe(2);
});

it('petugas tidak bisa memanggil pasien lagi untuk dokter yang masih punya pasien', function () {
    [$poli, $dokter] = siapkanDokterPraktik();
    $petugas = \App\Models\User::factory()->create(['role' => 'petugas']);
    antrianUji($poli, 'dipanggil', $dokter);
    $menunggu = antrianUji($poli, 'menunggu');

    $this->actingAs($petugas)->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id])
        ->assertSessionHas('error');

    expect($menunggu->refresh()->status)->toBe('menunggu');
});

// ---------- mode uji (panel DEV) ----------

it('mode uji tidak berlaku di luar APP_ENV local', function () {
    Cache::forever(JamOperasional::KUNCI_MODE_UJI, true);
    Carbon::setTestNow('2026-10-20 23:00:00');

    expect(JamOperasional::modeUji())->toBeFalse();
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();
});

it('mode uji membuat pendaftaran dan pemanggilan bisa kapan saja saat APP_ENV local', function () {
    app()->detectEnvironment(fn () => 'local');
    Cache::forever(JamOperasional::KUNCI_MODE_UJI, true);
    Carbon::setTestNow('2026-10-25 23:00:00'); // Minggu malam

    expect(JamOperasional::modeUji())->toBeTrue();
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();
    expect(JamOperasional::layananBerjalan())->toBeTrue();
});

it('saklar mode uji dan pasien uji menjawab 404 untuk permintaan bukan lokal', function () {
    $this->post(route('demo.mode-uji'))->assertNotFound();
    $this->post(route('demo.pasien-uji'))->assertNotFound();
});

it('di komputer lokal saklar mode uji bisa dinyalakan dan pasien uji ditambahkan', function () {
    app()->detectEnvironment(fn () => 'local');
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    $poli = Poli::factory()->create(['aktif' => true]);

    $this->post(route('demo.mode-uji'))->assertRedirect();
    expect(JamOperasional::modeUji())->toBeTrue();

    $this->post(route('demo.pasien-uji'), ['poli_id' => $poli->id])->assertRedirect();
    expect(Antrian::where('poli_id', $poli->id)->where('status', 'menunggu')->count())->toBe(1);
    expect(Pasien::where('nik', 'like', '9999%')->count())->toBe(1);

    $this->post(route('demo.mode-uji'))->assertRedirect();
    expect(JamOperasional::modeUji())->toBeFalse();
});
