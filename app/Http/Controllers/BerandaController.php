<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Halaman awal yang bisa dibuka siapa saja tanpa login:
     * ringkasan antrian hari ini per poli dan peta semua halaman sistem.
     */
    public function index(): View
    {
        $polis = Poli::where('aktif', true)
            ->orderBy('kode_poli')
            ->withCount([
                'antrians as menunggu_count' => fn ($q) => $q->hariIni()->menunggu(),
            ])
            ->get();

        return view('beranda', compact('polis'));
    }
}
