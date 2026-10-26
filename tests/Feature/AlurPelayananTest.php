<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper: bikin satu antrian dengan status 'menunggu' buat dasar testing.
 */
function buatAntrianMenunggu(Poli $poli): Antrian
{
    $pasien = Pasien::factory()->create();

    return Antrian::create([
        'pasien_id' => $pasien->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $poli->kode_poli . '001',
        'status' => 'menunggu',
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ]);
}

it('petugas bisa memanggil antrian berikutnya secara FIFO (paling lama menunggu duluan)', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    // antrian pertama daftar duluan (5 menit lalu), harus dipanggil duluan
    $antrianLamaTerdaftar = buatAntrianMenunggu($poli);
    $antrianLamaTerdaftar->update(['waktu_daftar' => now()->subMinutes(5)]);

    $antrianBaruDaftar = buatAntrianMenunggu($poli);
    $antrianBaruDaftar->update(['nomor_antrian' => $poli->kode_poli . '002', 'waktu_daftar' => now()]);

    $this->actingAs($petugas)->post(route('antrian.panggil', $poli), [
        'dokter_id' => $dokter->id,
    ]);

    $antrianLamaTerdaftar->refresh();
    $antrianBaruDaftar->refresh();

    expect($antrianLamaTerdaftar->status)->toBe('dipanggil');
    expect($antrianBaruDaftar->status)->toBe('menunggu'); // belum dipanggil, masih nunggu giliran
});

it('petugas dapat pesan error kalau tidak ada antrian yang menunggu', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    $response = $this->actingAs($petugas)->post(route('antrian.panggil', $poli), [
        'dokter_id' => $dokter->id,
    ]);

    $response->assertSessionHas('error');
});

it('dokter bisa mulai melayani pasien yang sudah dipanggil untuknya', function () {
    $userDokter = User::factory()->create(['role' => 'dokter']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $userDokter->id]);

    $antrian = buatAntrianMenunggu($poli);
    $antrian->update(['status' => 'dipanggil', 'dokter_id' => $dokter->id, 'waktu_dipanggil' => now()]);

    $this->actingAs($userDokter)->post(route('antrian.mulai-layani', $antrian));

    $antrian->refresh();
    expect($antrian->status)->toBe('dilayani');
    expect($antrian->waktu_mulai_dilayani)->not->toBeNull();
});

it('dokter bisa menyelesaikan pelayanan dan waktu_selesai otomatis terisi', function () {
    $userDokter = User::factory()->create(['role' => 'dokter']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $userDokter->id]);

    $antrian = buatAntrianMenunggu($poli);
    $antrian->update([
        'status' => 'dilayani',
        'dokter_id' => $dokter->id,
        'waktu_dipanggil' => now()->subMinutes(10),
        'waktu_mulai_dilayani' => now()->subMinutes(8),
    ]);

    $this->actingAs($userDokter)->post(route('antrian.selesai', $antrian));

    $antrian->refresh();
    expect($antrian->status)->toBe('selesai');
    expect($antrian->waktu_selesai)->not->toBeNull();
    expect($antrian->lama_layanan_detik)->toBeGreaterThan(0);
});

it('petugas bisa menandai pasien tidak hadir', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);

    $antrian = buatAntrianMenunggu($poli);
    $antrian->update(['status' => 'dipanggil', 'dokter_id' => $dokter->id]);

    $this->actingAs($petugas)->post(route('antrian.tidak-hadir', $antrian));

    $antrian->refresh();
    expect($antrian->status)->toBe('tidak_hadir');
});

it('petugas tidak bisa memanggil pasien dengan dokter dari poli lain', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokterPoliLain = Dokter::factory()->create(['poli_id' => Poli::factory()->create()->id]);

    $antrian = buatAntrianMenunggu($poli);

    $response = $this->actingAs($petugas)->post(route('antrian.panggil', $poli), [
        'dokter_id' => $dokterPoliLain->id,
    ]);

    $response->assertSessionHasErrors('dokter_id');
    expect($antrian->refresh()->status)->toBe('menunggu');
});

it('petugas tidak bisa memanggil pasien dengan dokter yang tidak aktif', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokterNonaktif = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => false]);

    $antrian = buatAntrianMenunggu($poli);

    $response = $this->actingAs($petugas)->post(route('antrian.panggil', $poli), [
        'dokter_id' => $dokterNonaktif->id,
    ]);

    $response->assertSessionHasErrors('dokter_id');
    expect($antrian->refresh()->status)->toBe('menunggu');
});

it('dokter tidak bisa mengubah pasien milik dokter lain', function () {
    $poli = Poli::factory()->create();
    $userA = User::factory()->create(['role' => 'dokter']);
    Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $userA->id]);
    $dokterB = Dokter::factory()->create(['poli_id' => $poli->id]);

    $antrian = buatAntrianMenunggu($poli);
    $antrian->update(['status' => 'dipanggil', 'dokter_id' => $dokterB->id, 'waktu_dipanggil' => now()]);

    $this->actingAs($userA)->post(route('antrian.mulai-layani', $antrian))->assertForbidden();

    expect($antrian->refresh()->status)->toBe('dipanggil');
});

it('selesai tidak bisa dipakai pada pasien yang belum dilayani', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $antrian = buatAntrianMenunggu($poli);

    $response = $this->actingAs($petugas)->post(route('antrian.selesai', $antrian));

    $response->assertSessionHas('error');
    expect($antrian->refresh()->status)->toBe('menunggu');
});

it('pasien yang sudah selesai tidak bisa dimulai lagi', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $antrian = buatAntrianMenunggu($poli);
    $antrian->update(['status' => 'selesai', 'waktu_selesai' => now()]);

    $response = $this->actingAs($petugas)->post(route('antrian.mulai-layani', $antrian));

    $response->assertSessionHas('error');
    expect($antrian->refresh()->status)->toBe('selesai');
});