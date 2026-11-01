<?php

use App\Livewire\Admin\PoliManager;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Livewire\Livewire;

function antrianPoliUji(Poli $poli, string $status = 'menunggu'): Antrian
{
    return Antrian::create([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $poli->kode_poli.'001',
        'status' => $status,
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ]);
}

it('halaman kelola poli menampilkan komponen interaktif', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Poli::factory()->create(['nama_poli' => 'Poli Umum']);

    $this->actingAs($admin)->get(route('admin.poli.index'))->assertOk()->assertSee('Poli Umum');
});

it('mencari dan menyaring poli', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    Poli::factory()->create(['nama_poli' => 'Poli Gigi', 'kode_poli' => 'B', 'aktif' => true]);
    Poli::factory()->create(['nama_poli' => 'Poli Anak', 'kode_poli' => 'C', 'aktif' => false]);

    Livewire::actingAs($admin)->test(PoliManager::class)
        ->set('cari', 'gigi')->assertSee('Poli Gigi')->assertDontSee('Poli Anak')
        ->set('cari', '')->set('status', 'nonaktif')->assertSee('Poli Anak')->assertDontSee('Poli Gigi');
});

it('admin bisa mengaktifkan dan menonaktifkan poli', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create(['aktif' => true]);

    Livewire::actingAs($admin)->test(PoliManager::class)->call('toggleAktif', $poli->id);
    expect($poli->refresh()->aktif)->toBeFalse();

    Livewire::actingAs($admin)->test(PoliManager::class)->call('toggleAktif', $poli->id);
    expect($poli->refresh()->aktif)->toBeTrue();
});

it('poli yang masih punya pasien menunggu tidak bisa dinonaktifkan', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create(['aktif' => true]);
    antrianPoliUji($poli, 'menunggu');

    Livewire::actingAs($admin)->test(PoliManager::class)->call('toggleAktif', $poli->id)->assertSee('belum bisa dinonaktifkan');

    expect($poli->refresh()->aktif)->toBeTrue();
});

it('poli tanpa riwayat bisa dihapus lewat konfirmasi', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create();

    Livewire::actingAs($admin)->test(PoliManager::class)
        ->call('konfirmasiHapus', $poli->id)->assertSet('hapusId', $poli->id)
        ->call('hapus');

    expect(Poli::find($poli->id))->toBeNull();
});

it('poli yang punya riwayat antrian tidak bisa dihapus', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create();
    Dokter::factory()->create(['poli_id' => $poli->id]);
    antrianPoliUji($poli, 'selesai');

    Livewire::actingAs($admin)->test(PoliManager::class)
        ->call('konfirmasiHapus', $poli->id)->call('hapus')->assertSee('tidak bisa dihapus');

    expect(Poli::find($poli->id))->not->toBeNull();
});

it('non-admin tidak bisa mengubah poli lewat aksi Livewire', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create(['aktif' => true]);

    Livewire::actingAs($petugas)->test(PoliManager::class)->call('toggleAktif', $poli->id)->assertForbidden();
    Livewire::actingAs($petugas)->test(PoliManager::class)->call('konfirmasiHapus', $poli->id)->assertForbidden();

    expect($poli->refresh()->aktif)->toBeTrue();
});
