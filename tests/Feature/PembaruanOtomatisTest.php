<?php

use App\Livewire\PemberitahuAntrianBaru;
use App\Livewire\TiketAntrian;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Livewire\Livewire;

function buatAntrianUji(string $status = 'menunggu', ?Poli $poli = null, string $nomor = 'A001'): Antrian
{
    $poli ??= Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    $pasien = Pasien::factory()->create();

    return Antrian::create([
        'pasien_id' => $pasien->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $nomor,
        'status' => $status,
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ]);
}

it('tiket memantau statusnya selama antrian masih berjalan', function () {
    $antrian = buatAntrianUji('menunggu');

    Livewire::test(TiketAntrian::class, ['antrianId' => $antrian->id])
        ->assertSee('Perkiraan tunggu')
        ->assertSeeHtml('wire:poll');

    $antrian->update(['status' => 'dipanggil']);

    Livewire::test(TiketAntrian::class, ['antrianId' => $antrian->id])
        ->assertSee('Giliran Anda!')
        ->assertDontSee('Perkiraan tunggu');
});

it('tiket berhenti memantau setelah pelayanan selesai', function () {
    $antrian = buatAntrianUji('selesai');

    Livewire::test(TiketAntrian::class, ['antrianId' => $antrian->id])
        ->assertSee('Pelayanan selesai')
        ->assertDontSeeHtml('wire:poll');
});

it('id antrian pada komponen tiket tidak bisa diubah dari browser', function () {
    $antrian = buatAntrianUji('menunggu');

    Livewire::test(TiketAntrian::class, ['antrianId' => $antrian->id])
        ->set('antrianId', 999)
        ->assertStatus(500);
})->throws(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

it('halaman kelola memberi tahu petugas saat ada pasien baru mendaftar', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    buatAntrianUji('menunggu', $poli, 'A001');

    $komponen = Livewire::test(PemberitahuAntrianBaru::class, ['poliId' => $poli->id])
        ->assertDontSee('pasien baru mendaftar');

    buatAntrianUji('menunggu', $poli, 'A002');

    $komponen->call('$refresh')
        ->assertSee('Ada 1 pasien baru mendaftar')
        ->assertSee('Sekarang 2 pasien menunggu');
});
