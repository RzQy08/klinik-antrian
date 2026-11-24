<?php

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

it('riwayat menyensor NIK dan nomor HP serta menolak status tidak dikenal', function () {
    $poli = Poli::factory()->create();
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nik' => '3201010101990004', 'no_hp' => '081234567890']);

    $petugas = User::factory()->create(['role' => 'petugas']);

    $this->actingAs($petugas)->get(route('antrian.riwayat', ['dari' => '2026-10-01', 'sampai' => '2026-10-31']))
        ->assertSee('HP •••••••••890', false)
        ->assertDontSee('3201010101990004')
        ->assertDontSee('081234567890');

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

it('unduh CSV memuat data terfilter tanpa NIK dan dengan HP disensor', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nama' => 'Budi Santoso', 'nik' => '3201010101990004', 'no_hp' => '081234567890']);
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
        ->toContain('•••••••••890')
        ->toContain('12') // lama layanan 12 menit
        ->not->toContain('3201010101990004')
        ->not->toContain('081234567890')
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

it('NIK disensor penuh dan nomor HP hanya menyisakan tiga digit akhir', function () {
    expect(Pasien::sensorNik('3201010101990004'))->toBe('•••• •••• •••• ••••');
    expect(Pasien::sensorNik(null))->toBe('-');
    expect(Pasien::sensorHp('081234567890'))->toBe('•••••••••890');
    expect(Pasien::sensorHp('+62 812-3456-7890'))->toBe('••••••••••890');
    expect(Pasien::sensorHp(null))->toBe('-');
});

it('pencarian riwayat menemukan lewat nama, nomor antrian, NIK, dan nomor HP', function () {
    $poli = Poli::factory()->create(['nama_poli' => 'Poli Mata']);
    riwayatAntrian($poli, '2026-10-10', 'A001', 'selesai', ['nama' => 'Budi Santoso', 'nik' => '3201010101990004', 'no_hp' => '081234567890']);
    riwayatAntrian($poli, '2026-10-10', 'A002', 'selesai', ['nama' => 'Citra Dewi', 'nik' => '3201010101990005', 'no_hp' => '081299990000']);

    $petugas = User::factory()->create(['role' => 'petugas']);
    $dasar = ['dari' => '2026-10-01', 'sampai' => '2026-10-31'];
    $cari = fn (string $q) => $this->actingAs($petugas)->get(route('antrian.riwayat', $dasar + ['q' => $q]));

    $cari('budi')->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');
    $cari('320101010199000')->assertSee('Budi Santoso')->assertSee('Citra Dewi'); // awalan NIK
    $cari('3201010101990005')->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
    $cari('081234567890')->assertSee('Budi Santoso')->assertDontSee('Citra Dewi');
    $cari('567890')->assertSee('Budi Santoso')->assertDontSee('Citra Dewi'); // akhiran HP
    $cari('A002')->assertSee('Citra Dewi')->assertDontSee('Budi Santoso');
});
