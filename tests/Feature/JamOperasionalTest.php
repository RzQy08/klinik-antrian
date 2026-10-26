<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\JamOperasional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();

    config([
        'whatsapp.driver' => 'log',
        'whatsapp.redirect_ke' => null,
        'operasional.aktif' => true,
        'operasional.pendaftaran_buka' => '07:00',
        'operasional.pendaftaran_tutup' => '11:00',
        'operasional.layanan_selesai' => '14:00',
        'operasional.limpahan_status' => ['menunggu'],
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

/** Buat satu antrian dengan nilai bawaan yang masuk akal. */
function buatAntrian(Poli $poli, array $atribut = []): Antrian
{
    $pasien = $atribut['pasien'] ?? Pasien::factory()->create(['no_hp' => null]);
    unset($atribut['pasien']);

    return Antrian::create(array_merge([
        'pasien_id' => $pasien->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $poli->kode_poli . '001',
        'status' => 'menunggu',
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ], $atribut));
}

// ---------- jam pendaftaran dan layanan ----------

it('pendaftaran dibuka pukul 07.00 sampai sebelum 11.00', function () {
    Carbon::setTestNow('2026-10-20 06:59:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();

    Carbon::setTestNow('2026-10-20 07:00:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();

    Carbon::setTestNow('2026-10-20 10:59:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();

    Carbon::setTestNow('2026-10-20 11:00:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();
});

it('layanan berjalan sampai sebelum jam layanan selesai', function () {
    Carbon::setTestNow('2026-10-20 13:59:00');
    expect(JamOperasional::layananBerjalan())->toBeTrue();

    Carbon::setTestNow('2026-10-20 14:00:00');
    expect(JamOperasional::layananBerjalan())->toBeFalse();
});

it('tidak ada pembatasan jam kalau OPERASIONAL_AKTIF dimatikan', function () {
    config(['operasional.aktif' => false]);
    Carbon::setTestNow('2026-10-20 23:30:00');

    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();
    expect(JamOperasional::layananBerjalan())->toBeTrue();
});

it('pasien tidak bisa mengambil antrian setelah pendaftaran ditutup', function () {
    Carbon::setTestNow('2026-10-20 11:30:00');
    $poli = Poli::factory()->create();

    $response = $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '3201010101990001',
        'nama' => 'Budi Hartono',
    ]);

    $response->assertSessionHas('error');
    expect(Antrian::count())->toBe(0);
});

it('pasien bisa mengambil antrian saat pendaftaran dibuka', function () {
    Carbon::setTestNow('2026-10-20 09:00:00');
    $poli = Poli::factory()->create();

    $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '3201010101990001',
        'nama' => 'Budi Hartono',
    ]);

    expect(Antrian::count())->toBe(1);
});

// ---------- panggil ulang ----------

it('petugas bisa memanggil ulang pasien yang tidak hadir', function () {
    Carbon::setTestNow('2026-10-20 12:00:00');

    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    $antrian = buatAntrian($poli, [
        'status' => 'tidak_hadir',
        'dokter_id' => $dokter->id,
        'jumlah_panggil' => 1,
        'waktu_dipanggil' => now()->subMinutes(10),
    ]);

    $this->actingAs($petugas)->post(route('antrian.panggil-ulang', $antrian));

    $antrian->refresh();

    expect($antrian->status)->toBe('dipanggil');
    expect($antrian->jumlah_panggil)->toBe(2);
});

it('panggil ulang ditolak setelah jam layanan selesai', function () {
    Carbon::setTestNow('2026-10-20 15:00:00');

    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    $antrian = buatAntrian($poli, ['status' => 'tidak_hadir', 'dokter_id' => $dokter->id, 'jumlah_panggil' => 1]);

    $response = $this->actingAs($petugas)->post(route('antrian.panggil-ulang', $antrian));

    $response->assertSessionHas('error');
    expect($antrian->refresh()->status)->toBe('tidak_hadir');
});

it('panggil berikutnya ditolak setelah jam layanan selesai', function () {
    Carbon::setTestNow('2026-10-20 15:00:00');

    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    $antrian = buatAntrian($poli);

    $response = $this->actingAs($petugas)->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id]);

    $response->assertSessionHas('error');
    expect($antrian->refresh()->status)->toBe('menunggu');
});

// ---------- limpahan ke hari berikutnya ----------

it('memindahkan antrian yang belum terpanggil ke hari berikutnya dengan nomor berurutan', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);

    buatAntrian($poli, ['nomor_antrian' => 'A010', 'status' => 'selesai', 'tanggal' => '2026-10-19', 'waktu_daftar' => '2026-10-19 08:00:00']);
    buatAntrian($poli, ['nomor_antrian' => 'A012', 'status' => 'menunggu', 'tanggal' => '2026-10-19', 'waktu_daftar' => '2026-10-19 09:30:00']);
    buatAntrian($poli, ['nomor_antrian' => 'A011', 'status' => 'menunggu', 'tanggal' => '2026-10-19', 'waktu_daftar' => '2026-10-19 09:00:00']);

    Artisan::call('antrian:limpahkan', ['dari' => '2026-10-19', '--ke' => '2026-10-20', '--tanpa-wa' => true]);

    $baru = Antrian::whereDate('tanggal', '2026-10-20')->orderBy('waktu_daftar')->get();

    // urutan lama dipertahankan: A011 daftar lebih dulu daripada A012
    expect($baru->pluck('nomor_antrian')->all())->toBe(['A001', 'A002']);
    expect($baru->pluck('nomor_lama')->all())->toBe(['A011', 'A012']);
    expect($baru->first()->tanggal_asal->toDateString())->toBe('2026-10-19');
    expect($baru->first()->status)->toBe('menunggu');

    // antrian lama ditandai sudah dipindah, yang selesai tidak ikut
    expect(Antrian::where('nomor_antrian', 'A011')->first()->dialihkan_at)->not->toBeNull();
    expect(Antrian::where('nomor_antrian', 'A010')->first()->dialihkan_at)->toBeNull();
});

it('menjalankan limpahan dua kali tidak membuat antrian ganda', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    buatAntrian($poli, ['nomor_antrian' => 'A005', 'tanggal' => '2026-10-19', 'waktu_daftar' => '2026-10-19 09:00:00']);

    Artisan::call('antrian:limpahkan', ['dari' => '2026-10-19', '--ke' => '2026-10-20', '--tanpa-wa' => true]);
    Artisan::call('antrian:limpahkan', ['dari' => '2026-10-19', '--ke' => '2026-10-20', '--tanpa-wa' => true]);

    expect(Antrian::whereDate('tanggal', '2026-10-20')->count())->toBe(1);
});

it('pendaftar baru melanjutkan nomor setelah antrian limpahan', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    buatAntrian($poli, ['nomor_antrian' => 'A005', 'tanggal' => '2026-10-19', 'waktu_daftar' => '2026-10-19 09:00:00']);

    Artisan::call('antrian:limpahkan', ['dari' => '2026-10-19', '--ke' => '2026-10-20', '--tanpa-wa' => true]);

    Carbon::setTestNow('2026-10-20 08:00:00');

    $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '3201010101990009',
        'nama' => 'Pendaftar Baru',
    ]);

    $nomor = Antrian::whereDate('tanggal', '2026-10-20')->orderBy('waktu_daftar')->pluck('nomor_antrian')->all();

    expect($nomor)->toBe(['A001', 'A002']);
});

it('antrian limpahan dikeluarkan dari data analitik', function () {
    $poli = Poli::factory()->create();

    buatAntrian($poli, ['status' => 'selesai']);
    buatAntrian($poli, ['status' => 'selesai', 'tanggal_asal' => '2026-10-19', 'nomor_lama' => 'A005']);

    expect(Antrian::untukAnalitik()->count())->toBe(1);
});
