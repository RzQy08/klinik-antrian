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
