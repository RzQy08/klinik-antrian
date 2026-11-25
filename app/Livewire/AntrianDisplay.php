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

        // semua dokter di poli ini yang sedang punya pasien (dipanggil atau diperiksa), terbaru di atas
        $diRuangan = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('dokter:id,nama')
            ->orderByDesc('waktu_dipanggil')
            ->get(['id', 'poli_id', 'dokter_id', 'nomor_antrian', 'status', 'jumlah_panggil', 'waktu_dipanggil']);

        $sedangDipanggil = $diRuangan->first();

        // pasien yang sudah dipanggil tapi belum datang
        $telat = Antrian::untukPoli($poli->id)->hariIni()->where('status', 'telat')->orderBy('waktu_telat')->pluck('nomor_antrian');

        // urutan datang (FIFO). Yang ditampilkan dibatasi supaya layar tetap rapi walau antrian panjang
        $batas = 12;
        $berikut = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->orderBy('waktu_daftar')->orderBy('id')->limit($batas)->pluck('nomor_antrian');

        $jumlahMenunggu = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->count();

        return view('livewire.antrian-display', [
            'poli' => $poli,
            'sedangDipanggil' => $sedangDipanggil,
            'diRuangan' => $diRuangan,
            'telat' => $telat,
            'sisa' => max(0, $jumlahMenunggu - $berikut->count()),
            'jumlahMenunggu' => $jumlahMenunggu,
            'berikut' => $berikut,
        ]);
    }
}