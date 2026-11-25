<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use App\Services\PemanggilOtomatis;
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
        // Layar ruang tunggu selalu terbuka, jadi ikut menjalankan aturan waktu antrian (dibatasi tiap beberapa detik)
        PemanggilOtomatis::tick();

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

        // yang terlambat (sudah dipanggil, belum hadir): ditampilkan sebagai penanda kecil di kartu poli
        $telat = Antrian::hariIni()->where('status', 'telat')->get(['poli_id', 'nomor_antrian'])->groupBy('poli_id');

        $baris = $polis->map(function (Poli $poli) use ($aktifPerPoli, $menunggu, $telat) {
            $antrianPoli = $menunggu->get($poli->id, collect());

            return (object) [
                'poli' => $poli,
                'sekarang' => optional($aktifPerPoli->get($poli->id))->first(),
                // dokter lain di poli yang sama yang juga sedang punya pasien
                'lainnya' => max(0, $aktifPerPoli->get($poli->id, collect())->count() - 1),
                'telat' => $telat->get($poli->id, collect())->count(),
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
