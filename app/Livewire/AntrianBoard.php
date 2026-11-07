<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use Livewire\Component;

/**
 * Papan antrian seluruh poli untuk TV ruang tunggu utama.
 *
 * Tampilan: panggilan terbaru dari poli mana pun di panel besar, daftar semua
 * poli di sisi kanan. Hanya 4 query berapa pun jumlah poli, karena komponen ini
 * di-refresh tiap 2 detik oleh setiap layar yang terbuka.
 */
class AntrianBoard extends Component
{
    public function render()
    {
        $polis = Poli::where('aktif', true)->orderBy('kode_poli')->get();

        // yang sedang dipanggil/dilayani di semua poli, terbaru di atas
        $aktif = Antrian::hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('dokter:id,nama')
            ->orderByDesc('waktu_dipanggil')
            ->get();

        $terbaru = $aktif->first();
        $aktifPerPoli = $aktif->groupBy('poli_id');

        // riwayat panggilan hari ini (termasuk yang sudah selesai), tanpa yang sedang tampil di panel besar
        $sebelumnya = Antrian::hariIni()
            ->whereNotNull('waktu_dipanggil')
            ->orderByDesc('waktu_dipanggil')
            ->limit(8)
            ->get(['id', 'poli_id', 'nomor_antrian', 'waktu_dipanggil'])
            ->reject(fn ($a) => $terbaru && $a->id === $terbaru->id)
            ->take(6)
            ->values();

        // yang masih menunggu: hanya kolom yang dibutuhkan, urut FIFO
        $menunggu = Antrian::hariIni()
            ->menunggu()
            ->orderBy('waktu_daftar')
            ->get(['id', 'poli_id', 'nomor_antrian'])
            ->groupBy('poli_id');

        $baris = $polis->map(function (Poli $poli) use ($aktifPerPoli, $menunggu) {
            $antrianPoli = $menunggu->get($poli->id, collect());

            return (object) [
                'poli' => $poli,
                'sekarang' => optional($aktifPerPoli->get($poli->id))->first(),
                'berikut' => $antrianPoli->take(3)->pluck('nomor_antrian'),
                'menunggu' => $antrianPoli->count(),
            ];
        });

        return view('livewire.antrian-board', [
            'baris' => $baris,
            'terbaru' => $terbaru,
            'poliTerbaru' => $terbaru ? $polis->firstWhere('id', $terbaru->poli_id) : null,
            'sebelumnya' => $sebelumnya,
            'polisById' => $polis->keyBy('id'),
        ]);
    }
}
