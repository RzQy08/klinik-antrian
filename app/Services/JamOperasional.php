<?php

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Aturan jam operasional. Semua pembatasan hanya berlaku kalau
 * OPERASIONAL_AKTIF=true; kalau tidak, semuanya dianggap terbuka.
 */
class JamOperasional
{
    public static function aktif(): bool
    {
        return (bool) config('operasional.aktif');
    }

    public static function pendaftaranDibuka(?CarbonInterface $waktu = null): bool
    {
        if (! self::aktif()) {
            return true;
        }

        $jam = ($waktu ?? now())->format('H:i');

        return $jam >= config('operasional.pendaftaran_buka')
            && $jam < config('operasional.pendaftaran_tutup');
    }

    public static function layananBerjalan(?CarbonInterface $waktu = null): bool
    {
        if (! self::aktif()) {
            return true;
        }

        return ($waktu ?? now())->format('H:i') < config('operasional.layanan_selesai');
    }

    public static function pesanPendaftaranTutup(?CarbonInterface $waktu = null): string
    {
        $jam = ($waktu ?? now())->format('H:i');
        $buka = self::tampil(config('operasional.pendaftaran_buka'));
        $tutup = self::tampil(config('operasional.pendaftaran_tutup'));

        if ($jam < config('operasional.pendaftaran_buka')) {
            return "Pendaftaran antrian belum dibuka. Pendaftaran dibuka mulai pukul {$buka}.";
        }

        return "Pendaftaran antrian hari ini sudah ditutup pukul {$tutup}. Silakan datang kembali besok mulai pukul {$buka}.";
    }

    public static function pesanLayananSelesai(): string
    {
        $selesai = self::tampil(config('operasional.layanan_selesai'));

        return "Jam layanan sudah selesai (pukul {$selesai}). Antrian yang belum terpanggil akan dipindahkan ke hari berikutnya.";
    }

    /** 07:00 menjadi 07.00, sesuai kebiasaan penulisan jam di Indonesia. */
    public static function tampil(string $jam): string
    {
        return str_replace(':', '.', $jam);
    }
}
