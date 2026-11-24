<?php

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\QueueTheoryService;
use Carbon\Carbon;
use Database\Seeders\DataPasienRealistis;
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

    expect(Pasien::query()->where('no_rm', 'like', 'RM-UJI-%')->count())->toBe(count(DataPasienRealistis::untukUji()));

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

it('pasien contoh punya nama, NIK, dan nomor HP yang unik dan berstruktur benar', function () {
    $semua = DataPasienRealistis::semua();

    expect(collect($semua)->pluck('nik')->unique())->toHaveCount(count($semua));
    expect(collect($semua)->pluck('nama')->unique())->toHaveCount(count($semua));
    expect(collect($semua)->pluck('no_hp')->unique())->toHaveCount(count($semua));

    foreach ($semua as $p) {
        expect($p['nik'])->toMatch('/^\d{16}$/');

        $tgl = (int) substr($p['nik'], 6, 2);
        $lahir = Carbon::parse($p['tanggal_lahir']);

        // Tanggal pada NIK perempuan ditambah 40
        expect($tgl)->toBe($lahir->day + ($p['jenis_kelamin'] === 'P' ? 40 : 0));
        expect(substr($p['nik'], 8, 4))->toBe($lahir->format('m').$lahir->format('y'));
        expect($p['nama'])->toMatch('/^[A-Z][a-z]+( [A-Z][a-z]+)*$/');
    }
});

it('seeder master melengkapi poli dan dokter spesialis dengan jadwal sendiri', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    expect(Poli::query()->pluck('nama_poli')->all())->toContain('Poli THT', 'Poli Penyakit Dalam', 'Poli Mata', 'Poli Kulit dan Kelamin');

    $mata = \App\Models\Dokter::query()->where('nama', 'like', '%Sp.M')->firstOrFail();
    expect($mata->jadwalPraktiks()->pluck('hari')->sort()->values()->all())->toBe(['kamis', 'sabtu', 'selasa']);
});

it('database menolak jadwal ganda dan akun yang dipakai dua dokter', function () {
    $dokter = \App\Models\Dokter::factory()->create();

    \App\Models\JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => 'senin', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 10]);

    expect(fn () => \App\Models\JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => 'senin', 'jam_mulai' => '13:00', 'jam_selesai' => '15:00', 'kuota' => 10]))
        ->toThrow(\Illuminate\Database\QueryException::class);

    $user = User::factory()->create(['role' => 'dokter']);
    $dokter->update(['user_id' => $user->id]);
    $lain = \App\Models\Dokter::factory()->create();

    expect(fn () => $lain->update(['user_id' => $user->id]))->toThrow(\Illuminate\Database\QueryException::class);
});
