<?php

use App\Models\User;

test('halaman pendaftaran akun publik tidak tersedia', function () {
    $this->get('/register')->assertNotFound();
});

test('orang asing tidak bisa membuat akun lewat pendaftaran publik', function () {
    $response = $this->post('/register', [
        'name' => 'Penyusup',
        'email' => 'penyusup@example.com',
        'password' => 'password-kuat-123',
        'password_confirmation' => 'password-kuat-123',
    ]);

    $response->assertNotFound();
    $this->assertGuest();
    expect(User::where('email', 'penyusup@example.com')->exists())->toBeFalse();
});