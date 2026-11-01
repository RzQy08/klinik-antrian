<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Halaman awal publik. Kartu antrian per poli ditangani komponen Livewire BerandaPoli
     * supaya bisa diperbarui tanpa memuat ulang halaman. Statistik di sini dihitung saat halaman dibuka.
     */
    public function index(): View
    {
        $polis = Poli::where('aktif', true)->orderBy('kode_poli')->get();

        $statistik = [
            'menunggu' => Antrian::hariIni()->menunggu()->count(),
            'selesai' => Antrian::hariIni()->selesai()->count(),
            'poli' => $polis->count(),
            'dokter' => Dokter::where('aktif', true)->count(),
        ];

        return view('beranda', compact('polis', 'statistik'));
    }
}
