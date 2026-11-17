<?php

use App\Models\Pengaturan;
use App\Models\Poli;
use App\Services\JamOperasional;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('migrasi menyalakan jam operasional dan menutup pendaftaran di luar jam', function () {
    Pengaturan::query()->delete();
    \Illuminate\Support\Facades\DB::table('jadwal_rumah_sakit')->delete();
    JamOperasional::lupakan();

    (require database_path('migrations/2026_11_17_000001_aktifkan_jam_operasional.php'))->up();

    expect(JamOperasional::aktif())->toBeTrue();

    // Senin 12:00: lewat jam pendaftaran (11:00), pendaftaran ditolak
    $this->travelTo(now()->next('Monday')->setTime(12, 0));
    JamOperasional::lupakan();
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();

    $poli = Poli::factory()->create();
    $this->post(route('antrian.store'), ['poli_id' => $poli->id, 'nik' => '3201010101010001', 'nama' => 'Uji'])
        ->assertSessionHas('error');
    $this->assertDatabaseCount('antrians', 0);

    // Senin 09:00: dalam jam pendaftaran
    $this->travelTo(now()->setTime(9, 0));
    JamOperasional::lupakan();
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();
});

it('migrasi tidak menimpa jadwal yang sudah diatur admin', function () {
    \Illuminate\Support\Facades\DB::table('jadwal_rumah_sakit')->where('hari', 'senin')->update(['pendaftaran_tutup' => '09:00']);

    (require database_path('migrations/2026_11_17_000001_aktifkan_jam_operasional.php'))->up();

    expect(\Illuminate\Support\Facades\DB::table('jadwal_rumah_sakit')->where('hari', 'senin')->value('pendaftaran_tutup'))->toBe('09:00');
});
