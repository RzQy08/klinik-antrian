<?php

use App\Services\QueueTheoryService;

it('simulasi dokter: menambah dokter menurunkan rho dan waktu tunggu', function () {
    $service = new QueueTheoryService(lambda: 15, mu: 10, c: 2);

    $simulasi = $service->simulasiDokter(2, 4);

    expect(array_keys($simulasi))->toBe([2, 3, 4]);
    expect($simulasi[2]['rho'])->toBe(0.75);
    expect($simulasi[3]['rho'])->toBe(0.5);
    expect($simulasi[3]['wq_menit'])->toBeLessThan($simulasi[2]['wq_menit']);
    expect($simulasi[4]['wq_menit'])->toBeLessThan($simulasi[3]['wq_menit']);
});

it('simulasi dokter: skenario tidak stabil tidak punya wq', function () {
    $service = new QueueTheoryService(lambda: 25, mu: 10, c: 2);

    $simulasi = $service->simulasiDokter(1, 3);

    expect($simulasi[1]['stabil'])->toBeFalse();
    expect($simulasi[1]['wq_menit'])->toBeNull();
    expect($simulasi[2]['stabil'])->toBeFalse(); // rho = 1,25
    expect($simulasi[3]['stabil'])->toBeTrue();
    expect($simulasi[3]['wq_menit'])->not->toBeNull();
});

it('rekomendasi dokter: jumlah paling sedikit yang stabil, rho <= 85% dan tunggu sesuai target', function () {
    // lambda 25, mu 10: 3 dokter rho 0,83 stabil tetapi tunggu masih panjang, 4 dokter rho 0,625
    $service = new QueueTheoryService(lambda: 25, mu: 10, c: 2);

    $c = $service->rekomendasiDokter(targetWqMenit: 5);

    expect($c)->not->toBeNull();
    $skenario = $service->denganDokter($c);
    expect($skenario->rho())->toBeLessThanOrEqual(0.85);
    expect($skenario->wqMenit())->toBeLessThanOrEqual(5);

    // dokter lebih sedikit satu orang tidak memenuhi syarat
    if ($c > 1) {
        $kurang = $service->denganDokter($c - 1);
        expect($kurang->stabil() && $kurang->rho() <= 0.85 && $kurang->wqMenit() <= 5)->toBeFalse();
    }
});

it('rekomendasi dokter: null kalau batas dokter belum cukup', function () {
    $service = new QueueTheoryService(lambda: 100, mu: 10, c: 1);

    expect($service->rekomendasiDokter(targetWqMenit: 5, rhoMaks: 0.85, maksDokter: 5))->toBeNull();
});
