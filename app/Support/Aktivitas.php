<?php

namespace App\Support;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat log aktivitas. Kegagalan mencatat tidak boleh mengganggu pekerjaan pengguna.
 */
final class Aktivitas
{
    public static function catat(string $aksi, ?string $keterangan = null, ?User $pengguna = null): void
    {
        try {
            $pengguna ??= auth()->user();
            $request = app()->bound('request') ? request() : null;

            LogAktivitas::create([
                'user_id' => $pengguna?->id,
                'nama_pengguna' => $pengguna?->name,
                'peran' => $pengguna?->role,
                'aksi' => $aksi,
                'keterangan' => $keterangan !== null ? Str::limit($keterangan, 480, '') : null,
                'ip' => $request?->ip(),
                'perangkat' => $request ? Str::limit((string) $request->userAgent(), 150, '') : null,
            ]);
        } catch (Throwable) {
            // Tabel belum dimigrasi atau database sibuk: abaikan
        }
    }

    /** Samarkan deretan 16 digit (NIK) sebelum masuk ke log aktivitas. */
    public static function samarkan(?string $teks): ?string
    {
        return $teks === null ? null : preg_replace_callback('/\d{12,16}/', fn ($m) => substr($m[0], 0, 4).str_repeat('*', strlen($m[0]) - 8).substr($m[0], -4), $teks);
    }
}
