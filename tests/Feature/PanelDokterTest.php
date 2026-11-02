<?php

use App\Livewire\PanelDokter;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Livewire\Livewire;

function dokterLogin(): array
{
    $user = User::factory()->create(['role' => 'dokter']);
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => $user->id]);

    return [$user, $poli, $dokter];
}

function antrianDokter(Poli $poli, ?Dokter $dokter, string $status, string $nomor = 'A001'): Antrian
{
    return Antrian::create([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id, 'poli_id' => $poli->id, 'dokter_id' => $dokter?->id,
        'nomor_antrian' => $nomor, 'status' => $status, 'waktu_daftar' => now()->subMinutes(20),
        'waktu_dipanggil' => $status === 'menunggu' ? null : now()->subMinutes(5),
        'waktu_mulai_dilayani' => $status === 'dilayani' ? now()->subMinutes(2) : null,
        'tanggal' => now()->toDateString(),
    ]);
}

it('halaman dokter menampilkan pasien yang dipanggil untuknya', function () {
    [$user, $poli, $dokter] = dokterLogin();
    $a = antrianDokter($poli, $dokter, 'dipanggil', 'A007');

    $this->actingAs($user)->get(route('antrian.dokter'))->assertOk()->assertSee('Selamat')->assertSee($dokter->nama);

    Livewire::actingAs($user)->test(PanelDokter::class)->assertSee('A007')->assertSee($a->pasien->nama)->assertSee('Mulai layani');
});

it('dokter bisa memulai lalu menyelesaikan pelayanan', function () {
    [$user, $poli, $dokter] = dokterLogin();
    $a = antrianDokter($poli, $dokter, 'dipanggil');

    Livewire::actingAs($user)->test(PanelDokter::class)->call('mulai', $a->id)->assertSee('Selesaikan pelayanan');
    expect($a->refresh()->status)->toBe('dilayani');
    expect($a->waktu_mulai_dilayani)->not->toBeNull();

    Livewire::actingAs($user)->test(PanelDokter::class)->call('selesai', $a->id);
    expect($a->refresh()->status)->toBe('selesai');
    expect($a->waktu_selesai)->not->toBeNull();
});

it('dokter tidak bisa memulai pasien kedua selagi masih melayani', function () {
    [$user, $poli, $dokter] = dokterLogin();
    antrianDokter($poli, $dokter, 'dilayani', 'A001');
    $kedua = antrianDokter($poli, $dokter, 'dipanggil', 'A002');

    Livewire::actingAs($user)->test(PanelDokter::class)->call('mulai', $kedua->id)->assertSee('Selesaikan dulu');

    expect($kedua->refresh()->status)->toBe('dipanggil');
});

it('dokter bisa menandai pasien tidak hadir', function () {
    [$user, $poli, $dokter] = dokterLogin();
    $a = antrianDokter($poli, $dokter, 'dipanggil');

    Livewire::actingAs($user)->test(PanelDokter::class)->call('tidakHadir', $a->id);

    expect($a->refresh()->status)->toBe('tidak_hadir');
});

it('dokter tidak bisa mengubah pasien milik dokter lain', function () {
    [$user, $poli] = dokterLogin();
    $lain = Dokter::factory()->create(['poli_id' => $poli->id]);
    $a = antrianDokter($poli, $lain, 'dipanggil');

    Livewire::actingAs($user)->test(PanelDokter::class)->call('mulai', $a->id)->assertSee('bukan pasien Anda');

    expect($a->refresh()->status)->toBe('dipanggil');
});

it('selain dokter tidak bisa memakai panel dokter', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)->test(PanelDokter::class)->assertForbidden();
});

it('panel memberi tahu browser saat ada panggilan baru', function () {
    [$user, $poli, $dokter] = dokterLogin();

    $uji = Livewire::actingAs($user)->test(PanelDokter::class)->assertSet('terakhirDipanggil', 0);
    antrianDokter($poli, $dokter, 'dipanggil');

    $uji->call('segarkan')->assertDispatched('ada-panggilan')->assertSet('terakhirDipanggil', 1);
});
