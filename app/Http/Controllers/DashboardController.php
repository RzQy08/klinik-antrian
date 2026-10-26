<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Mengarahkan pengguna ke dashboard sesuai perannya setelah login.
 * Dibuat sebagai controller (bukan closure di web.php) supaya
 * php artisan route:cache bisa dijalankan untuk mempercepat aplikasi.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->isDokter()) {
            return redirect()->route('antrian.dokter');
        }

        return redirect()->route('antrian.index');
    }
}
