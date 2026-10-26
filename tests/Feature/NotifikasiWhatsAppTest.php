<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use App\Services\PesanAntrian;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'whatsapp.driver' => 'fonnte',
        'whatsapp.fonnte_token' => 'TOKEN123',
        'whatsapp.redirect_ke' => null,
    ]);
});

it('menormalkan nomor Indonesia ke format 62', function () {
    expect(WhatsAppService::normalisasi('081219102754'))->toBe('6281219102754');
    expect(WhatsAppService::normalisasi('0812-1910-2754'))->toBe('6281219102754');
    expect(WhatsAppService::normalisasi('+62 812 1910 2754'))->toBe('6281219102754');
    expect(WhatsAppService::normalisasi('81219102754'))->toBe('6281219102754');
    expect(WhatsAppService::normalisasi('123'))->toBeNull();
    expect(WhatsAppService::normalisasi(''))->toBeNull();
});

it('pesan panggilan memuat nama, nomor, poli, dokter, dan jam', function () {
    $pesan = PesanAntrian::panggilan('Budi', 'A005', 'Poli Umum', 'dr. Andi Wijaya', '09.41');

    expect($pesan)->toContain('Budi', 'A005', 'Poli Umum', 'dr. Andi Wijaya', '09.41', 'Giliran Anda sudah tiba');
});

it('mengirim lewat Fonnte dengan token dan nomor yang sudah dinormalkan', function () {
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success! message in queue'], 200)]);

    $hasil = app(WhatsAppService::class)->kirim('081219102754', 'Halo');

    expect($hasil['berhasil'])->toBeTrue();

    Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
        && $request->hasHeader('Authorization', 'TOKEN123')
        && $request['target'] === '6281219102754'
        && $request['message'] === 'Halo');
});

it('melaporkan gagal kalau Fonnte menolak', function () {
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'token invalid'], 200)]);

    $hasil = app(WhatsAppService::class)->kirim('081219102754', 'Halo');

    expect($hasil['berhasil'])->toBeFalse();
    expect($hasil['keterangan'])->toContain('token invalid');
});

it('mengalihkan semua pesan ke nomor uji kalau WA_REDIRECT_KE diisi', function () {
    config(['whatsapp.redirect_ke' => '081200000001']);
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

    app(WhatsAppService::class)->kirim('081234560001', 'Halo');

    Http::assertSent(fn ($request) => $request['target'] === '6281200000001'
        && str_contains($request['message'], 'UJI')
        && str_contains($request['message'], '6281234560001'));
});

it('driver log tidak mengirim apa pun ke internet', function () {
    config(['whatsapp.driver' => 'log']);
    Http::fake();

    $hasil = app(WhatsAppService::class)->kirim('081219102754', 'Halo');

    expect($hasil['berhasil'])->toBeTrue();
    Http::assertNothingSent();
});

it('mengirim WhatsApp ke pasien saat petugas memanggil antrian', function () {
    Http::fake(['api.fonnte.com/*' => Http::response(['status' => true], 200)]);

    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create(['kode_poli' => 'A', 'nama_poli' => 'Poli Umum']);
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'nama' => 'dr. Andi Wijaya']);
    $pasien = Pasien::factory()->create(['nama' => 'Budi Hartono', 'no_hp' => '081234567890']);

    Antrian::create([
        'pasien_id' => $pasien->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => 'A005',
        'status' => 'menunggu',
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ]);

    $this->actingAs($petugas)->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id]);

    Http::assertSent(fn ($request) => $request['target'] === '6281234567890'
        && str_contains($request['message'], 'A005')
        && str_contains($request['message'], 'Budi Hartono')
        && str_contains($request['message'], 'dr. Andi Wijaya'));
});

it('tidak mengirim WhatsApp kalau pasien tidak punya nomor HP', function () {
    Http::fake();

    $petugas = User::factory()->create(['role' => 'petugas']);
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $pasien = Pasien::factory()->create(['no_hp' => null]);

    Antrian::create([
        'pasien_id' => $pasien->id,
        'poli_id' => $poli->id,
        'nomor_antrian' => 'A001',
        'status' => 'menunggu',
        'waktu_daftar' => now(),
        'tanggal' => now()->toDateString(),
    ]);

    $this->actingAs($petugas)->post(route('antrian.panggil', $poli), ['dokter_id' => $dokter->id]);

    Http::assertNothingSent();
});

it('nomor HP disamarkan di log dan isi pesan tidak dicatat di luar lokal', function () {
    config(['whatsapp.driver' => 'log', 'whatsapp.redirect_ke' => null]);
    \Illuminate\Support\Facades\Log::spy();

    app(\App\Services\WhatsAppService::class)->kirim('081234567890', 'Halo Budi Hartono');

    \Illuminate\Support\Facades\Log::shouldHaveReceived('info')->withArgs(function ($pesan, $konteks = []) {
        return str_contains($pesan, '6281******890')
            && ! str_contains($pesan, '6281234567890')
            && ! str_contains(json_encode($konteks), 'Budi Hartono');
    });
});

it('samarkan menyisakan 4 angka awal dan 3 angka akhir', function () {
    expect(\App\Services\WhatsAppService::samarkan('6281234567890'))->toBe('6281******890');
    expect(\App\Services\WhatsAppService::samarkan('12345'))->toBe('*****');
});