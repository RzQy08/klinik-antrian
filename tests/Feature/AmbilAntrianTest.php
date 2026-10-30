<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('menampilkan halaman form ambil antrian tanpa perlu login', function () {
    $response = $this->get(route('antrian.create'));

    $response->assertOk();
});

it('pasien baru bisa ambil nomor antrian dan data pasiennya otomatis dibuat', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    Dokter::factory()->create(['poli_id' => $poli->id]);

    $response = $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '3201010101990001',
        'nama' => 'Budi Hartono',
        'no_hp' => '081234567890',
        'tanggal_lahir' => '1995-05-12',
        'jenis_kelamin' => 'L',
    ]);

    $antrian = Antrian::first();

    expect($antrian)->not->toBeNull();
    expect($antrian->nomor_antrian)->toBe('A001');
    expect($antrian->status)->toBe('menunggu');
    expect($antrian->pasien->nama)->toBe('Budi Hartono');

    $response->assertRedirect(route('antrian.tiket', $antrian->kode_tiket));
});

it('nomor antrian berurutan dan tidak bentrok untuk poli yang sama di hari yang sama', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'B']);
    Dokter::factory()->create(['poli_id' => $poli->id]);

    foreach (range(1, 3) as $i) {
        $this->post(route('antrian.store'), [
            'poli_id' => $poli->id,
            'nik' => str_pad((string) $i, 16, '0', STR_PAD_LEFT),
            'nama' => "Pasien {$i}",
        ]);
    }

    $nomorAntrian = Antrian::orderBy('id')->pluck('nomor_antrian')->all();

    expect($nomorAntrian)->toBe(['B001', 'B002', 'B003']);
});

it('menolak submit kalau NIK bukan 16 digit', function () {
    $poli = Poli::factory()->create();

    $response = $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '12345',
        'nama' => 'Test User',
    ]);

    $response->assertSessionHasErrors('nik');
    expect(Antrian::count())->toBe(0);
});

it('menolak submit kalau poli_id tidak valid', function () {
    $response = $this->post(route('antrian.store'), [
        'poli_id' => 99999,
        'nik' => '3201010101990001',
        'nama' => 'Test User',
    ]);

    $response->assertSessionHasErrors('poli_id');
});

it('tiket tidak bisa dibuka lewat ID berurutan, hanya lewat kode acak', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    Dokter::factory()->create(['poli_id' => $poli->id]);

    $this->post(route('antrian.store'), [
        'poli_id' => $poli->id,
        'nik' => '3201010101990002',
        'nama' => 'Siti Aminah',
    ]);

    $antrian = Antrian::first();

    $this->get('/antrian/tiket/' . $antrian->id)->assertNotFound();
    $this->get(route('antrian.tiket', $antrian->kode_tiket))->assertOk();
});

it('nomor RM pasien baru unik dan tidak bergantung pada jumlah baris pasien', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    Dokter::factory()->create(['poli_id' => $poli->id]);

    // Pasien dihapus: jumlah baris turun, tapi nomor RM baru tetap tidak boleh bentrok
    $pasienLama = \App\Models\Pasien::factory()->create();
    \App\Models\Pasien::factory()->create();
    $pasienLama->delete();

    $niks = ['3201010101990001', '3201010101990002'];

    foreach ($niks as $nik) {
        $this->post(route('antrian.store'), [
            'poli_id' => $poli->id,
            'nik' => $nik,
            'nama' => 'Pasien ' . $nik,
        ])->assertSessionHasNoErrors();
    }

    $noRm = \App\Models\Pasien::whereIn('nik', $niks)->pluck('no_rm');
    $awalan = 'RM-' . now()->format('ymd') . '-';

    expect($noRm)->toHaveCount(2);
    expect($noRm->unique())->toHaveCount(2);
    expect($noRm->every(fn ($n) => str_starts_with($n, $awalan)))->toBeTrue();
});

it('pasien lama dengan NIK yang sama tidak dibuat ulang', function () {
    $poli = Poli::factory()->create(['kode_poli' => 'A']);
    Dokter::factory()->create(['poli_id' => $poli->id]);

    foreach ([1, 2] as $i) {
        $this->post(route('antrian.store'), [
            'poli_id' => $poli->id,
            'nik' => '3201010101990003',
            'nama' => 'Dewi Lestari',
        ])->assertSessionHasNoErrors();
    }

    expect(\App\Models\Pasien::where('nik', '3201010101990003')->count())->toBe(1);
    expect(Antrian::count())->toBe(1);
});

   it('pendaftaran kedua di poli yang sama diarahkan ke tiket yang sudah ada', function () {
       $poli = Poli::factory()->create(['kode_poli' => 'A']);
       Dokter::factory()->create(['poli_id' => $poli->id]);

       $data = ['poli_id' => $poli->id, 'nik' => '3201010101990004', 'nama' => 'Rina Sari'];

       $this->post(route('antrian.store'), $data);
       $pertama = Antrian::first();

       $this->post(route('antrian.store'), $data)
           ->assertRedirect(route('antrian.tiket', $pertama->kode_tiket));

       expect(Antrian::count())->toBe(1);
   });

   it('pasien yang sama boleh mengambil nomor di poli lain', function () {
       $poliA = Poli::factory()->create(['kode_poli' => 'A']);
       $poliB = Poli::factory()->create(['kode_poli' => 'B']);

       foreach ([$poliA, $poliB] as $poli) {
           $this->post(route('antrian.store'), ['poli_id' => $poli->id, 'nik' => '3201010101990005', 'nama' => 'Tono']);
       }

       expect(Antrian::count())->toBe(2);
   });

   it('pasien boleh mengambil nomor baru setelah antrian sebelumnya selesai', function () {
       $poli = Poli::factory()->create(['kode_poli' => 'A']);
       $data = ['poli_id' => $poli->id, 'nik' => '3201010101990006', 'nama' => 'Wati'];

       $this->post(route('antrian.store'), $data);
       Antrian::first()->update(['status' => 'selesai']);
       $this->post(route('antrian.store'), $data);

       expect(Antrian::count())->toBe(2);
   });

   it('tiket menampilkan pemberitahuan giliran saat pasien dipanggil', function () {
       $poli = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);

       $this->post(route('antrian.store'), ['poli_id' => $poli->id, 'nik' => '3201010101990007', 'nama' => 'Eko']);
       $antrian = Antrian::first();
       $antrian->update(['status' => 'dipanggil']);

       $this->get(route('antrian.tiket', $antrian->kode_tiket))
           ->assertOk()
           ->assertSee('Giliran Anda!')
           ->assertDontSee('Perkiraan tunggu');
   });

   it('aplikasi memakai zona waktu WIB dan bahasa Indonesia', function () {
       expect(config('app.timezone'))->toBe('Asia/Jakarta');
       expect(app()->getLocale())->toBe('id');

       $this->post(route('antrian.store'), ['nik' => '12345'])
           ->assertSessionHasErrors(['nik' => 'NIK harus terdiri dari 16 digit angka.']);
   });