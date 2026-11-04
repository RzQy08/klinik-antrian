<?php

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\QueueTheoryService;
use Carbon\Carbon;
use Database\Seeders\DataUjiSeeder;
use Database\Seeders\HapusDataUjiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuat akun, pasien, antrian hari ini, dan riwayat yang siap diuji', function () {
    $this->seed(DataUjiSeeder::class);

    $admin = User::query()->where('email', DataUjiSeeder::EMAIL_ADMIN)->first();
    $petugas = User::query()->where('email', DataUjiSeeder::EMAIL_PETUGAS)->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe('admin');
    expect($petugas)->not->toBeNull();
    expect($petugas->role)->toBe('petugas');

    $akunDokter = User::query()
        ->where('role', 'dokter')
        ->where('email', 'like', '%.uji@klinik.test')
        ->get();

    expect($akunDokter)->not->toBeEmpty();
    expect($akunDokter->every(fn (User $user) => $user->dokter()->exists()))->toBeTrue();

    expect(Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->count())->toBe(12);

    $statusHariIni = Antrian::query()
        ->hariIni()
        ->pluck('status')
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($statusHariIni)->toContain('menunggu', 'dipanggil', 'dilayani', 'selesai', 'tidak_hadir', 'batal');

    if (! Carbon::yesterday()->isSunday()) {
        expect(Antrian::query()->hariIni()->whereNotNull('tanggal_asal')->exists())->toBeTrue();
    }

    $riwayat = Antrian::query()
        ->untukAnalitik()
        ->where('tanggal', '>=', Carbon::today()->subDays(14)->toDateString())
        ->where('tanggal', '<', Carbon::today()->toDateString())
        ->count();

    expect($riwayat)->toBeGreaterThan(100);

    $hariMinggu = Antrian::query()
        ->where('tanggal', '<', Carbon::today()->toDateString())
        ->get(['tanggal'])
        ->filter(fn (Antrian $antrian) => Carbon::parse($antrian->tanggal)->isSunday());

    expect($hariMinggu)->toBeEmpty();
});

it('bisa dijalankan dua kali tanpa menggandakan data uji', function () {
    $this->seed(DataUjiSeeder::class);

    $jumlahUser = User::query()->where('email', 'like', '%.uji@klinik.test')->count();
    $jumlahPasien = Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->count();
    $jumlahAntrian = Antrian::query()->count();

    $this->seed(DataUjiSeeder::class);

    expect(User::query()->where('email', 'like', '%.uji@klinik.test')->count())->toBe($jumlahUser);
    expect(Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->count())->toBe($jumlahPasien);
    expect(Antrian::query()->count())->toBe($jumlahAntrian);
});

it('riwayat uji menghasilkan perhitungan queue theory yang stabil', function () {
    $this->seed(DataUjiSeeder::class);

    $polis = Poli::query()
        ->where('aktif', true)
        ->whereHas('dokters', fn ($q) => $q->where('aktif', true))
        ->get();

    expect($polis)->not->toBeEmpty();

    foreach ($polis as $poli) {
        $service = QueueTheoryService::dariData(
            poliId: $poli->id,
            dari: Carbon::today()->subDays(14),
            sampai: Carbon::yesterday(),
        );

        expect($service->stabil())->toBeTrue();
        expect($service->rho())->toBeLessThan(1.0);
    }
});

it('pembersih data uji hanya menghapus data uji', function () {
    $this->seed(DataUjiSeeder::class);

    $poli = Poli::query()->first();
    $jumlahPasienLain = Pasien::query()->where('no_rm', 'not like', 'RM-UJI-%')->count();

    $this->seed(HapusDataUjiSeeder::class);

    expect(Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->count())->toBe(0);
    expect(User::query()->where('email', 'like', '%.uji@klinik.test')->count())->toBe(0);
    expect(Pasien::query()->where('no_rm', 'not like', 'RM-UJI-%')->count())->toBe($jumlahPasienLain);
    expect(Poli::query()->find($poli->id))->not->toBeNull();
});
