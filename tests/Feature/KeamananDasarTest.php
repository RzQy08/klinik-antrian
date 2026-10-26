<?php

it('halaman publik mengirim header keamanan dasar', function () {
    $response = $this->get(route('beranda'));

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('masuk cepat demo tidak aktif di luar lingkungan lokal', function () {
    app()->detectEnvironment(fn () => 'production');

    // Token CSRF dikirim manual karena di luar environment "testing" pengecekan CSRF aktif
    $this->withSession(['_token' => 'token-uji'])
        ->post(route('demo.masuk', 'admin'), ['_token' => 'token-uji'])
        ->assertNotFound();

    $this->assertGuest();
});

it('masuk cepat demo berfungsi di lingkungan lokal', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->withSession(['_token' => 'token-uji'])
        ->post(route('demo.masuk', 'admin'), ['_token' => 'token-uji'])
        ->assertRedirect();

    $this->assertAuthenticated();
});