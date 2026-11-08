<?php

use App\Livewire\AntrianBoard;
use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function buatPoliPapanUji(string $kode): Poli
{
    return Poli::factory()->create(['kode_poli' => $kode, 'nama_poli' => 'Poli '.$kode]);
}

function buatAntrianPapanUji(Poli $poli, string $nomor, string $status = 'menunggu', int $jumlahPanggil = 0, ?Carbon $dipanggil = null): Antrian
{
    return Antrian::create([
        'pasien_id' => Pasien::factory()->create()->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $nomor,
        'status' => $status,
        'waktu_daftar' => now(),
        'waktu_dipanggil' => $dipanggil,
        'jumlah_panggil' => $jumlahPanggil,
        'tanggal' => now()->toDateString(),
    ]);
}

it('menyorot poli dari panggilan terbaru saat papan pertama dibuka', function () {
    Carbon::setTestNow('2026-11-08 09:00:00');

    $poliA = buatPoliPapanUji('A');
    $poliB = buatPoliPapanUji('B');

    buatAntrianPapanUji($poliA, 'A001', 'dipanggil', 1, now()->subMinutes(5));
    buatAntrianPapanUji($poliB, 'B001', 'dipanggil', 1, now()->subMinute());

    Livewire::test(AntrianBoard::class)
        ->assertSet('aktifId', $poliB->id)
        ->assertDontSeeHtml('ly-progress')
        ->assertSee('B001');
});

it('memutar sorotan ke poli berikutnya setelah delapan detik', function () {
    Carbon::setTestNow('2026-11-08 09:00:00');

    $poliA = buatPoliPapanUji('A');
    $poliB = buatPoliPapanUji('B');

    $komponen = Livewire::test(AntrianBoard::class)
        ->assertSet('aktifId', $poliA->id);

    Carbon::setTestNow('2026-11-08 09:00:08');

    $komponen->call('segarkan')
        ->assertSet('aktifId', $poliB->id);
});

it('melompat ke poli yang baru memanggil dan menahan sorotan lebih lama', function () {
    Carbon::setTestNow('2026-11-08 09:00:00');

    $poliA = buatPoliPapanUji('A');
    $poliB = buatPoliPapanUji('B');

    $komponen = Livewire::test(AntrianBoard::class)
        ->assertSet('aktifId', $poliA->id);

    Carbon::setTestNow('2026-11-08 09:00:02');
    buatAntrianPapanUji($poliB, 'B001', 'dipanggil', 1, now());

    $komponen->call('segarkan')
        ->assertSet('aktifId', $poliB->id)
        ->assertSet('panggilanBaru', true)
        ->assertSee('Panggilan baru');
});

it('bisa dijeda lalu dilanjutkan tanpa kehilangan sorotan', function () {
    Carbon::setTestNow('2026-11-08 09:00:00');

    $poliA = buatPoliPapanUji('A');
    $poliB = buatPoliPapanUji('B');

    $komponen = Livewire::test(AntrianBoard::class)
        ->assertSet('aktifId', $poliA->id);

    $komponen->call('toggleJeda')->assertSet('jeda', true);

    Carbon::setTestNow('2026-11-08 09:00:30');
    $komponen->call('segarkan')->assertSet('aktifId', $poliA->id);

    $komponen->call('toggleJeda')->assertSet('jeda', false);

    Carbon::setTestNow('2026-11-08 09:00:39');
    $komponen->call('segarkan')->assertSet('aktifId', $poliB->id);
});
