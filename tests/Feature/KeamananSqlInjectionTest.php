<?php

use App\Livewire\Admin\DokterManager;
use App\Livewire\Admin\PoliManager;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

/** Kumpulan payload injeksi yang umum dipakai: boolean, union, stacked query, komentar, dan sleep. */
function payloadSql(): array
{
    return [
        "' OR '1'='1",
        "' OR 1=1 --",
        "1' OR '1'='1' /*",
        "'; DROP TABLE users; --",
        "' UNION SELECT id,email,password,role,1,1,1,1 FROM users --",
        '1; DELETE FROM antrians',
        "x' AND SLEEP(5) --",
        "\\' OR 1=1 #",
        '%',
        '_',
        '")) OR (("1"="1',
    ];
}

function tabelUtuhDanTanpaKebocoran(): void
{
    foreach (['users', 'polis', 'dokters', 'pasiens', 'antrians', 'log_aktivitas'] as $tabel) {
        expect(Schema::hasTable($tabel))->toBeTrue("tabel {$tabel} hilang");
    }
}

it('semua filter dan pencarian di halaman staf kebal terhadap payload SQL injection', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Rahasia']);
    User::factory()->create(['role' => 'petugas', 'name' => 'Budi Petugas', 'email' => 'budi@rs.test']);
    $this->actingAs($admin);

    foreach (payloadSql() as $p) {
        foreach ([
            route('antrian.riwayat', ['q' => $p]),
            route('antrian.riwayat', ['status' => $p]),
            route('antrian.riwayat', ['poli_id' => $p]),
            route('antrian.riwayat', ['dari' => $p, 'sampai' => $p]),
            route('antrian.riwayat.csv', ['q' => $p]),
            route('antrian.analitik', ['hari' => $p, 'target' => $p, 'sampai' => $p]),
            route('admin.log-aktivitas', ['aksi' => $p]),
            route('admin.akun.index', ['cari' => $p, 'peran' => $p]),
            route('antrian.tiket', ['antrian' => $p]),
            route('antrian.display', ['poli' => $p]),
            route('antrian.kelola', ['poli' => $p]),
        ] as $url) {
            $status = $this->get($url)->getStatusCode();

            // Boleh ditolak (302 validasi, 404, 422), tidak boleh error server
            expect($status)->toBeLessThan(500, "{$url} menghasilkan {$status}");
        }
    }

    tabelUtuhDanTanpaKebocoran();
    expect(User::count())->toBe(2);
});

it('pencarian akun dengan payload tidak membocorkan akun lain', function () {
    $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Rahasia']);
    User::factory()->create(['role' => 'petugas', 'name' => 'Budi Petugas', 'email' => 'budi@rs.test']);

    $this->actingAs($admin)
        ->get(route('admin.akun.index', ['cari' => "' OR '1'='1"]))
        ->assertOk()
        ->assertDontSee('Budi Petugas')
        ->assertSee('Akun tidak ditemukan');
});

it('pencarian Livewire (poli dan dokter) memperlakukan payload sebagai teks biasa', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    Poli::create(['kode_poli' => 'Z', 'nama_poli' => 'Poli Uji Aman', 'aktif' => true]);

    foreach (payloadSql() as $p) {
        Livewire::test(PoliManager::class)->set('cari', $p)->assertOk()->assertDontSee('Poli Uji Aman');
        Livewire::test(DokterManager::class)->set('cari', $p)->set('poliId', $p)->assertOk();
    }

    tabelUtuhDanTanpaKebocoran();
});

it('pendaftaran antrian pasien menolak payload di setiap kolom tanpa merusak data', function () {
    $poli = Poli::create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum', 'aktif' => true]);

    foreach (payloadSql() as $p) {
        $status = $this->post(route('antrian.store'), [
            'poli_id' => $p, 'nik' => $p, 'nama' => $p, 'no_hp' => $p, 'tanggal_lahir' => $p, 'jenis_kelamin' => $p,
        ])->status();

        expect($status)->toBeLessThan(500);
    }

    // poli valid tetapi kolom lain berisi payload: ditolak validasi atau disimpan sebagai teks utuh
    $this->post(route('antrian.store'), [
        'poli_id' => $poli->id, 'nik' => '3201010101010001', 'nama' => "Robert'); DROP TABLE antrians;--",
    ]);

    tabelUtuhDanTanpaKebocoran();
});

it('aksi panggil, ubah akun, dan kelola menolak id berisi payload', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::create(['kode_poli' => 'B', 'nama_poli' => 'Poli Gigi', 'aktif' => true]);

    foreach (payloadSql() as $p) {
        expect($this->actingAs($admin)->post(route('antrian.panggil', $poli), ['dokter_id' => $p])->status())->toBeLessThan(500);
        expect($this->actingAs($admin)->patch(route('admin.akun.update', $admin), ['aksi' => $p, 'password' => $p])->status())->toBeLessThan(500);
        expect($this->actingAs($admin)->post(route('admin.akun.store'), ['name' => $p, 'email' => $p, 'role' => $p, 'password' => $p])->status())->toBeLessThan(500);
    }

    tabelUtuhDanTanpaKebocoran();
    expect(User::count())->toBe(1);
});

it('login dan lupa password kebal terhadap payload', function () {
    $akun = User::factory()->create(['email' => 'aman@rs.test']);

    foreach (payloadSql() as $p) {
        expect($this->post('/login', ['email' => $p, 'password' => $p])->status())->toBeLessThan(500);
        expect($this->post('/forgot-password', ['email' => $p])->status())->toBeLessThan(500);
    }

    $this->assertGuest();
    expect(User::find($akun->id))->not->toBeNull();
});
