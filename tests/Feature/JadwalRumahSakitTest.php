<?php

use App\Livewire\Admin\JadwalDokterManager;
use App\Livewire\Admin\JadwalRumahSakitManager;
use App\Models\CutiDokter;
use App\Models\Dokter;
use App\Models\HariLibur;
use App\Models\JadwalRumahSakit;
use App\Models\Pengaturan;
use App\Models\Poli;
use App\Models\User;
use App\Services\JamOperasional;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

afterEach(fn () => Carbon::setTestNow());

function aturJam(string $hari, bool $buka, string $daftarBuka = '07:00', string $daftarTutup = '11:00', string $selesai = '14:00'): void
{
    JadwalRumahSakit::updateOrCreate(['hari' => $hari], ['buka' => $buka, 'pendaftaran_buka' => $daftarBuka, 'pendaftaran_tutup' => $daftarTutup, 'layanan_selesai' => $selesai]);
    JamOperasional::lupakan();
}

it('memakai jam khusus per hari dari database', function () {
    Pengaturan::simpan('operasional_aktif', '1');
    aturJam('sabtu', true, '07:00', '10:00', '12:00');

    Carbon::setTestNow('2026-11-14 10:30:00'); // Sabtu
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();

    Carbon::setTestNow('2026-11-14 09:30:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue();
    expect(JamOperasional::jam('layanan_selesai'))->toBe('12:00');
});

it('menolak pendaftaran dan layanan pada hari tutup mingguan', function () {
    Pengaturan::simpan('operasional_aktif', '1');
    aturJam('minggu', false);

    Carbon::setTestNow('2026-11-15 09:00:00'); // Minggu
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse()
        ->and(JamOperasional::layananBerjalan())->toBeFalse()
        ->and(JamOperasional::pesanPendaftaranTutup())->toContain('tutup hari Minggu');
});

it('menutup rumah sakit pada tanggal libur dan menyebut hari buka berikutnya', function () {
    Pengaturan::simpan('operasional_aktif', '1');
    HariLibur::create(['tanggal' => '2026-12-25', 'keterangan' => 'Hari Raya Natal']);
    JamOperasional::lupakan();

    Carbon::setTestNow('2026-12-25 08:00:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse()
        ->and(JamOperasional::pesanPendaftaranTutup())->toContain('Hari Raya Natal')->toContain('Silakan datang kembali');
});

it('tidak membatasi apa pun saat saklar pembatasan mati', function () {
    Pengaturan::simpan('operasional_aktif', '0');
    HariLibur::create(['tanggal' => '2026-12-25', 'keterangan' => 'Natal']);
    JamOperasional::lupakan();

    Carbon::setTestNow('2026-12-25 03:00:00');
    expect(JamOperasional::pendaftaranDibuka())->toBeTrue()->and(JamOperasional::layananBerjalan())->toBeTrue();
});

it('admin menyimpan jadwal rumah sakit dan langsung berlaku', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $komponen = Livewire::test(JadwalRumahSakitManager::class)
        ->set('aktif', true)
        ->set('hari.senin.pendaftaran_tutup', '09:00')
        ->set('hari.minggu.buka', false)
        ->call('simpan')
        ->assertHasNoErrors();

    expect(JamOperasional::aktif())->toBeTrue();
    Carbon::setTestNow('2026-11-09 09:30:00'); // Senin
    expect(JamOperasional::pendaftaranDibuka())->toBeFalse();
    expect(JadwalRumahSakit::where('hari', 'minggu')->value('buka'))->toBeFalsy();
});

it('menolak jam yang tidak masuk akal', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    Livewire::test(JadwalRumahSakitManager::class)
        ->set('hari.senin.pendaftaran_buka', '12:00')
        ->set('hari.senin.pendaftaran_tutup', '08:00')
        ->call('simpan')
        ->assertHasErrors(['hari.senin.pendaftaran_tutup']);
});

it('admin menambah dan menghapus hari libur, tanggal lampau ditolak', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    Livewire::test(JadwalRumahSakitManager::class)
        ->set('liburTanggal', now()->addDays(5)->toDateString())->set('liburKeterangan', 'Libur uji')
        ->call('tambahLibur')->assertHasNoErrors();
    expect(HariLibur::count())->toBe(1);

    Livewire::test(JadwalRumahSakitManager::class)
        ->set('liburTanggal', '2020-01-01')->set('liburKeterangan', 'Lampau')
        ->call('tambahLibur')->assertHasErrors(['liburTanggal']);

    Livewire::test(JadwalRumahSakitManager::class)->call('hapusLibur', HariLibur::first()->id);
    expect(HariLibur::count())->toBe(0);
});

it('hanya admin yang boleh mengubah jadwal', function () {
    $this->actingAs(User::factory()->create(['role' => 'petugas']));

    Livewire::test(JadwalRumahSakitManager::class)->call('simpan')->assertForbidden();
    Livewire::test(JadwalDokterManager::class)->call('hapusCuti', 1)->assertForbidden();
    $this->get(route('admin.penjadwalan'))->assertForbidden();
});

it('halaman jadwal terbuka untuk admin di kedua tab', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    $this->get(route('admin.penjadwalan', 'rumah-sakit'))->assertOk()->assertSee('Hari libur dan penutupan khusus');
    $this->get(route('admin.penjadwalan', 'dokter'))->assertOk()->assertSee('Cuti dan dokter berhalangan');
});

it('dokter yang cuti tidak bisa dipilih untuk memanggil pasien dan ditandai cuti', function () {
    $poli = Poli::factory()->create();
    $cuti = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => true]);
    $masuk = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => true]);
    CutiDokter::create(['dokter_id' => $cuti->id, 'tanggal_mulai' => now()->toDateString(), 'tanggal_selesai' => now()->addDay()->toDateString()]);

    expect($cuti->sedangCuti())->toBeTrue()->and($masuk->sedangCuti())->toBeFalse();
    expect(Dokter::where('poli_id', $poli->id)->tidakCuti()->pluck('id')->all())->toBe([$masuk->id]);
});

it('admin mencatat cuti dan cuti yang beririsan ditolak', function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $dokter = Dokter::factory()->create(['aktif' => true]);

    $komponen = Livewire::test(JadwalDokterManager::class)
        ->set('dokterId', (string) $dokter->id)
        ->set('mulai', now()->addDays(3)->toDateString())->set('selesai', now()->addDays(5)->toDateString())
        ->call('tambahCuti')->assertHasNoErrors();
    expect(CutiDokter::count())->toBe(1);

    $komponen->set('dokterId', (string) $dokter->id)
        ->set('mulai', now()->addDays(4)->toDateString())->set('selesai', now()->addDays(6)->toDateString())
        ->call('tambahCuti')->assertHasErrors(['mulai']);
    expect(CutiDokter::count())->toBe(1);
});
