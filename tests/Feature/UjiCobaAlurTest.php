<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DataUjiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('membuka semua halaman utama sesuai peran dan login akun uji berjalan', function () {
    $this->seed(DataUjiSeeder::class);

    $admin = User::query()->where('email', DataUjiSeeder::EMAIL_ADMIN)->firstOrFail();
    $petugas = User::query()->where('email', DataUjiSeeder::EMAIL_PETUGAS)->firstOrFail();
    $dokter = User::query()
        ->where('role', 'dokter')
        ->where('email', 'like', '%.uji@klinik.test')
        ->firstOrFail();
    $poli = Poli::query()->where('aktif', true)->firstOrFail();

    // Halaman publik
    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('Ambil nomor antrian')
        ->assertSee('Empat langkah');

    $this->get(route('antrian.create'))->assertOk()->assertSee('Ambil nomor antrian');
    $this->get(route('antrian.layar'))->assertOk();
    $this->get(route('antrian.display', $poli))->assertOk();

    $this->getJson(route('antrian.hari-ini'))
        ->assertOk()
        ->assertJsonStructure(['diperbarui', 'tanggal', 'ringkasan', 'poli', 'dokter']);

    // Login sungguhan memakai akun hasil seeder
    $this->post('/login', [
        'email' => DataUjiSeeder::EMAIL_ADMIN,
        'password' => DataUjiSeeder::PASSWORD_ADMIN,
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin);
    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    $this->post('/logout');

    // Admin
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.poli.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.dokter.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.poli.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.dokter.create'))->assertOk();

    $dokterPertama = Dokter::query()->where('aktif', true)->firstOrFail();
    $this->actingAs($admin)->get(route('admin.dokter.jadwal.index', $dokterPertama))->assertOk();

    // Petugas (sesi dibersihkan: ganti akun dalam satu sesi memang dianggap pembajakan oleh AuthenticateSession)
    $this->flushSession();
    $this->actingAs($petugas)->get(route('antrian.index'))->assertOk();
    $this->actingAs($petugas)->get(route('antrian.kelola', $poli))->assertOk();
    $this->actingAs($petugas)->get(route('antrian.analitik'))->assertOk()->assertSee('Analitik antrian');
    $this->actingAs($petugas)->get(route('antrian.riwayat'))->assertOk()->assertSee('Riwayat antrian');
    $this->actingAs($petugas)->get(route('antrian.riwayat.csv'))->assertOk();

    // Dokter
    $this->flushSession();
    $this->actingAs($dokter)->get(route('antrian.dokter'))->assertOk();

    // Pembatasan peran
    $this->flushSession();
    $this->actingAs($dokter)->get(route('admin.dashboard'))->assertForbidden();
    $this->flushSession();
    $this->actingAs($petugas)->get(route('admin.dashboard'))->assertForbidden();
});

it('alur ambil nomor sampai selesai pelayanan berjalan lancar', function () {
    $this->seed(DataUjiSeeder::class);

    $dokter = Dokter::query()
        ->where('aktif', true)
        ->whereNotNull('user_id')
        ->orderBy('id')
        ->firstOrFail();

    $poli = $dokter->poli;
    $userDokter = User::query()->findOrFail($dokter->user_id);
    $petugas = User::query()->where('email', DataUjiSeeder::EMAIL_PETUGAS)->firstOrFail();

    $poliPublik = Poli::query()
        ->where('aktif', true)
        ->whereHas('dokters', fn ($q) => $q->where('aktif', true))
        ->whereKeyNot($poli->id)
        ->first() ?? $poli;

    $dataPasien = [
        'poli_id' => $poliPublik->id,
        'nik' => '3273999999990001',
        'nama' => 'Pasien Alur Uji',
        'no_hp' => '081299990001',
        'tanggal_lahir' => '1994-04-04',
        'jenis_kelamin' => 'L',
    ];

    // Pasien baru mengambil nomor
    $this->post(route('antrian.store'), $dataPasien)->assertSessionHasNoErrors();

    $pasien = Pasien::query()->where('nik', $dataPasien['nik'])->firstOrFail();
    $antrianBaru = Antrian::query()
        ->where('pasien_id', $pasien->id)
        ->where('poli_id', $poliPublik->id)
        ->latest('id')
        ->firstOrFail();

    expect($antrianBaru->status)->toBe('menunggu');
    expect($antrianBaru->kode_tiket)->not->toBeEmpty();

    $this->get(route('antrian.tiket', $antrianBaru->kode_tiket))
        ->assertOk()
        ->assertSee('P***** A*** U**');

    // Pendaftaran ulang di poli yang sama mengembalikan tiket yang sama
    $jumlahSebelum = Antrian::query()->where('pasien_id', $pasien->id)->count();

    $this->post(route('antrian.store'), $dataPasien)
        ->assertRedirect(route('antrian.tiket', $antrianBaru->kode_tiket));

    expect(Antrian::query()->where('pasien_id', $pasien->id)->count())->toBe($jumlahSebelum);

    // NIK salah ditolak
    $this->post(route('antrian.store'), [
        'poli_id' => $poliPublik->id,
        'nik' => '12345',
        'nama' => 'NIK Salah',
    ])->assertSessionHasErrors('nik');

    // Petugas memanggil antrian paling lama menunggu (limpahan harus paling depan)
    $dipanggil = Antrian::query()
        ->untukPoli($poli->id)
        ->hariIni()
        ->menunggu()
        ->orderBy('waktu_daftar')
        ->firstOrFail();

    $this->actingAs($petugas)
        ->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id])
        ->assertSessionHas('success');

    $dipanggil->refresh();
    expect($dipanggil->status)->toBe('dipanggil');
    expect($dipanggil->dokter_id)->toBe($dokter->id);

    // Dokter mulai dan menyelesaikan pelayanan
    $this->flushSession();
    $this->actingAs($userDokter)
        ->post(route('antrian.mulai-layani', $dipanggil))
        ->assertSessionHas('success');

    $dipanggil->refresh();
    expect($dipanggil->status)->toBe('dilayani');
    expect($dipanggil->waktu_mulai_dilayani)->not->toBeNull();

    $this->actingAs($userDokter)
        ->post(route('antrian.selesai', $dipanggil))
        ->assertSessionHas('success');

    $dipanggil->refresh();
    expect($dipanggil->status)->toBe('selesai');
    expect($dipanggil->waktu_selesai)->not->toBeNull();

    // Panggil, tandai tidak hadir, lalu panggil ulang
    $this->flushSession();
    $berikutnya = Antrian::query()
        ->untukPoli($poli->id)
        ->hariIni()
        ->menunggu()
        ->orderBy('waktu_daftar')
        ->firstOrFail();

    $this->actingAs($petugas)
        ->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id])
        ->assertSessionHas('success');

    $berikutnya->refresh();
    expect($berikutnya->status)->toBe('dipanggil');

    $this->actingAs($petugas)
        ->post(route('antrian.tidak-hadir', $berikutnya))
        ->assertSessionHas('success');

    $berikutnya->refresh();
    expect($berikutnya->status)->toBe('tidak_hadir');

    $this->actingAs($petugas)
        ->post(route('antrian.panggil-ulang', $berikutnya))
        ->assertSessionHas('success');

    $berikutnya->refresh();
    expect($berikutnya->status)->toBe('dipanggil');
    expect($berikutnya->jumlah_panggil)->toBe(2);

    // Pasien boleh mengambil nomor baru setelah antrian sebelumnya selesai
    $antrianBaru->update([
        'status' => 'selesai',
        'waktu_dipanggil' => now(),
        'waktu_mulai_dilayani' => now(),
        'waktu_selesai' => now(),
    ]);

    $this->post(route('antrian.store'), $dataPasien)->assertSessionHasNoErrors();

    $antrianKedua = Antrian::query()
        ->where('pasien_id', $pasien->id)
        ->where('poli_id', $poliPublik->id)
        ->latest('id')
        ->firstOrFail();

    expect($antrianKedua->id)->not->toBe($antrianBaru->id);
    expect(Antrian::query()->where('pasien_id', $pasien->id)->count())->toBe($jumlahSebelum + 1);

    // Riwayat dan analitik tetap bisa dibuka setelah alur berjalan
    $this->actingAs($petugas)
        ->get(route('antrian.analitik', [
            'hari' => 14,
            'sampai' => Carbon::yesterday()->toDateString(),
        ]))
        ->assertOk();

    $this->actingAs($petugas)
        ->get(route('antrian.riwayat', [
            'dari' => Carbon::today()->subDays(6)->toDateString(),
            'sampai' => Carbon::today()->toDateString(),
        ]))
        ->assertOk();
});
