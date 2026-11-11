<?php

use App\Livewire\Admin\DokterManager;
use App\Livewire\Admin\JadwalEditor;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Livewire\Livewire;

function adminUji(): User
{
    return User::factory()->create(['role' => 'admin']);
}

it('halaman kelola dokter, jadwal, tambah, dan ubah bisa dibuka admin', function () {
    $admin = adminUji();
    $dokter = Dokter::factory()->create();

    $this->actingAs($admin)->get(route('admin.dokter.index'))->assertOk()->assertSee($dokter->nama);
    $this->actingAs($admin)->get(route('admin.dokter.jadwal.index', $dokter))->assertOk()->assertSee('Jadwal praktik');
    $this->actingAs($admin)->get(route('admin.dokter.create'))->assertOk();
    $this->actingAs($admin)->get(route('admin.dokter.edit', $dokter))->assertOk()->assertSee($dokter->nama);
});

it('tabel dokter dibagi per halaman dan bisa dicari', function () {
    $admin = adminUji();
    $poli = Poli::factory()->create();
    Dokter::factory()->count(14)->create(['poli_id' => $poli->id]);
    Dokter::factory()->create(['poli_id' => $poli->id, 'nama' => 'dr. Zulkarnain Unik']);

    $uji = Livewire::actingAs($admin)->test(DokterManager::class);
    expect($uji->viewData('dokters')->count())->toBe(10);
    expect($uji->viewData('dokters')->total())->toBe(15);

    $uji->set('cari', 'zulkarnain')->assertSee('dr. Zulkarnain Unik');
    expect($uji->viewData('dokters')->total())->toBe(1);
});

it('menyaring dokter menurut poli, status, dan akun', function () {
    $admin = adminUji();
    $a = Poli::factory()->create();
    $b = Poli::factory()->create();
    Dokter::factory()->create(['poli_id' => $a->id, 'aktif' => true, 'user_id' => User::factory()->create(['role' => 'dokter'])->id]);
    Dokter::factory()->create(['poli_id' => $b->id, 'aktif' => false]);

    $uji = Livewire::actingAs($admin)->test(DokterManager::class);

    expect($uji->set('poliId', (string) $a->id)->viewData('dokters')->total())->toBe(1);
    expect($uji->set('poliId', '')->set('status', 'nonaktif')->viewData('dokters')->total())->toBe(1);
    expect($uji->set('status', 'semua')->set('akun', 'tanpa')->viewData('dokters')->total())->toBe(1);
});

it('dokter yang sedang menangani pasien tidak bisa dinonaktifkan', function () {
    $admin = adminUji();
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'aktif' => true]);
    Antrian::create([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id, 'poli_id' => $poli->id, 'dokter_id' => $dokter->id,
        'nomor_antrian' => 'A001', 'status' => 'dilayani', 'waktu_daftar' => now(), 'tanggal' => now()->toDateString(),
    ]);

    Livewire::actingAs($admin)->test(DokterManager::class)->call('toggleAktif', $dokter->id);
    expect($dokter->refresh()->aktif)->toBeTrue();

    $lain = Dokter::factory()->create(['aktif' => true]);
    Livewire::actingAs($admin)->test(DokterManager::class)->call('toggleAktif', $lain->id);
    expect($lain->refresh()->aktif)->toBeFalse();
});

it('dokter dengan riwayat tidak bisa dihapus, tanpa riwayat bisa', function () {
    $admin = adminUji();
    $poli = Poli::factory()->create();
    $berRiwayat = Dokter::factory()->create(['poli_id' => $poli->id]);
    Antrian::create([
        'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id, 'poli_id' => $poli->id, 'dokter_id' => $berRiwayat->id,
        'nomor_antrian' => 'A001', 'status' => 'selesai', 'waktu_daftar' => now(), 'tanggal' => now()->toDateString(),
    ]);
    $bersih = Dokter::factory()->create(['poli_id' => $poli->id]);

    Livewire::actingAs($admin)->test(DokterManager::class)->call('konfirmasiHapus', $berRiwayat->id)->call('hapus')->assertSee('tidak bisa dihapus');
    Livewire::actingAs($admin)->test(DokterManager::class)->call('konfirmasiHapus', $bersih->id)->call('hapus');

    expect(Dokter::find($berRiwayat->id))->not->toBeNull();
    expect(Dokter::find($bersih->id))->toBeNull();
});

it('non-admin tidak bisa mengubah dokter lewat aksi Livewire', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $dokter = Dokter::factory()->create(['aktif' => true]);

    Livewire::actingAs($petugas)->test(DokterManager::class)->call('toggleAktif', $dokter->id)->assertForbidden();
    expect($dokter->refresh()->aktif)->toBeTrue();
});

it('editor jadwal menyimpan hari aktif dan menghapus hari yang dimatikan', function () {
    $admin = adminUji();
    $dokter = Dokter::factory()->create();
    JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => 'minggu', 'jam_mulai' => '08:00', 'jam_selesai' => '12:00', 'kuota' => 10]);

    Livewire::actingAs($admin)->test(JadwalEditor::class, ['dokter' => $dokter])
        ->call('preset', 'senin-jumat')
        ->set('jadwal.senin.mulai', '09:00')
        ->set('jadwal.minggu.aktif', false)
        ->call('simpan')
        ->assertHasNoErrors();

    expect(JadwalPraktik::where('dokter_id', $dokter->id)->count())->toBe(5);
    expect(JadwalPraktik::where('dokter_id', $dokter->id)->where('hari', 'senin')->value('jam_mulai'))->toStartWith('09:00');
    expect(JadwalPraktik::where('dokter_id', $dokter->id)->where('hari', 'minggu')->exists())->toBeFalse();
});

it('editor jadwal menolak jam selesai sebelum jam mulai', function () {
    $admin = adminUji();
    $dokter = Dokter::factory()->create();

    Livewire::actingAs($admin)->test(JadwalEditor::class, ['dokter' => $dokter])
        ->set('jadwal.senin.aktif', true)
        ->set('jadwal.senin.mulai', '14:00')
        ->set('jadwal.senin.selesai', '09:00')
        ->call('simpan')
        ->assertHasErrors('jadwal.senin.selesai');

    expect(JadwalPraktik::where('dokter_id', $dokter->id)->count())->toBe(0);
});

it('non-admin tidak bisa menyimpan jadwal', function () {
    $petugas = User::factory()->create(['role' => 'petugas']);
    $dokter = Dokter::factory()->create();

    Livewire::actingAs($petugas)->test(JadwalEditor::class, ['dokter' => $dokter])->call('simpan')->assertForbidden();
});

it('mengubah dokter bisa sekalian membuat akun atau mengganti password', function () {
    $admin = adminUji();
    $poli = Poli::factory()->create();
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id, 'user_id' => null]);

    $this->actingAs($admin)->put(route('admin.dokter.update', $dokter), [
        'poli_id' => $poli->id, 'nama' => 'dr. Baru', 'aktif' => 1,
        'buat_akun' => 1, 'email' => 'dokter.baru@klinik.test', 'password' => 'Rahasia-Dokter#123',
    ])->assertRedirect(route('admin.dokter.index'));

    $dokter->refresh();
    expect($dokter->user)->not->toBeNull();
    expect($dokter->user->role)->toBe('dokter');

    $this->actingAs($admin)->put(route('admin.dokter.update', $dokter), [
        'poli_id' => $poli->id, 'nama' => 'dr. Baru', 'aktif' => 1, 'password_baru' => 'Sandi-Baru#456x',
    ])->assertRedirect();

    expect(\Illuminate\Support\Facades\Hash::check('Sandi-Baru#456x', $dokter->user->refresh()->password))->toBeTrue();
});
