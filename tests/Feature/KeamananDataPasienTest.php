<?php

use App\Models\AuditLog;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\PemeriksaKeamanan;
use App\Support\AksesLokal;
use Illuminate\Support\Facades\Hash;

function masukCepat($uji, array $header = [], array $server = [])
{
    app()->detectEnvironment(fn () => 'local');

    return $uji->withServerVariables($server)->withHeaders($header)
        ->withSession(['_token' => 'token-uji'])
        ->post(route('demo.masuk', 'admin'), ['_token' => 'token-uji']);
}

it('masuk cepat DEV ditolak bila lewat tunnel atau proxy walau APP_ENV=local', function () {
    masukCepat($this, ['X-Forwarded-For' => '203.0.113.9', 'X-Forwarded-Proto' => 'https'])->assertNotFound();
    $this->assertGuest();
});

it('masuk cepat DEV ditolak dari alamat jaringan atau host publik', function () {
    masukCepat($this, [], ['REMOTE_ADDR' => '203.0.113.9'])->assertNotFound();
    $this->assertGuest();

    app()->detectEnvironment(fn () => 'local');
    $this->withServerVariables(['HTTP_HOST' => 'abc.ngrok-free.app'])
        ->withSession(['_token' => 'token-uji'])
        ->post('http://abc.ngrok-free.app/demo/masuk/admin', ['_token' => 'token-uji'])
        ->assertNotFound();
    $this->assertGuest();
});

it('AksesLokal mengenali host Laragon dan menolak host publik', function () {
    expect(AksesLokal::hostLokal('klinik-antrian.test'))->toBeTrue()
        ->and(AksesLokal::hostLokal('localhost'))->toBeTrue()
        ->and(AksesLokal::hostLokal('abc.ngrok-free.app'))->toBeFalse()
        ->and(AksesLokal::hostLokal('192.168.1.5'))->toBeFalse();
});

it('panel DEV tidak dirender untuk permintaan lewat tunnel', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get(route('beranda'))->assertOk()->assertSee('data-dev', false);

    $this->withHeaders(['X-Forwarded-For' => '203.0.113.9'])->get(route('beranda'))->assertOk()->assertDontSee('data-dev', false);
});

it('halaman staf dan tiket tidak boleh disimpan cache browser dan tidak diindeks', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
        ->assertHeader('Cache-Control');

    expect($this->actingAs($admin)->get(route('admin.dashboard'))->headers->get('Cache-Control'))->toContain('no-store');
});

it('nomor HP pasien tidak bisa ditimpa oleh pengunjung tanpa login, tetapi bisa oleh petugas', function () {
    $pasien = Pasien::factory()->create(['no_hp' => '081111111111']);

    $pasien->update(['no_hp' => '089999999999']);
    expect($pasien->refresh()->no_hp)->toBe('081111111111');

    $this->actingAs(User::factory()->create(['role' => 'petugas']));
    $pasien->update(['no_hp' => '082222222222']);
    expect($pasien->refresh()->no_hp)->toBe('082222222222');
});

it('nomor HP pasien yang masih kosong boleh diisi pengunjung', function () {
    $pasien = Pasien::factory()->create(['no_hp' => null]);
    $pasien->update(['no_hp' => '083333333333']);

    expect($pasien->refresh()->no_hp)->toBe('083333333333');
});

it('tiket publik hanya menampilkan nama samar', function () {
    $poli = Poli::factory()->create();
    $antrian = Antrian::create([
        'poli_id' => $poli->id,
        'pasien_id' => Pasien::factory()->create(['nama' => 'Budi Santoso'])->id,
        'nomor_antrian' => 'A001',
        'status' => 'menunggu',
        'waktu_daftar' => now(),
        'tanggal' => today()->toDateString(),
    ]);

    $this->get(route('antrian.tiket', $antrian->kode_tiket))
        ->assertOk()
        ->assertSee('B*** S******')
        ->assertDontSee('Budi Santoso');
});

it('password akun dokter yang dibuat admin harus kuat', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $poli = Poli::factory()->create();

    $this->actingAs($admin)->post(route('admin.dokter.store'), [
        'poli_id' => $poli->id, 'nama' => 'dr. Lemah', 'aktif' => 1,
        'buat_akun' => 1, 'email' => 'lemah@klinik.test', 'password' => 'abc12345',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'lemah@klinik.test')->exists())->toBeFalse();
});

it('login, kegagalan login, dan unduhan CSV tercatat di jejak audit tanpa membocorkan NIK', function () {
    $petugas = User::factory()->create(['role' => 'petugas', 'password' => 'Petugas-Kuat#2026']);

    $this->post('/login', ['email' => $petugas->email, 'password' => 'salah'])->assertSessionHasErrors('email');
    expect(AuditLog::where('aksi', 'login_gagal')->exists())->toBeTrue();

    $this->post('/login', ['email' => $petugas->email, 'password' => 'Petugas-Kuat#2026']);
    expect(AuditLog::where('aksi', 'login')->where('user_id', $petugas->id)->exists())->toBeTrue();

    $this->get(route('antrian.riwayat.csv', ['q' => '3201234567890001']))->assertOk();
    $log = AuditLog::where('aksi', 'ekspor_csv')->firstOrFail();

    expect($log->nama_pengguna)->toBe($petugas->name)
        ->and($log->keterangan)->toContain('3201********0001')
        ->and($log->keterangan)->not->toContain('3201234567890001');
});

it('perubahan akun dan password tercatat', function () {
    $user = User::factory()->create(['role' => 'petugas']);
    expect(AuditLog::where('aksi', 'akun_dibuat')->exists())->toBeTrue();

    $user->update(['password' => 'Sandi-Baru-Kuat#1']);
    $user->update(['role' => 'admin']);

    expect(AuditLog::where('aksi', 'password_diubah')->exists())->toBeTrue()
        ->and(AuditLog::where('aksi', 'peran_diubah')->exists())->toBeTrue();
});

it('jejak audit hanya bisa dibuka admin', function () {
    $this->actingAs(User::factory()->create(['role' => 'petugas']))->get(route('admin.audit'))->assertForbidden();
    $this->flushSession();
    $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.audit'))->assertOk()->assertSee('Jejak audit');
});

it('pemeriksa keamanan menandai akun yang masih memakai password bawaan seeder', function () {
    User::factory()->create(['email' => 'admin@klinik.test', 'role' => 'admin', 'password' => Hash::make('Admin@Klinik26')]);
    User::factory()->create(['email' => 'petugas@klinik.test', 'role' => 'petugas', 'password' => Hash::make('Sudah-Diganti#987')]);

    $temuan = collect((new PemeriksaKeamanan)->periksa())->pluck('judul')->implode(' | ');

    expect($temuan)->toContain('1 akun masih memakai password bawaan');
});

it('perintah keamanan:periksa gagal bila ada berkas sensitif di folder public', function () {
    $berkas = public_path('phpinfo.php');
    file_put_contents($berkas, '<?php // uji');

    try {
        $this->artisan('keamanan:periksa')->assertFailed();
    } finally {
        @unlink($berkas);
    }
});
