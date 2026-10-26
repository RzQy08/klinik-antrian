<?php

use App\Services\QueueTheoryService;

it('menghitung rho (utilisasi) dengan benar untuk M/M/1', function () {
    $service = new QueueTheoryService(lambda: 8, mu: 10, c: 1);

    expect($service->rho())->toBe(0.8);
});

it('menghitung Wq M/M/1 sesuai rumus baku lambda/(mu*(mu-lambda))', function () {
    $service = new QueueTheoryService(lambda: 8, mu: 10, c: 1);

    // rumus baku M/M/1: Wq = λ / (μ(μ-λ)) = 8 / (10*2) = 0.4 jam = 24 menit
    expect($service->wq())->toEqualWithDelta(0.4, 0.0001);
    expect($service->wqMenit())->toEqualWithDelta(24, 0.01);
});

it('menghitung Lq M/M/1 sesuai rumus baku lambda^2/(mu*(mu-lambda))', function () {
    $service = new QueueTheoryService(lambda: 8, mu: 10, c: 1);

    // Lq = 8^2 / (10*2) = 3.2
    expect($service->lq())->toEqualWithDelta(3.2, 0.0001);
});

it('menghitung metrik M/M/c (multi-server) tanpa error', function () {
    $service = new QueueTheoryService(lambda: 15, mu: 10, c: 2);

    expect($service->rho())->toBe(0.75);
    expect($service->stabil())->toBeTrue();
    expect($service->wqMenit())->toBeGreaterThan(0);
});

it('mendeteksi sistem tidak stabil ketika rho >= 1', function () {
    $service = new QueueTheoryService(lambda: 25, mu: 10, c: 2);

    expect($service->stabil())->toBeFalse();
});

it('melempar exception saat menghitung Wq pada sistem tidak stabil', function () {
    $service = new QueueTheoryService(lambda: 25, mu: 10, c: 2);

    expect(fn () => $service->wq())->toThrow(InvalidArgumentException::class);
});

it('menolak lambda atau mu bernilai nol atau negatif', function () {
    expect(fn () => new QueueTheoryService(lambda: 0, mu: 10, c: 1))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => new QueueTheoryService(lambda: 10, mu: -5, c: 1))
        ->toThrow(InvalidArgumentException::class);
});

it('ringkasan() mengembalikan semua metrik yang dibutuhkan dashboard', function () {
    $service = new QueueTheoryService(lambda: 8, mu: 10, c: 1);

    $ringkasan = $service->ringkasan();

    expect($ringkasan)->toHaveKeys([
        'lambda_per_jam',
        'mu_per_jam',
        'jumlah_server',
        'rho',
        'stabil',
        'probabilitas_menunggu',
        'wq_menit',
        'w_menit',
        'lq_pasien',
        'l_pasien',
    ]);
});
