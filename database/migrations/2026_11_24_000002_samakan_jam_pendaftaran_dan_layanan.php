<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Aturan baru: pendaftaran antrian ditutup pukul 14.00 dan layanan berakhir pukul 16.00
 * (atau lebih awal bila semua pasien sudah selesai) di semua hari buka.
 * Hari yang tutup (Minggu) tidak disentuh; admin tetap bisa mengubahnya lagi di menu Jadwal.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('jadwal_rumah_sakit')->where('buka', true)->update([
            'pendaftaran_tutup' => '14:00',
            'layanan_selesai' => '16:00',
            'updated_at' => now(),
        ]);

        DB::table('jadwal_rumah_sakit')->where('buka', false)->update([
            'pendaftaran_tutup' => '14:00',
            'layanan_selesai' => '16:00',
        ]);
    }

    public function down(): void
    {
        // Jam lama tidak dipulihkan: tiap hari bisa saja sudah diatur admin
    }
};
