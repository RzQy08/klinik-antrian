<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        // Tidak membatasi sumber skrip (halaman memakai skrip inline), tetapi menutup pembajakan form, frame, dan plugin
        $response->headers->set('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");

        // Halaman yang memuat data pasien atau dibuka petugas tidak boleh tersimpan di cache browser:
        // di komputer bersama, tombol Back setelah logout tidak boleh memunculkan data lagi.
        // Halaman itu juga tidak boleh muncul di hasil pencarian Google (tiket yang tautannya dibagikan).
        if ($request->user() || $request->is('antrian/tiket/*', 'admin/*', 'antrian/riwayat*', 'antrian/kelola/*', 'antrian/dokter', 'profile*')) {
            $response->headers->set('Cache-Control', 'no-store, private, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        // HSTS hanya masuk akal di produksi dan lewat HTTPS
        if (app()->isProduction() && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}