<?php

namespace App\Livewire;

use App\Models\Antrian;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pemberitahuan di halaman kelola petugas: memantau jumlah pasien menunggu.
 * Halaman tidak dimuat ulang otomatis (supaya dokter yang sudah dipilih tidak hilang);
 * petugas cukup menekan tombol "Muat ulang" saat ada perubahan.
 */
class PemberitahuAntrianBaru extends Component
{
    #[Locked]
    public int $poliId;

    // Jumlah menunggu saat halaman dibuka
    #[Locked]
    public int $jumlahAwal = 0;

    public function mount(int $poliId): void
    {
        $this->poliId = $poliId;
        $this->jumlahAwal = $this->hitung();
    }

    private function hitung(): int
    {
        return Antrian::untukPoli($this->poliId)->hariIni()->menunggu()->count();
    }

    public function render()
    {
        $sekarang = $this->hitung();

        return view('livewire.pemberitahu-antrian-baru', [
            'berubah' => $sekarang !== $this->jumlahAwal,
            'sekarang' => $sekarang,
            'selisih' => $sekarang - $this->jumlahAwal,
        ]);
    }
}
