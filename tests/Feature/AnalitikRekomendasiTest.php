<?php

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Carbon\Carbon;

it('halaman analitik menampilkan rekomendasi dan simulasi jumlah dokter', function () {
    $poli = Poli::factory()->create(['nama_poli' => 'Poli Umum', 'kode_poli' => 'A', 'aktif' => true]);
    $dokter = Dokter::factory()->create(['poli_id' => $poli->id]);
    $kemarin = Carbon::yesterday();

    // 6 pasien selesai kemarin, lama layanan 15 menit
    foreach (range(1, 6) as $i) {
        $mulai = $kemarin->copy()->setTime(9, 0)->addMinutes($i * 20);

        Antrian::create([
            'pasien_id' => Pasien::factory()->create(['no_hp' => null])->id,
            'poli_id' => $poli->id,
            'dokter_id' => $dokter->id,
            'nomor_antrian' => 'A00'.$i,
            'status' => 'selesai',
            'waktu_daftar' => $mulai->copy()->subMinutes(10),
            'waktu_mulai_dilayani' => $mulai,
            'waktu_selesai' => $mulai->copy()->addMinutes(15),
            'tanggal' => $kemarin->toDateString(),
        ]);
    }

    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('antrian.analitik', ['hari' => 7, 'target' => 10]))
        ->assertOk()
        ->assertSee('Rekomendasi jumlah dokter')
        ->assertSee('Target rata-rata tunggu untuk rekomendasi dokter')
        ->assertSee('Saat ini');
});
