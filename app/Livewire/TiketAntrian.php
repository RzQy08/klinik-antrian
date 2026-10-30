<?php

namespace App\Livewire;

use App\Models\Antrian;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Isi tiket pasien. Diperbarui sendiri oleh Livewire tanpa memuat ulang seluruh halaman,
 * dan berhenti memantau begitu antrian selesai atau pasien ditandai tidak hadir.
 */
class TiketAntrian extends Component
{
    // Dikunci supaya tidak bisa diubah dari browser (tidak bisa mengintip tiket orang lain)
    #[Locked]
    public int $antrianId;

    public function mount(int $antrianId): void
    {
        $this->antrianId = $antrianId;
    }

    public function render()
    {
        $antrian = Antrian::with('poli', 'pasien', 'dokter')->findOrFail($this->antrianId);

        $posisiDidepan = Antrian::untukPoli($antrian->poli_id)
            ->hariIni()
            ->menunggu()
            ->where('waktu_daftar', '<', $antrian->waktu_daftar)
            ->count();

        return view('livewire.tiket-antrian', [
            'antrian' => $antrian,
            'posisiDidepan' => $posisiDidepan,
            'masihBerjalan' => in_array($antrian->status, ['menunggu', 'dipanggil', 'dilayani'], true),
        ]);
    }
}
