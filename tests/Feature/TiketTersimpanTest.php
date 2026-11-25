<?php

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-20 09:00:00');
});

afterEach(fn () => Carbon::setTestNow());

function tiketUji(string $status = 'menunggu', ?string $tanggal = null): Antrian
{
    $poli = Poli::factory()->create();

    return Antrian::create([
        'pasien_id' => Pasien::factory()->create()->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => $poli->kode_poli.'001',
        'status' => $status,
        'waktu_daftar' => now(),
        'tanggal' => $tanggal ?? now()->toDateString(),
    ]);
}

it('halaman tiket menampilkan tanggal daftar dan tombol unduh kartu', function () {
    $antrian = tiketUji();

    $this->get(route('antrian.tiket', $antrian->kode_tiket))
        ->assertOk()
        ->assertSee('Selasa, 20 Oktober 2026')
        ->assertSee('id="tiketUnduh"', false)
        ->assertSee('data-kode="'.$antrian->kode_tiket.'"', false);
});

it('status tiket aktif hari ini belum selesai', function () {
    $antrian = tiketUji('dipanggil');

    $this->getJson(route('antrian.status-tiket', $antrian->kode_tiket))
        ->assertOk()
        ->assertJson(['nomor' => $antrian->nomor_antrian, 'status' => 'dipanggil', 'selesai' => false]);
});

it('status tiket dianggap selesai bila antrian selesai, tidak hadir, atau sudah lewat hari', function () {
    foreach (['selesai', 'tidak_hadir'] as $status) {
        $a = tiketUji($status);
        $this->getJson(route('antrian.status-tiket', $a->kode_tiket))->assertJson(['selesai' => true]);
    }

    $kemarin = tiketUji('menunggu', now()->subDay()->toDateString());
    $this->getJson(route('antrian.status-tiket', $kemarin->kode_tiket))->assertJson(['selesai' => true]);
});

it('status tiket dengan kode asal menjawab 404', function () {
    $this->getJson(route('antrian.status-tiket', str_repeat('a', 32)))->assertNotFound();
});

it('cek tiket membuka tiket dari kode maupun dari tautan penuh', function () {
    $antrian = tiketUji();

    $this->post(route('antrian.cari'), ['kode' => $antrian->kode_tiket])->assertRedirect(route('antrian.tiket', $antrian->kode_tiket));
    $this->post(route('antrian.cari'), ['kode' => route('antrian.tiket', $antrian->kode_tiket)])->assertRedirect(route('antrian.tiket', $antrian->kode_tiket));
});

it('cek tiket menolak kode yang tidak dikenal', function () {
    $this->post(route('antrian.cari'), ['kode' => str_repeat('z', 32)])->assertSessionHasErrors('kode');
    $this->get(route('antrian.cek'))->assertOk()->assertSee('Buka tiket');
});

it('papan antrian dan layar poli tetap tampil dengan banyak pasien menunggu', function () {
    $poli = Poli::factory()->create(['aktif' => true]);
    foreach (range(1, 30) as $i) {
        Antrian::create([
            'pasien_id' => Pasien::factory()->create()->id,
            'poli_id' => $poli->id,
            'nomor_antrian' => $poli->kode_poli.str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'status' => 'menunggu',
            'waktu_daftar' => now()->addSeconds($i),
            'tanggal' => now()->toDateString(),
        ]);
    }

    $this->get(route('antrian.layar'))->assertOk();
    $this->get(route('antrian.display', $poli))->assertOk();

    \Livewire\Livewire::test(\App\Livewire\AntrianDisplay::class, ['poliId' => $poli->id])
        ->assertSee('+18')
        ->assertSee($poli->kode_poli.'001');
});

it('halaman tiket layar penuh: tanpa navbar dan footer, ada tombol kembali ke beranda', function () {
    $antrian = tiketUji();

    $this->get(route('antrian.tiket', $antrian->kode_tiket))
        ->assertOk()
        ->assertSee('class="tk-back"', false)
        ->assertSee(route('beranda'), false)
        ->assertDontSee('id="nvWrap"', false)
        ->assertDontSee('class="ft"', false)
        ->assertDontSee('nv-ctabar', false);
});

it('layout publik biasa membawa skrip yang mengubah Ambil nomor menjadi Antrian saya', function () {
    $this->get(route('beranda'))->assertOk()->assertSee('Antrian saya', false);
});
