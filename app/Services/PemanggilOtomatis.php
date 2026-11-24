<?php

namespace App\Services;

use App\Jobs\KirimNotifikasiPanggilan;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Menjalankan aturan waktu antrian. Dipanggil dari penjadwal (tiap menit), dari layar yang terbuka
 * (dibatasi supaya tidak dikerjakan berulang), dan langsung setelah pelayanan selesai.
 *
 *   1. Dipanggil lalu tidak datang selama BATAS_TELAT_MENIT   -> telat (sementara)
 *   2. Telat tanpa kabar selama BATAS_TIDAK_HADIR_MENIT        -> tidak hadir
 *   3. Dokter yang sedang kosong                               -> pasien berikutnya dipanggil otomatis
 *
 * Aturan ini aman dijalankan berulang kali dan dari beberapa tempat sekaligus.
 */
class PemanggilOtomatis
{
    /** Jalankan paling sering sekali per beberapa detik, dari mana pun asalnya. */
    public static function tick(int $detik = 8): void
    {
        try {
            if (Cache::add('antrian.tick', 1, $detik)) {
                self::jalankan();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{telat: int, tidak_hadir: int, dipanggil: int} */
    public static function jalankan(?int $poliId = null): array
    {
        $hasil = ['telat' => 0, 'tidak_hadir' => 0, 'dipanggil' => 0];

        $hasil['telat'] = self::tandaiTelat($poliId);
        $hasil['tidak_hadir'] = self::tandaiTidakHadir($poliId);

        if (config('antrian.panggil_otomatis') && JamOperasional::layananBerjalan()) {
            $hasil['dipanggil'] = self::panggilUntukDokterKosong($poliId);
        }

        return $hasil;
    }

    private static function tandaiTelat(?int $poliId): int
    {
        $batas = now()->subMinutes(max(1, (int) config('antrian.batas_telat_menit')));

        return Antrian::hariIni()
            ->where('status', 'dipanggil')
            ->where('waktu_dipanggil', '<=', $batas)
            ->when($poliId, fn ($q) => $q->where('poli_id', $poliId))
            ->update(['status' => 'telat', 'waktu_telat' => now()]);
    }

    private static function tandaiTidakHadir(?int $poliId): int
    {
        $batas = now()->subMinutes(max(1, (int) config('antrian.batas_tidak_hadir_menit')));

        return Antrian::hariIni()
            ->where('status', 'telat')
            ->where('waktu_telat', '<=', $batas)
            ->when($poliId, fn ($q) => $q->where('poli_id', $poliId))
            ->update(['status' => 'tidak_hadir']);
    }

    private static function panggilUntukDokterKosong(?int $poliId): int
    {
        $polis = Poli::where('aktif', true)
            ->when($poliId, fn ($q) => $q->whereKey($poliId))
            ->whereHas('antrians', fn ($q) => $q->hariIni()->menunggu())
            ->pluck('id');

        $jumlah = 0;

        foreach ($polis as $id) {
            $dokters = Dokter::where('poli_id', $id)
                ->where('aktif', true)
                ->tidakCuti()
                ->when(config('antrian.ikuti_jadwal'), fn ($q) => $q->bertugasSekarang())
                ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('antrians')
                    ->whereColumn('antrians.dokter_id', 'dokters.id')
                    ->where('antrians.tanggal', '>=', now()->toDateString())
                    ->where('antrians.tanggal', '<', now()->addDay()->toDateString())
                    ->whereIn('antrians.status', ['dipanggil', 'dilayani']))
                ->orderBy('id')
                ->get(['id']);

            foreach ($dokters as $dokter) {
                if (self::panggilSatu($id, $dokter->id)) {
                    $jumlah++;
                }
            }
        }

        return $jumlah;
    }

    private static function panggilSatu(int $poliId, int $dokterId): bool
    {
        $antrian = DB::transaction(function () use ($poliId, $dokterId) {
            // Dokter bisa saja sudah mendapat pasien dari petugas sejak daftar di atas dibuat
            $sibuk = Antrian::hariIni()->where('dokter_id', $dokterId)->whereIn('status', ['dipanggil', 'dilayani'])->exists();

            if ($sibuk) {
                return null;
            }

            $antrian = Antrian::untukPoli($poliId)->hariIni()->menunggu()->orderBy('waktu_daftar')->lockForUpdate()->first();

            $antrian?->update([
                'status' => 'dipanggil',
                'dokter_id' => $dokterId,
                'waktu_dipanggil' => now(),
                'jumlah_panggil' => 1,
            ]);

            return $antrian;
        });

        if (! $antrian) {
            return false;
        }

        KirimNotifikasiPanggilan::dispatchAfterResponse($antrian->id);

        return true;
    }
}
