<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use Livewire\Attributes\Lazy;
use App\Services\PemanggilOtomatis;
use Livewire\Component;

class AntrianDisplay extends Component
{
    public int $poliId;

    public function mount(int $poliId): void
    {
        $this->poliId = $poliId;
    }

    public function render()
    {
        // Layar ruang tunggu selalu terbuka, jadi ikut menjalankan aturan waktu antrian (dibatasi tiap beberapa detik)
        PemanggilOtomatis::tick();

        $poli = Poli::findOrFail($this->poliId);

        // antrian yang sedang aktif dipanggil/dilayani (paling baru)
        $sedangDipanggil = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('pasien')
            ->latest('waktu_dipanggil')
            ->first();

        // 5 antrian terakhir yang sudah dipanggil (riwayat panggilan, buat ditampilkan di layar)
        $riwayatDipanggil = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->whereNotNull('waktu_dipanggil')
            ->latest('waktu_dipanggil')
            ->limit(5)
            ->get();

        // hanya satu nomor berikutnya (urutan datang, FIFO) agar layar tidak ramai
        $berikut = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->orderBy('id')->limit(1)->pluck('nomor_antrian');

        $jumlahMenunggu = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->count();

        return view('livewire.antrian-display', [
            'poli' => $poli,
            'sedangDipanggil' => $sedangDipanggil,
            'riwayatDipanggil' => $riwayatDipanggil,
            'jumlahMenunggu' => $jumlahMenunggu,
            'berikut' => $berikut,
        ]);
    }
}