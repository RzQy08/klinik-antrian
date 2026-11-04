<?php

namespace App\Http\Controllers;

use App\Models\Poli;
use App\Services\RingkasanAntrian;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BerandaController extends Controller
{
    /**
     * Halaman awal publik. Panel antrian di hero membaca endpoint antrianHariIni() secara berkala
     * (tanpa memuat ulang halaman); kartu per poli ditangani komponen Livewire BerandaPoli.
     * Data awal ikut dikirim saat halaman dibuka supaya panel langsung terisi.
     */
    public function index(): View
    {
        $ringkasan = RingkasanAntrian::ambil();

        return view('beranda', [
            'polis' => Poli::where('aktif', true)->orderBy('kode_poli')->get(),
            'ringkasan' => $ringkasan,
            'statistik' => $ringkasan['ringkasan'],
            'dokterHariIni' => $ringkasan['dokter'],
        ]);
    }

    /** Data antrian hari ini (JSON) untuk panel di hero. Publik, hanya berisi nomor dan jumlah. */
    public function antrianHariIni(): JsonResponse
    {
        return response()
            ->json(RingkasanAntrian::ambil())
            ->header('Cache-Control', 'no-store');
    }
}
