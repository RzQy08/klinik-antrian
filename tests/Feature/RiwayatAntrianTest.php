<?php

use App\Http\Controllers\RiwayatController;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Carbon\Carbon;

function riwayatAntrian(Poli $poli, string $tanggal, string $nomor, string $status = 'selesai', array $pasien = []): Antrian
{
    $mulai = Carbon::parse("{$tanggal} 09:00:00");

    return Antrian::create([
        'pasien_id' => Pasien::factory()->create(array_merge(['no_hp' => null], $pasien))->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $nomor,
        'status' => $status,
        'waktu_daftar' => $mulai->copy()->subMinutes(10),
        'waktu_mulai_dilayani' => $status === 'selesai' ? $mulai : null,
        'waktu_selesai' => $status === 'selesai' ? $mulai->copy()->addMinutes(12) : null,
        'tanggal' => $tanggal,
    ]);
}

it('riwayat hanya bisa dibuka admin dan petugas', function () {
    $this->get(route('antrian.riwayat'))->assertRedirect(route('login'));

    $dokter = User::factory()->create(['role' => 'dokter']);
    $this->actingAs($dokter)->get(route('antrian.riwayat'))->assertForbidden();
    $this->actingAs($dokter)->get(route('antrian.riwayat.csv'))->assertForbidden();

    $petugas = User::factory()->create(['role' => 'petugas']);
    $this->actingAs($petugas)->get(route('antrian.riwayat'))->assertOk();
});

it('riwayat menyaring berdasarkan rentang tanggal, poli, status, dan pencarian', function () {
    $umum = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    $gigi = Poli::factory()->create(['kode_poli' => 'B', 'nama_poli' => 'Poli Gigi']);

    riwayatAntrian($umum, '2026-10-10', 'A001', 'selesai', ['nama' => 'Budi Santoso']);
    riwayatAntrian($umum, '2026-10-11', 'A002', 'tidak_hadir', ['nama' => 'Citra Dewi']);
    riwayatAntrian($gigi, '2026-10-11', 'B001', 'selesai', ['nama' => 'Dewi Lestari']);
    riwayatAntrian($umum, '2026-09-01', 'A099', 'selesai', ['nama' => 'Di Luar Rentang']);

    $petugas = User::factory()->create(['role' => 'petugas']);
    $dasar = ['dari' => '2026-10-01', 'sampai' => '2026-10-31'];

    $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar))
        ->assertSee('Budi Santoso')->assertSee('Citra Dewi')->assertSee('Dewi Lestari')
        ->assertDontSee('Di Luar Rentang');

    $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar + ['poli_id' => $gigi->id]))
        ->assertSee('Dewi Lestari')->assertDontSee('Budi Santoso');

    $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar + ['status' => 'tidak_hadir']))
        ->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');

    $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar + ['q' => 'santoso']))
        ->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');

    // karakter LIKE dicari apa adanya, bukan sebagai wildcard
    $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar + ['q' => '%']))
        ->assertDontSee('Budi Santoso');
});

it('riwayat menyamarkan NIK dan menolak status tidak dikenal', function () {
    $poli = Poli::factory()->create();
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nik' => '3201010101990004']);

    $petugas = User::factory()->create(['role' => 'petugas']);

    $this->actingAs($petugas)->get(route('antrian.riwayat', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
        ->assertSee('3201********0004')
        ->assertDontSee('3201010101990004');

    $this->actingAs($petugas)->get(route('antrian.riwayat', ['status' => 'ngawur']))
        ->assertSessionHasErrors('status');
});

it('riwayat membatasi rentang maksimal dan menukar tanggal terbalik', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);

    $this->actingAs($petugas)
        ->get(route('antrian.riwayat', ['dari' => '2020-01-01', 'sampai' => '2026-10-31']))
        ->assertOk()
        ->assertDontSee('1 Jan 2020');

    $this->actingAs($petugas)
        ->get(route('antrian.riwayat', ['dari' => '2026-10-31', 'sampai' => '2026-10-01']))
        ->assertOk();
});

it('unduh CSV memuat data terfilter dengan NIK samar dan pemisah titik koma', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nama' => 'Budi Santoso', 'nik' => '3201010101990004']);
    riwayatAntrian($poli, '2026-09-01', 'A002', 'selesai', ['nama' => 'Di Luar Rentang']);

    $petugas = User::factory()->create(['role' => 'petugas']);

    $respon = $this->actingAs($petugas)
        ->get(route('antrian.riwayat.csv', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
        ->assertOk();

    expect($respon->headers->get('content-disposition'))->toContain('riwayat-antrian-20261001-20261031.csv');

    $isi = $respon->streamedContent();

    expect($isi)->toStartWith("\xEF\xBB\xBF")
        ->toContain('Tanggal;Nomor;Poli;Pasien')
        ->toContain('Budi Santoso')
        ->toContain('3201********0004')
        ->toContain('12') // lama layanan 12 menit
        ->not->toContain('3201010101990004')
        ->not->toContain('Di Luar Rentang');
});

it('CSV menetralkan sel yang diawali karakter rumus', function () {
    $poli = Poli::factory()->create();
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nama' => '=HYPERLINK("http://jahat")']);

    $petugas = User::factory()->create(['role' => 'petugas']);

    $isi = $this->actingAs($petugas)
        ->get(route('antrian.riwayat.csv', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
        ->streamedContent();

    expect($isi)->toContain("'=HYPERLINK")->not->toContain(';=HYPERLINK');
});

it('samarkanNik menyisakan empat digit awal dan akhir', function () {
    expect(RiwayatController::samarkanNik('3201010101990004'))->toBe('3201********0004');
    expect(RiwayatController::samarkanNik(null))->toBeNull();
    expect(RiwayatController::samarkanNik('123'))->toBe('123');
});
