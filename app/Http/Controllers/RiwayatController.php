<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Poli;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Riwayat antrian lama untuk petugas: filter, ringkasan, dan unduh CSV.
 */
class RiwayatController extends Controller
{
    private const STATUS = ['menunggu', 'dipanggil', 'dilayani', 'selesai', 'tidak_hadir', 'batal'];

    // Rentang dibatasi supaya satu permintaan tidak membaca data bertahun-tahun
    private const MAKS_HARI = 92;

    public function index(Request $request): View
    {
        $filter = $this->filter($request);
        $dasar = $this->query($filter);

        $ringkasan = (clone $dasar)->reorder()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $data = $dasar->paginate(25)->withQueryString();

        return view('antrian.riwayat', [
            'data' => $data,
            'filter' => $filter,
            'ringkasan' => $ringkasan,
            'total' => (int) $ringkasan->sum(),
            'polis' => Poli::orderBy('kode_poli')->get(['id', 'kode_poli', 'nama_poli']),
            'daftarStatus' => self::STATUS,
        ]);
    }

    public function csv(Request $request): StreamedResponse
    {
        $filter = $this->filter($request);
        $query = $this->query($filter);

        $nama = 'riwayat-antrian-'.$filter['dari']->format('Ymd').'-'.$filter['sampai']->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca UTF-8

            // Pemisah titik koma: Excel dengan pengaturan regional Indonesia memakai ini
            $tulis = fn (array $baris) => fputcsv($out, array_map([$this, 'aman'], $baris), ';');

            $tulis(['Tanggal', 'Nomor', 'Poli', 'Pasien', 'No RM', 'NIK (disamarkan)', 'Dokter', 'Status',
                'Waktu daftar', 'Dipanggil', 'Mulai dilayani', 'Selesai', 'Tunggu (menit)', 'Layanan (menit)', 'Limpahan dari']);

            $query->chunk(500, function ($baris) use ($tulis) {
                foreach ($baris as $a) {
                    $tulis([
                        $a->tanggal?->format('Y-m-d'),
                        $a->nomor_antrian,
                        $a->poli?->nama_poli,
                        $a->pasien?->nama,
                        $a->pasien?->no_rm,
                        self::samarkanNik($a->pasien?->nik),
                        $a->dokter?->nama,
                        $a->status,
                        $a->waktu_daftar?->format('Y-m-d H:i:s'),
                        $a->waktu_dipanggil?->format('Y-m-d H:i:s'),
                        $a->waktu_mulai_dilayani?->format('Y-m-d H:i:s'),
                        $a->waktu_selesai?->format('Y-m-d H:i:s'),
                        $a->lama_tunggu_detik !== null ? round($a->lama_tunggu_detik / 60, 1) : null,
                        $a->lama_layanan_detik !== null ? round($a->lama_layanan_detik / 60, 1) : null,
                        $a->tanggal_asal?->format('Y-m-d'),
                    ]);
                }
            });

            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Normalisasi dan batasi input filter.
     */
    private function filter(Request $request): array
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
            'poli_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:'.implode(',', self::STATUS)],
            'q' => ['nullable', 'string', 'max:50'],
        ]);

        $sampai = $request->filled('sampai') ? Carbon::parse($request->input('sampai'))->startOfDay() : Carbon::today();
        $dari = $request->filled('dari') ? Carbon::parse($request->input('dari'))->startOfDay() : $sampai->copy()->subDays(6);

        if ($dari->greaterThan($sampai)) {
            [$dari, $sampai] = [$sampai, $dari];
        }

        if ($dari->diffInDays($sampai) > self::MAKS_HARI) {
            $dari = $sampai->copy()->subDays(self::MAKS_HARI);
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'poli_id' => $request->integer('poli_id') ?: null,
            'status' => $request->input('status'),
            'q' => trim((string) $request->input('q')) ?: null,
        ];
    }

    private function query(array $filter): Builder
    {
        $query = Antrian::query()
            ->with('pasien:id,nama,nik,no_rm', 'poli:id,kode_poli,nama_poli', 'dokter:id,nama')
            ->antaraTanggal($filter['dari'], $filter['sampai'])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_daftar')
            ->orderByDesc('id');

        if ($filter['poli_id']) {
            $query->where('poli_id', $filter['poli_id']);
        }

        if ($filter['status']) {
            $query->where('status', $filter['status']);
        }

        if ($filter['q']) {
            // Karakter khusus LIKE dinetralkan supaya "%" atau "_" dicari apa adanya
            $kata = addcslashes($filter['q'], '%_\\');

            $query->where(function (Builder $q) use ($kata) {
                $q->where('nomor_antrian', 'like', $kata.'%')
                    ->orWhereHas('pasien', fn (Builder $p) => $p
                        ->where('nama', 'like', '%'.$kata.'%')
                        ->orWhere('nik', 'like', $kata.'%')
                        ->orWhere('no_rm', 'like', $kata.'%'));
            });
        }

        return $query;
    }

    public static function samarkanNik(?string $nik): ?string
    {
        if ($nik === null || strlen($nik) < 9) {
            return $nik;
        }

        return substr($nik, 0, 4).str_repeat('*', strlen($nik) - 8).substr($nik, -4);
    }

    /**
     * Cegah CSV injection: sel yang diawali = + - @ dibaca Excel sebagai rumus.
     */
    private function aman(mixed $nilai): mixed
    {
        if (is_string($nilai) && $nilai !== '' && in_array($nilai[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$nilai;
        }

        return $nilai;
    }
}
