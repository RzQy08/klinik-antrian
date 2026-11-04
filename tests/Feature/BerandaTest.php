<?php

use App\Models\Poli;
use App\Models\User;

it('beranda publik tidak menampilkan menu staf atau urutan uji', function () {
    Poli::factory()->create(['nama_poli' => 'Poli Umum', 'aktif' => true]);

    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('Ambil nomor antrian')
        ->assertSee('Cara kerjanya')
        ->assertDontSee('Semua halaman')
        ->assertDontSee('Urutan uji')
        ->assertDontSee('Menu Anda');
});

it('struktur html beranda seimbang (tidak ada tag section yang tidak tertutup)', function () {
    $html = $this->get(route('beranda'))->getContent();

    expect(substr_count($html, '<section'))->toBe(substr_count($html, '</section>'));
});

it('menu staf hanya tampil setelah login dan sesuai peran', function () {
    Poli::factory()->create(['aktif' => true]);

    $petugas = User::factory()->create(['role' => 'petugas']);
    $this->actingAs($petugas)->get(route('beranda'))
        ->assertOk()
        ->assertSee('Menu Anda')
        ->assertSee('Analitik')
        ->assertDontSee('Kelola poli');

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get(route('beranda'))
        ->assertSee('Kelola poli')
        ->assertSee('Analitik');
});

it('endpoint antrian hari ini mengembalikan nomor, jumlah, dan estimasi per poli', function () {
    $poli = Poli::factory()->create(['nama_poli' => 'Poli Umum', 'kode_poli' => 'A', 'aktif' => true]);
    $dokter = \App\Models\Dokter::factory()->create(['poli_id' => $poli->id, 'nama' => 'dr. Andika Pratama']);
    $pasien = \App\Models\Pasien::factory()->create();

    $buat = fn (string $nomor, string $status, int $menit) => \App\Models\Antrian::create([
        'pasien_id' => $pasien->id, 'poli_id' => $poli->id, 'dokter_id' => $dokter->id,
        'nomor_antrian' => $nomor, 'status' => $status, 'tanggal' => now()->toDateString(),
        'waktu_daftar' => now()->subMinutes($menit), 'waktu_dipanggil' => $status === 'dipanggil' ? now() : null,
    ]);

    $buat('A001', 'selesai', 30);
    $buat('A002', 'dipanggil', 20);
    $buat('A003', 'menunggu', 10);
    $buat('A004', 'menunggu', 5);

    $this->getJson(route('antrian.hari-ini'))
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertJsonPath('ringkasan.menunggu', 2)
        ->assertJsonPath('ringkasan.selesai', 1)
        ->assertJsonPath('poli.0.nomor', 'A002')
        ->assertJsonPath('poli.0.dokter', 'dr. Andika Pratama')
        ->assertJsonPath('poli.0.total', 4)
        ->assertJsonPath('poli.0.berikutnya', ['A003', 'A004'])
        ->assertJsonPath('poli.0.persen', 25);
});

it('endpoint antrian hari ini tidak membocorkan data pasien', function () {
    $poli = Poli::factory()->create(['aktif' => true]);
    $pasien = \App\Models\Pasien::factory()->create(['nama' => 'Rahasia Sekali', 'nik' => '3201999999999999']);
    \App\Models\Antrian::create([
        'pasien_id' => $pasien->id, 'poli_id' => $poli->id, 'nomor_antrian' => 'A001', 'status' => 'dipanggil',
        'tanggal' => now()->toDateString(), 'waktu_daftar' => now(), 'waktu_dipanggil' => now(),
    ]);

    $isi = $this->getJson(route('antrian.hari-ini'))->assertOk()->getContent();

    expect($isi)->not->toContain('Rahasia Sekali')->not->toContain('3201999999999999');
});

it('beranda menyertakan data awal panel antrian dan tetap rapi tanpa poli', function () {
    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('data-hero-antrian', false)
        ->assertSee('Belum ada dokter dengan jadwal praktik hari ini.');
});
