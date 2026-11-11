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

    public static function hostLokal(string $host): bool
    {
        $host = strtolower(trim($host, '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_ends_with($host, '.localhost')
            || str_ends_with($host, '.test');
    }
}
