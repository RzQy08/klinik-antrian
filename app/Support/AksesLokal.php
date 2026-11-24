<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Menentukan apakah sebuah permintaan benar-benar datang dari komputer developer sendiri.
 *
 * Fitur khusus pengembangan (masuk cepat tanpa password, panel DEV) TIDAK boleh cukup dijaga
 * dengan APP_ENV=local. Kesalahan yang sering terjadi: aplikasi dibagikan lewat ngrok, port forward,
 * atau terlanjur di-upload ke hosting dengan APP_ENV=local, lalu siapa pun bisa masuk sebagai admin.
 *
 * Di sini permintaan dianggap lokal hanya bila semua syarat ini terpenuhi:
 *  - APP_ENV=local
 *  - tidak melewati proxy atau tunnel (tidak ada header X-Forwarded-* / Forwarded)
 *  - host-nya localhost, 127.0.0.1, [::1], *.localhost, atau *.test (Laragon)
 *  - alamat penyambungnya loopback
 */
final class AksesLokal
{
    private const LOOPBACK = ['127.0.0.1', '::1'];

    public static function diizinkan(?Request $request = null): bool
    {
        $request ??= request();

        if (! app()->isLocal()) {
            return false;
        }

        foreach (['X-Forwarded-For', 'X-Forwarded-Host', 'X-Forwarded-Proto', 'Forwarded', 'X-Real-IP'] as $header) {
            if ($request->headers->has($header)) {
                return false;
            }
        }

        return self::hostLokal($request->getHost())
            && in_array((string) $request->server('REMOTE_ADDR'), self::LOOPBACK, true);
    }

    /**
     * Khusus tombol DEV dan masuk cepat. Selain syarat lokal biasa, boleh juga lewat tunnel ngrok,
     * tetapi hanya bila DEV_MASUK_NGROK=true di .env, APP_ENV=local, hostnya domain ngrok, dan
     * koneksi dari agen ngrok di komputer ini (loopback). Tanpa DEV_MASUK_NGROK perilakunya sama
     * persis dengan diizinkan().
     *
     * Sengaja terpisah dari diizinkan(): kunci APP_DEBUG tetap memakai diizinkan(), jadi halaman
     * error yang menampilkan isi .env tidak pernah ikut terbuka lewat tunnel.
     */
    public static function masukCepatDiizinkan(?Request $request = null): bool
    {
        $request ??= request();

        if (self::diizinkan($request)) {
            return true;
        }

        if (! app()->isLocal() || ! filter_var(env('DEV_MASUK_NGROK', false), FILTER_VALIDATE_BOOL)) {
            return false;
        }

        return self::hostNgrok($request->getHost())
            && in_array((string) $request->server('REMOTE_ADDR'), self::LOOPBACK, true);
    }

    public static function hostNgrok(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        foreach (['.ngrok-free.app', '.ngrok-free.dev', '.ngrok.app', '.ngrok.dev', '.ngrok.io'] as $akhiran) {
            if (str_ends_with($host, $akhiran)) {
                return true;
            }
        }

        return false;
    }

    public static function hostLokal(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');
    }
}
