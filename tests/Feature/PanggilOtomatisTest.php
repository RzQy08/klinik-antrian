<?php

use App\Livewire\PanelDokter;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\PemanggilOtomatis;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Dokter yang jadwalnya hari ini sedang berjalan. */
function dokterBertugas(Poli $poli, string $nama = 'dr. Aktif'): Dokter
{
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'nama' => $nama, 'aktif' => true]);
    $hari = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu', 7 => 'minggu'][now()->dayOfWeekIso];
    JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => $hari, 'jam_mulai' => '00:00:00', 'jam_selesai' => '23:59:59', 'kuota' => 30]);

    return $dokter;
}

function tiketOtomatis(Poli $poli, string $nomor, string $status = 'menunggu', array $lain = []): Antrian
{
    return Antrian::create(array_merge([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $nomor,
        'status' => $status,
        'waktu_daftar' => now()->subMinutes(30),
        'tanggal' => now()->toDateString(),
    ], $lain));
}

beforeEach(function () {
    config(['antrian.panggil_otomatis' => true, 'antrian.batas_telat_menit' => 5, 'antrian.batas_tidak_hadir_menit' => 15, 'antrian.ikuti_jadwal' => true]);
});

it('memanggil pasien berikutnya otomatis untuk dokter yang kosong', function () {
    $poli = Poli::factory()->create();
    $dokter = dokterBertugas($poli);
    $a = tiketOtomatis($poli, 'A001');
    $b = tiketOtomatis($poli, 'A002', 'menunggu', ['waktu_daftar' => now()->subMinutes(20)]);

    $hasil = PemanggilOtomatis::jalankan();

    expect($hasil['dipanggil'])->toBe(1);
    expect($a->refresh()->status)->toBe('dipanggil');
    expect($a->dokter_id)->toBe($dokter->id);
    expect($a->jumlah_panggil)->toBe(1);
    expect($b->refresh()->status)->toBe('menunggu'); // dokter hanya menangani satu pasien

    // dijalankan lagi: dokter masih sibuk, tidak ada panggilan baru
    expect(PemanggilOtomatis::jalankan()['dipanggil'])->toBe(0);
});

it('tidak memanggil otomatis bila dokter tidak sedang bertugas, cuti, atau fitur dimatikan', function () {
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => true]); // tanpa jadwal
    $a = tiketOtomatis($poli, 'A001');

    expect(PemanggilOtomatis::jalankan()['dipanggil'])->toBe(0);
    expect($a->refresh()->status)->toBe('menunggu');

    $bertugas = dokterBertugas($poli);
    config(['antrian.panggil_otomatis' => false]);
    expect(PemanggilOtomatis::jalankan()['dipanggil'])->toBe(0);

    config(['antrian.panggil_otomatis' => true]);
    \App\Models\CutiDokter::create(['dokter_id' => $bertugas->id, 'tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->toDateString()]);
    expect(PemanggilOtomatis::jalankan()['dipanggil'])->toBe(0);
});

it('pasien yang dipanggil lalu lewat 5 menit menjadi telat, dan dokter langsung mendapat pasien berikutnya', function () {
    $poli = Poli::factory()->create();
    $dokter = dokterBertugas($poli);
    $lambat = tiketOtomatis($poli, 'A001', 'dipanggil', ['dokter_id' => $dokter->id, 'waktu_dipanggil' => now()->subMinutes(6), 'jumlah_panggil' => 1]);
    $berikut = tiketOtomatis($poli, 'A002');

    $hasil = PemanggilOtomatis::jalankan();

    expect($hasil['telat'])->toBe(1);
    expect($lambat->refresh()->status)->toBe('telat');
    expect($lambat->waktu_telat)->not->toBeNull();
    expect($berikut->refresh()->status)->toBe('dipanggil');
});

it('pasien yang belum 5 menit dipanggil tetap dipanggil', function () {
    $poli = Poli::factory()->create();
    $dokter = dokterBertugas($poli);
    $a = tiketOtomatis($poli, 'A001', 'dipanggil', ['dokter_id' => $dokter->id, 'waktu_dipanggil' => now()->subMinutes(4), 'jumlah_panggil' => 1]);

    PemanggilOtomatis::jalankan();

    expect($a->refresh()->status)->toBe('dipanggil');
});

it('telat tanpa kabar menjadi tidak hadir setelah batas waktu', function () {
    $poli = Poli::factory()->create();
    $baru = tiketOtomatis($poli, 'A001', 'telat', ['waktu_dipanggil' => now()->subMinutes(10), 'waktu_telat' => now()->subMinutes(5)]);
    $lama = tiketOtomatis($poli, 'A002', 'telat', ['waktu_dipanggil' => now()->subMinutes(30), 'waktu_telat' => now()->subMinutes(16)]);

    $hasil = PemanggilOtomatis::jalankan();

    expect($hasil['tidak_hadir'])->toBe(1);
    expect($baru->refresh()->status)->toBe('telat');
    expect($lama->refresh()->status)->toBe('tidak_hadir');
});

it('petugas bisa memanggil ulang pasien telat dan waktu telatnya dihapus', function () {
    $poli = Poli::factory()->create();
    $dokter = dokterBertugas($poli);
    $petugas = User::factory()->create(['role' => 'petugas']);
    $a = tiketOtomatis($poli, 'A001', 'telat', ['dokter_id' => $dokter->id, 'waktu_dipanggil' => now()->subMinutes(8), 'waktu_telat' => now()->subMinutes(3), 'jumlah_panggil' => 1]);

    $this->actingAs($petugas)->post(route('antrian.panggil-ulang', $a))->assertRedirect();

    $a->refresh();
    expect($a->status)->toBe('dipanggil');
    expect($a->jumlah_panggil)->toBe(2);
    expect($a->waktu_telat)->toBeNull();
});

it('pasien telat yang datang bisa langsung dimulai dokter', function () {
    $poli = Poli::factory()->create();
    $user = User::factory()->create(['role' => 'dokter']);
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $user->id]);
    $a = tiketOtomatis($poli, 'A001', 'telat', ['dokter_id' => $dokter->id, 'waktu_dipanggil' => now()->subMinutes(8), 'waktu_telat' => now()->subMinutes(2)]);

    Livewire::actingAs($user)->test(PanelDokter::class)->assertSee('Telat')->call('mulai', $a->id);

    expect($a->refresh()->status)->toBe('dilayani');
});

it('setelah dokter menyelesaikan pasien, nomor berikutnya otomatis dipanggil', function () {
    $poli = Poli::factory()->create();
    $user = User::factory()->create(['role' => 'dokter']);
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $user->id]);
    $hari = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu', 7 => 'minggu'][now()->dayOfWeekIso];
    JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => $hari, 'jam_mulai' => '00:00:00', 'jam_selesai' => '23:59:59', 'kuota' => 30]);

    $sedang = tiketOtomatis($poli, 'A001', 'dilayani', ['dokter_id' => $dokter->id, 'waktu_dipanggil' => now()->subMinutes(10), 'waktu_mulai_dilayani' => now()->subMinutes(5), 'jumlah_panggil' => 1]);
    $berikut = tiketOtomatis($poli, 'A002');

    Livewire::actingAs($user)->test(PanelDokter::class)->call('selesai', $sedang->id);

    expect($sedang->refresh()->status)->toBe('selesai');
    expect($berikut->refresh()->status)->toBe('dipanggil');
    expect($berikut->dokter_id)->toBe($dokter->id);
});

it('pasien tidak hadir boleh mendaftar lagi dengan NIK yang sama dan mendapat nomor baru', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    $pasien = Pasien::factory()->create(['nik' => '3201010101990004', 'no_hp' => null]);
    $lama = Antrian::create(['pasien_id' => $pasien->id, 'poli_id' => $poli->id, 'nomor_antrian' => 'A001', 'status' => 'tidak_hadir', 'waktu_daftar' => now()->subHour(), 'waktu_dipanggil' => now()->subMinutes(40), 'tanggal' => now()->toDateString()]);

    $this->post(route('antrian.store'), ['poli_id' => $poli->id, 'nik' => '3201010101990004', 'nama' => $pasien->nama])->assertRedirect();

    $baru = Antrian::where('pasien_id', $pasien->id)->where('status', 'menunggu')->first();
    expect($baru)->not->toBeNull();
    expect($baru->id)->not->toBe($lama->id);
    expect($baru->nomor_antrian)->toBe('A002');

    // nomor lama tidak lagi bisa dipanggil ulang
    $petugas = User::factory()->create(['role' => 'petugas']);
    $this->actingAs($petugas)->post(route('antrian.panggil-ulang', $lama), ['dokter_id' => Dokter::factory()->create(['poli_id' => $poli->id])->id])
        ->assertSessionHas('error');
    expect($lama->refresh()->status)->toBe('tidak_hadir');
});

it('pasien yang masih telat mendapat tiket yang sama saat mendaftar lagi', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    $pasien = Pasien::factory()->create(['nik' => '3201010101990004', 'no_hp' => null]);
    $telat = Antrian::create(['pasien_id' => $pasien->id, 'poli_id' => $poli->id, 'nomor_antrian' => 'A001', 'status' => 'telat', 'waktu_daftar' => now()->subHour(), 'waktu_dipanggil' => now()->subMinutes(8), 'waktu_telat' => now()->subMinutes(3), 'tanggal' => now()->toDateString()]);

    $this->post(route('antrian.store'), ['poli_id' => $poli->id, 'nik' => '3201010101990004', 'nama' => $pasien->nama])
        ->assertRedirect(route('antrian.tiket', $telat->kode_tiket));

    expect(Antrian::where('pasien_id', $pasien->id)->count())->toBe(1);
});

it('halaman tiket memberi tahu pasien yang telat dan yang tidak hadir', function () {
    $poli = Poli::factory()->create();
    $telat = tiketOtomatis($poli, 'A001', 'telat', ['waktu_dipanggil' => now()->subMinutes(8), 'waktu_telat' => now()->subMinutes(3)]);
    $absen = tiketOtomatis($poli, 'A002', 'tidak_hadir', ['waktu_dipanggil' => now()->subMinutes(40)]);

    $this->get(route('antrian.tiket', $telat->kode_tiket))->assertOk()->assertSee('Anda terlambat');
    $this->get(route('antrian.tiket', $absen->kode_tiket))->assertOk()->assertSee('Daftar lagi');
});

it('halaman kelola menampilkan daftar telat sementara', function () {
    $poli = Poli::factory()->create();
    $petugas = User::factory()->create(['role' => 'petugas']);
    tiketOtomatis($poli, 'A001', 'telat', ['waktu_dipanggil' => now()->subMinutes(8), 'waktu_telat' => now()->subMinutes(3)]);

    $this->actingAs($petugas)->get(route('antrian.kelola', $poli))->assertOk()->assertSee('Telat sementara')->assertSee('A001');
    $this->actingAs($petugas)->getJson(route('antrian.versi', $poli))->assertOk()->assertJsonStructure(['v']);
});

it('perintah antrian:otomatis berjalan', function () {
    $this->artisan('antrian:otomatis')->assertSuccessful();
});
