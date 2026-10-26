<?php

use App\Models\Poli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('user yang belum login diarahkan ke halaman login saat akses dashboard petugas', function () {
    $response = $this->get(route('antrian.index'));

    $response->assertRedirect(route('login'));
});

it('petugas bisa akses dashboard antrian', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);

    $response = $this->actingAs($petugas)->get(route('antrian.index'));

    $response->assertOk();
});

it('dokter TIDAK bisa akses dashboard antrian milik petugas', function () {
    $dokter = User::factory()->create(['role' => 'dokter']);

    $response = $this->actingAs($dokter)->get(route('antrian.index'));

    $response->assertForbidden();
});

it('petugas TIDAK bisa akses dashboard dokter', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);

    $response = $this->actingAs($petugas)->get(route('antrian.dokter'));

    $response->assertForbidden();
});

it('petugas TIDAK bisa akses halaman admin', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);

    $response = $this->actingAs($petugas)->get(route('admin.dashboard'));

    $response->assertForbidden();
});

it('admin bisa akses halaman kelola poli', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Poli::factory()->count(2)->create();

    $response = $this->actingAs($admin)->get(route('admin.poli.index'));

    $response->assertOk();
});
