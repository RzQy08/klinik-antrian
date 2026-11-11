<?php

namespace Database\Seeders;

use App\Models\CutiDokter;
use App\Models\Dokter;
use App\Models\HariLibur;
use App\Models\JadwalRumahSakit;
use App\Models\JadwalPraktik;
use App\Services\JamOperasional;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk menu Jadwal: jam buka rumah sakit, hari libur, cuti dokter, dan
 * jadwal praktik bagi dokter yang belum punya. Aman dijalankan berulang kali.
 */
class JadwalRumahSakitSeeder extends Seeder
{
    public function run(): void
    {
        // Jam buka: Senin sampai Jumat penuh, Sabtu setengah hari, Minggu tutup
        $jam = [
            'senin' => [true, '07:00', '11:00', '14:00'],
            'selasa' => [true, '07:00', '11:00', '14:00'],
            'rabu' => [true, '07:00', '11:00', '14:00'],
            'kamis' => [true, '07:00', '11:00', '14:00'],
            'jumat' => [true, '07:00', '10:30', '12:00'],
            'sabtu' => [true, '07:00', '10:00', '12:00'],
            'minggu' => [false, '07:00', '11:00', '14:00'],
        ];

        foreach ($jam as $hari => [$buka, $daftarBuka, $daftarTutup, $selesai]) {
            JadwalRumahSakit::updateOrCreate(['hari' => $hari], [
                'buka' => $buka,
                'pendaftaran_buka' => $daftarBuka,
                'pendaftaran_tutup' => $daftarTutup,
                'layanan_selesai' => $selesai,
            ]);
        }

        // Hari libur nasional dan cuti bersama (contoh, silakan sesuaikan dengan SKB terbaru)
        $libur = [
            '2026-12-25' => 'Hari Raya Natal',
            '2026-12-26' => 'Cuti Bersama Natal',
            '2027-01-01' => 'Tahun Baru Masehi',
            '2027-02-06' => 'Tahun Baru Imlek',
            '2027-03-10' => 'Hari Raya Idul Fitri (perkiraan)',
            '2027-05-01' => 'Hari Buruh Internasional',
            '2027-08-17' => 'Hari Kemerdekaan Republik Indonesia',
        ];

        foreach ($libur as $tanggal => $ket) {
            HariLibur::updateOrCreate(['tanggal' => $tanggal], ['keterangan' => $ket]);
        }

        // Jadwal praktik untuk dokter yang belum punya satu pun, supaya tabel mingguan tidak kosong
        $pola = [
            ['senin', 'rabu', 'jumat'],
            ['selasa', 'kamis', 'sabtu'],
            ['senin', 'selasa', 'kamis'],
            ['rabu', 'kamis', 'sabtu'],
        ];

        Dokter::where('aktif', true)->doesntHave('jadwalPraktiks')->orderBy('id')->get()->each(function (Dokter $d, int $i) use ($pola) {
            foreach ($pola[$i % count($pola)] as $hari) {
                JadwalPraktik::create([
                    'dokter_id' => $d->id,
                    'hari' => $hari,
                    'jam_mulai' => $hari === 'sabtu' ? '08:00' : '08:00',
                    'jam_selesai' => $hari === 'sabtu' ? '11:30' : '13:30',
                    'kuota' => $hari === 'sabtu' ? 25 : 40,
                ]);
            }
        });

        // Satu contoh cuti dokter di waktu dekat
        $dokter = Dokter::where('aktif', true)->orderBy('id')->skip(1)->first() ?? Dokter::where('aktif', true)->first();

        if ($dokter && ! CutiDokter::where('dokter_id', $dokter->id)->exists()) {
            CutiDokter::create([
                'dokter_id' => $dokter->id,
                'tanggal_mulai' => now()->addDays(10)->toDateString(),
                'tanggal_selesai' => now()->addDays(12)->toDateString(),
                'alasan' => 'Seminar kedokteran',
            ]);
        }

        JamOperasional::lupakan();
    }
}
