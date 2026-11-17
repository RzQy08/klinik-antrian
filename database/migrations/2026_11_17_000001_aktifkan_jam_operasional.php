<?php

use App\Services\JamOperasional;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyalakan pembatasan jam operasional untuk semua database yang sudah berjalan.
 *
 * Setelah ini pendaftaran antrian otomatis tertutup di luar jam pendaftaran, pada hari
 * tutup, dan pada tanggal libur. Pemanggilan pasien berhenti setelah jam layanan selesai.
 * Admin tetap bisa mengubah jam atau mematikan saklarnya di menu Jadwal.
 */
return new class extends Migration
{
    private const JAM_BAWAAN = [
        ['senin', 1, '07:00', '11:00', '14:00'],
        ['selasa', 1, '07:00', '11:00', '14:00'],
        ['rabu', 1, '07:00', '11:00', '14:00'],
        ['kamis', 1, '07:00', '11:00', '14:00'],
        ['jumat', 1, '07:00', '10:30', '12:00'],
        ['sabtu', 1, '07:00', '10:00', '12:00'],
        ['minggu', 0, '07:00', '11:00', '14:00'],
    ];

    public function up(): void
    {
        $sekarang = now();

        // Hari yang belum punya jadwal diisi jam bawaan; jadwal yang sudah diatur admin tidak diubah
        foreach (self::JAM_BAWAAN as [$hari, $buka, $daftarBuka, $daftarTutup, $selesai]) {
            DB::table('jadwal_rumah_sakit')->insertOrIgnore([
                'hari' => $hari,
                'buka' => $buka,
                'pendaftaran_buka' => $daftarBuka,
                'pendaftaran_tutup' => $daftarTutup,
                'layanan_selesai' => $selesai,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }

        DB::table('pengaturans')->updateOrInsert(
            ['kunci' => 'operasional_aktif'],
            ['nilai' => '1', 'updated_at' => $sekarang, 'created_at' => $sekarang],
        );

        // Cache jam operasional dibuang supaya perubahan langsung berlaku
        JamOperasional::lupakan();
    }

    public function down(): void
    {
        DB::table('pengaturans')->where('kunci', 'operasional_aktif')->update(['nilai' => '0', 'updated_at' => now()]);

        JamOperasional::lupakan();
    }
};
