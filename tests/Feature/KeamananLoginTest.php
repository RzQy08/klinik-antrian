<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('x'));

function akunStaf(string $email = 'petugas@rs.test'): User
{
    return User::factory()->create(['email' => $email, 'role' => 'petugas', 'password' => Hash::make('KataSandi#2026Kuat')]);
}

it('menolak password salah dengan pesan umum yang sama untuk email tak terdaftar', function () {
    akunStaf();

    $salah = $this->from('/login')->post('/login', ['email' => 'petugas@rs.test', 'password' => 'salah']);
    $tak = $this->from('/login')->post('/login', ['email' => 'tidak.ada@rs.test', 'password' => 'salah']);

    $salah->assertSessionHasErrors('email');
    expect(session('errors')->get('email'))->toBe(['Email atau password tidak sesuai.']);
    $tak->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('mengunci setelah lima kali gagal, bahkan dengan password yang benar', function () {
    akunStaf();

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => 'petugas@rs.test', 'password' => 'salah'.$i]);
    }

    $this->post('/login', ['email' => 'petugas@rs.test', 'password' => 'KataSandi#2026Kuat'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toContain('Terlalu banyak percobaan');
    $this->assertGuest();
});

it('tidak tembus dengan payload SQL injection', function () {
    akunStaf();

    foreach (["' OR '1'='1", "petugas@rs.test' --", "x@y.z'; DROP TABLE users; --"] as $payload) {
        $this->post('/login', ['email' => $payload, 'password' => "' OR '1'='1"])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    expect(User::count())->toBe(1);
});

it('menolak password yang terlalu panjang sebelum diproses', function () {
    $this->post('/login', ['email' => 'a@b.test', 'password' => str_repeat('a', 300)])->assertSessionHasErrors('password');
});

it('tidak memberi cookie ingat saya', function () {
    akunStaf();

    $r = $this->post('/login', ['email' => 'petugas@rs.test', 'password' => 'KataSandi#2026Kuat', 'remember' => 'on']);

    $r->assertRedirect();
    $this->assertAuthenticated();
    expect(collect($r->headers->getCookies())->contains(fn ($c) => str_starts_with($c->getName(), 'remember_web')))->toBeFalse();
});

it('lupa password menjawab sama untuk email terdaftar maupun tidak', function () {
    akunStaf();

    $a = $this->post('/forgot-password', ['email' => 'petugas@rs.test']);
    $b = $this->post('/forgot-password', ['email' => 'tidak.ada@rs.test']);

    expect(session('status'))->toBe('Jika email terdaftar, tautan untuk mengatur ulang password sudah dikirim.');
    $a->assertSessionHasNoErrors();
    $b->assertSessionHasNoErrors();
});

it('admin bisa membuat petugas, menonaktifkan, dan akun nonaktif tidak bisa masuk', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.akun.store'), [
        'name' => 'Sari Petugas', 'email' => 'Sari@RS.test', 'role' => 'petugas',
        'password' => 'Rahasia#Kuat2026', 'password_confirmation' => 'Rahasia#Kuat2026',
    ])->assertSessionHas('success');

    $sari = User::where('email', 'sari@rs.test')->firstOrFail();
    expect($sari->role)->toBe('petugas');

    $this->actingAs($admin)->patch(route('admin.akun.update', $sari), ['aksi' => 'nonaktifkan'])->assertSessionHas('success');
    expect($sari->fresh()->nonaktif_at)->not->toBeNull();

    auth()->logout();
    $this->post('/login', ['email' => 'sari@rs.test', 'password' => 'Rahasia#Kuat2026'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('tidak bisa menonaktifkan diri sendiri atau admin terakhir, dan petugas dilarang membuka halaman akun', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->patch(route('admin.akun.update', $admin), ['aksi' => 'nonaktifkan'])->assertSessionHas('error');
    expect($admin->fresh()->nonaktif_at)->toBeNull();

    $petugas = User::factory()->create(['role' => 'petugas']);
    $this->actingAs($petugas)->get(route('admin.akun.index'))->assertForbidden();
});

it('akun yang dinonaktifkan langsung dikeluarkan dari sesi yang sedang berjalan', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $this->actingAs($petugas)->get(route('antrian.index'))->assertOk();

    $petugas->forceFill(['nonaktif_at' => now()])->save();

    $this->actingAs($petugas->fresh())->get(route('antrian.index'))->assertRedirect(route('login'));
});

it('menolak password lemah saat membuat akun', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->post(route('admin.akun.store'), [
        'name' => 'X', 'email' => 'x@rs.test', 'role' => 'petugas', 'password' => 'abc12345', 'password_confirmation' => 'abc12345',
    ])->assertSessionHasErrors('password');
});
