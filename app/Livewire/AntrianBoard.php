<?php

namespace App\Livewire;

use App\Models\Antrian;
use App\Models\Poli;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Papan antrian seluruh poli untuk TV ruang tunggu.
 *
 * Panel besar menyorot satu poli bergantian (otomatis). Panggilan baru dari poli mana pun
 * langsung disorot lebih lama. Siklusnya dihitung di server dari polling 2 detik yang sama,
 * jadi tidak perlu JavaScript tambahan dan tidak bentrok dengan morph Livewire.
 * Hanya 5 query berapa pun jumlah poli.
 */
class AntrianBoard extends Component
{
    /** Detik tiap poli disorot saat berputar otomatis. */
    private const DURASI_PUTAR = 8;

    /** Detik sorotan bertahan setelah ada panggilan baru atau poli dipilih manual. */
    private const DURASI_PANGGILAN = 16;

    #[Locked]
    public ?int $aktifId = null;

    #[Locked]
    public int $sampai = 0;

    #[Locked]
    public int $durasiAktif = 8;

    #[Locked]
    public string $tandaTerakhir = '';

    #[Locked]
    public bool $panggilanBaru = false;

    public bool $jeda = false;

    private ?Collection $polisMemo = null;

    public function mount(): void
    {
        $ids = $this->idPoli();
        $terbaru = $this->panggilanTerbaru();

        $this->tandaTerakhir = $this->tanda($terbaru);
        $this->aktifId = ($terbaru && in_array((int) $terbaru->poli_id, $ids, true))
            ? (int) $terbaru->poli_id
            : ($ids[0] ?? null);

        $this->sorot(self::DURASI_PUTAR);
    }

    /** Dipanggil polling tiap 2 detik: deteksi panggilan baru, lalu putar ke poli berikutnya bila waktunya. */
    public function segarkan(): void
    {
        $ids = $this->idPoli();

        if ($ids === []) {
            $this->aktifId = null;

            return;
        }

        $terbaru = $this->panggilanTerbaru();
        $tanda = $this->tanda($terbaru);

        if ($tanda !== '' && $tanda !== $this->tandaTerakhir && in_array((int) $terbaru->poli_id, $ids, true)) {
            $this->aktifId = (int) $terbaru->poli_id;
            $this->panggilanBaru = true;
            $this->sorot(self::DURASI_PANGGILAN);
        } elseif (! in_array($this->aktifId, $ids, true)) {
            $this->aktifId = $ids[0];
            $this->panggilanBaru = false;
            $this->sorot(self::DURASI_PUTAR);
        } elseif (! $this->jeda && time() >= $this->sampai - 1) {
            $this->maju($ids);
        }

        $this->tandaTerakhir = $tanda;
    }

    /** Dipilih manual (ketuk titik atau baris poli). */
    public function pilih(int $id): void
    {
        if (in_array($id, $this->idPoli(), true)) {
            $this->aktifId = $id;
            $this->panggilanBaru = false;
            $this->sorot(self::DURASI_PANGGILAN);
        }
    }

    public function toggleJeda(): void
    {
        $this->jeda = ! $this->jeda;

        if (! $this->jeda) {
            $this->sorot(self::DURASI_PUTAR);
        }
    }

    public function render()
    {
        $polis = $this->polis();

        // yang sedang dipanggil/dilayani di semua poli, terbaru di atas
        $aktifPerPoli = Antrian::hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('dokter:id,nama')
            ->orderByDesc('waktu_dipanggil')
            ->get()
            ->groupBy('poli_id');

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
                'sekarang' => $aktifPerPoli->get($poli->id)?->first(),
                'berikut' => $antrianPoli->take(4)->pluck('nomor_antrian'),
                'menunggu' => $antrianPoli->count(),
            ];
        });

        return view('livewire.antrian-board', [
            'baris' => $baris,
        ]);
    }

    private function maju(array $ids): void
    {
        $posisi = array_search($this->aktifId, $ids, true);
        $this->aktifId = $ids[(($posisi === false ? -1 : $posisi) + 1) % count($ids)];
        $this->panggilanBaru = false;
        $this->sorot(self::DURASI_PUTAR);
    }

    private function sorot(int $detik): void
    {
        $this->durasiAktif = $detik;
        $this->sampai = time() + $detik;
    }

    private function panggilanTerbaru(): ?Antrian
    {
        return Antrian::hariIni()
            ->whereNotNull('waktu_dipanggil')
            ->orderByDesc('waktu_dipanggil')
            ->orderByDesc('id')
            ->first(['id', 'poli_id', 'jumlah_panggil', 'waktu_dipanggil']);
    }

    /** Berubah setiap ada panggilan atau panggil ulang. */
    private function tanda(?Antrian $antrian): string
    {
        return $antrian
            ? $antrian->id.'-'.$antrian->jumlah_panggil.'-'.$antrian->waktu_dipanggil?->timestamp
            : '';
    }

    private function polis(): Collection
    {
        return $this->polisMemo ??= Poli::where('aktif', true)->orderBy('kode_poli')->get();
    }

    /** @return array<int, int> */
    private function idPoli(): array
    {
        return $this->polis()->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}