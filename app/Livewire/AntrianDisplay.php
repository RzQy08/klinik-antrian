<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use Livewire\Attributes\Lazy;
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

        $jumlahMenunggu = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->count();

        return view('livewire.antrian-display', [
            'poli' => $poli,
            'sedangDipanggil' => $sedangDipanggil,
            'riwayatDipanggil' => $riwayatDipanggil,
            'jumlahMenunggu' => $jumlahMenunggu,
        ]);
    }
}