<?php

namespace App\Jobs;

use App\Models\Antrian;
use App\Services\PesanAntrian;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Kirim WhatsApp "giliran Anda" saat petugas memanggil (atau memanggil ulang) pasien.
 *
 * Dipanggil dengan dispatchAfterResponse(): berjalan SETELAH halaman petugas
 * selesai dimuat, jadi tombol panggil tidak terasa lambat dan tidak perlu
 * queue worker. Kegagalan WhatsApp tidak pernah membatalkan pemanggilan.
 */
class KirimNotifikasiPanggilan
{
    use Dispatchable;

    public function __construct(public int $antrianId)
    {
    }

    public function handle(WhatsAppService $wa): void
    {
        try {
            $antrian = Antrian::with('pasien', 'poli', 'dokter')->find($this->antrianId);

            if (! $antrian) {
                return;
            }

            $nomorHp = $antrian->pasien?->no_hp;

            if (! $nomorHp) {
                Log::info("[WhatsApp] antrian {$antrian->nomor_antrian} dilewati: pasien tidak punya nomor HP.");

                return;
            }

            $pesan = PesanAntrian::panggilan(
                $antrian->pasien->nama,
                $antrian->nomor_antrian,
                $antrian->poli->nama_poli,
                $antrian->dokter?->nama,
                $antrian->waktu_dipanggil?->format('H.i'),
                max(1, (int) $antrian->jumlah_panggil),
            );

            $wa->kirim($nomorHp, $pesan);
        } catch (\Throwable $e) {
            Log::error('[WhatsApp] notifikasi panggilan gagal: ' . $e->getMessage());
        }
    }
}
