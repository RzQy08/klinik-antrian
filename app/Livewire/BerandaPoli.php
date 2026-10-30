<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use Livewire\Component;

/**
 * Kartu antrian per poli di beranda publik. Tiga kueri berapa pun jumlah poli.
 * Nomor yang sedang dipanggil bertransisi ke nomor berikutnya saat polling menemukan perubahan.
 */
class BerandaPoli extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $baris = [];

    public function mount(): void
    {
        $this->baris = $this->susun(false);
    }

    public function segarkan(): void
    {
        $this->baris = $this->susun(true);
    }

    /** @return array<int, array<string, mixed>> */
    private function susun(bool $animasi): array
    {
        $lama = collect($this->baris)->keyBy('id');

        // Kueri 1: poli aktif
        $polis = Poli::where('aktif', true)->orderBy('kode_poli')->get(['id', 'nama_poli', 'kode_poli']);

        // Kueri 2: nomor yang sedang dipanggil/dilayani hari ini, terbaru per poli, nama dokter lewat join
        $aktif = Antrian::hariIni()
            ->leftJoin('dokters', 'dokters.id', '=', 'antrians.dokter_id')
            ->whereIn('antrians.status', ['dipanggil', 'dilayani'])
            ->orderByDesc('antrians.waktu_dipanggil')
            ->get(['antrians.poli_id', 'antrians.nomor_antrian', 'antrians.status', 'antrians.jumlah_panggil', 'dokters.nama as nama_dokter'])
            ->unique('poli_id')
            ->keyBy('poli_id');

        // Kueri 3: jumlah menunggu per poli
        $menunggu = Antrian::hariIni()->menunggu()
            ->selectRaw('poli_id, COUNT(*) as jumlah')
            ->groupBy('poli_id')
            ->pluck('jumlah', 'poli_id');

        $maks = max(1, (int) $menunggu->max());

        return $polis->map(function (Poli $poli) use ($aktif, $menunggu, $lama, $animasi, $maks) {
            $a = $aktif->get($poli->id);
            $nomor = $a?->nomor_antrian;
            $kunci = ($nomor ?? 'kosong') . '-' . ($a?->jumlah_panggil ?? 0);
            $dulu = $lama->get($poli->id);
            $berubah = $animasi && $dulu !== null && $dulu['kunci'] !== $kunci;
            $jumlah = (int) $menunggu->get($poli->id, 0);

            return [
                'id' => $poli->id,
                'kode' => $poli->kode_poli,
                'nama' => $poli->nama_poli,
                'nomor' => $nomor,
                'status' => $a?->status,
                'dokter' => $a?->nama_dokter,
                'menunggu' => $jumlah,
                'persen' => $jumlah > 0 ? max(6, (int) round($jumlah / $maks * 100)) : 0,
                'kunci' => $kunci,
                'bergerak' => $berubah,
                'sebelumnya' => $berubah ? $dulu['nomor'] : null,
            ];
        })->all();
    }

    public function render()
    {
        return view('livewire.beranda-poli');
    }
}
