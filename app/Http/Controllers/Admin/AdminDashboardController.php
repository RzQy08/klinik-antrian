<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Halaman utama admin - pintu masuk ke semua fitur kelola data master.
     */
    public function index(): View
    {
        $ringkasan = [
            'jumlah_poli' => Poli::count(),
            'jumlah_dokter' => Dokter::count(),
            'jumlah_user' => User::count(),
        ];

        return view('admin.dashboard', compact('ringkasan'));
    }
}
