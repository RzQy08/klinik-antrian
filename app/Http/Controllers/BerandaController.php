<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Halaman awal publik. Angka antrian per poli ditangani komponen Livewire BerandaPoli
     * supaya bisa diperbarui tanpa memuat ulang halaman.
     */
    public function index(): View
    {
        $polis = Poli::where('aktif', true)->orderBy('kode_poli')->get();

        return view('beranda', compact('polis'));
    }
}
