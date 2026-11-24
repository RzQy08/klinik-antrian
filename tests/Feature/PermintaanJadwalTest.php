<?php

use App\Livewire\Admin\JadwalDokterManager;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\PermintaanJadwal;
use App\Models\Poli;
use App\Models\User;
use Livewire\Livewire;

function dokterDenganJadwal(): array
{
    $user = User::factory()->create(['role' => 'dokter']);
    $dokter = Dokter::factory()->create(['poli_id' => Poli::factory()->create()->id, 'user_id' => $user->id]);
    JadwalPraktik::create(['dokter_id' => $dokter->id, 'hari' => 'senin', 'jam_mulai' => '08:00:00', 'jam_selesai' => '12:00:00', 'kuota' => 20]);

    return [$user, $dokter];
}

function dataPermintaan(array $ubah = []): array
{
    return array_merge(['hari' => 'senin', 'jam_mulai_baru' => '09:00', 'jam_selesai_baru' => '13:00', 'kuota_baru' => 25, 'alasan' => 'Bentrok'], $ubah);
}

it('dokter mengajukan perubahan dan jadwal lama tetap berlaku sampai disetujui', function () {
    [$user, $dokter] = dokterDenganJadwal();

    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan())->assertSessionHas('success');

    expect(PermintaanJadwal::where('dokter_id', $dokter->id)->first()->status)->toBe('menunggu');
    expect(JadwalPraktik::where('dokter_id', $dokter->id)->first()->jam_mulai)->toStartWith('08:00');
});

it('permintaan kedua untuk hari yang sama ditolak selama yang pertama menunggu', function () {
    [$user] = dokterDenganJadwal();

    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan());
    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan(['kuota_baru' => 30]))->assertSessionHas('error');

    expect(PermintaanJadwal::count())->toBe(1);
});

it('jam selesai harus setelah jam mulai', function () {
    [$user] = dokterDenganJadwal();

    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan(['jam_selesai_baru' => '08:30']))->assertSessionHasErrors('jam_selesai_baru');
});

it('hanya dokter yang bisa mengajukan', function () {
    $this->actingAs(User::factory()->create(['role' => 'petugas']))->post(route('permintaan-jadwal.store'), dataPermintaan())->assertForbidden();
});

it('admin menyetujui, jadwal praktik berubah, dan tidak bisa ditinjau dua kali', function () {
    [$user, $dokter] = dokterDenganJadwal();
    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan());
    $permintaan = PermintaanJadwal::first();

    $admin = User::factory()->create(['role' => 'admin']);
    Livewire::actingAs($admin)->test(JadwalDokterManager::class)->call('setujuiPermintaan', $permintaan->id);

    $jadwal = JadwalPraktik::where('dokter_id', $dokter->id)->where('hari', 'senin')->first();
    expect($jadwal->jam_mulai)->toStartWith('09:00')->and($jadwal->kuota)->toBe(25);
    expect($permintaan->fresh()->status)->toBe('disetujui');

    Livewire::actingAs($admin)->test(JadwalDokterManager::class)->call('tolakPermintaan', $permintaan->id);
    expect($permintaan->fresh()->status)->toBe('disetujui');
});

it('admin menolak, jadwal tidak berubah', function () {
    [$user, $dokter] = dokterDenganJadwal();
    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan());
    $permintaan = PermintaanJadwal::first();

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))->test(JadwalDokterManager::class)->call('tolakPermintaan', $permintaan->id);

    expect($permintaan->fresh()->status)->toBe('ditolak');
    expect(JadwalPraktik::where('dokter_id', $dokter->id)->first()->jam_mulai)->toStartWith('08:00');
});

it('dokter dan petugas tidak bisa menyetujui permintaan', function () {
    [$user] = dokterDenganJadwal();
    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan());
    $permintaan = PermintaanJadwal::first();

    $this->actingAs($user)->post(route('admin.permintaan-jadwal.setujui', $permintaan))->assertForbidden();
    Livewire::actingAs($user)->test(JadwalDokterManager::class)->call('setujuiPermintaan', $permintaan->id)->assertForbidden();

    expect($permintaan->fresh()->status)->toBe('menunggu');
});

it('dokter hanya bisa membatalkan permintaannya sendiri', function () {
    [$user] = dokterDenganJadwal();
    [$lain] = dokterDenganJadwal();
    $this->actingAs($user)->post(route('permintaan-jadwal.store'), dataPermintaan());
    $permintaan = PermintaanJadwal::first();

    $this->actingAs($lain)->delete(route('permintaan-jadwal.batalkan', $permintaan))->assertForbidden();
    $this->actingAs($user)->delete(route('permintaan-jadwal.batalkan', $permintaan));

    expect(PermintaanJadwal::count())->toBe(0);
});
