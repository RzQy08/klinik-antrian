<?php

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scope padaTanggal hanya mengambil antrian pada tanggal itu', function () {
    $poli = Poli::factory()->create();

    foreach (['2026-10-19', '2026-10-20', '2026-10-21'] as $i => $tanggal) {
        Antrian::create([
            'pasien_id' => Pasien::factory()->create()->id,
            'poli_id' => $poli->id,
            'nomor_antrian' => 'A00' . ($i + 1),
            'status' => 'menunggu',
            'waktu_daftar' => $tanggal . ' 09:00:00',
            'tanggal' => $tanggal,
        ]);
    }

    expect(Antrian::padaTanggal('2026-10-20')->pluck('nomor_antrian')->all())->toBe(['A002']);
    expect(Antrian::padaTanggal('2026-10-22')->count())->toBe(0);
});

it('dashboard mengarahkan pengguna sesuai perannya', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $petugas = User::factory()->create(['role' => 'petugas']);
    $dokter = User::factory()->create(['role' => 'dokter']);

    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    $this->actingAs($petugas)->get('/dashboard')->assertRedirect(route('antrian.index'));
    $this->actingAs($dokter)->get('/dashboard')->assertRedirect(route('antrian.dokter'));
});

it('halaman daftar poli memuat jumlah dokter tanpa error', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Poli::factory()->count(3)->create();

    $this->actingAs($admin)->get(route('admin.poli.index'))->assertOk();
});
