<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Kirim satu pesan WhatsApp.
     *
     * @return array{berhasil: bool, keterangan: string}
     */
    public function kirim(string $nomor, string $pesan): array
    {
        $tujuan = self::normalisasi($nomor);

        if ($tujuan === null) {
            return ['berhasil' => false, 'keterangan' => "Nomor \"{$nomor}\" tidak valid."];
        }

        // Mode uji: semua pesan dialihkan ke satu nomor supaya pasien dummy tidak ikut menerima
        $alih = config('whatsapp.redirect_ke');

        if ($alih) {
            $nomorAlih = self::normalisasi((string) $alih);

            if ($nomorAlih !== null) {
                $pesan = "[UJI - seharusnya untuk {$tujuan}]\n\n" . $pesan;
                $tujuan = $nomorAlih;
            }
        }

        $driver = strtolower(trim((string) config('whatsapp.driver')));

        return match ($driver) {
            'fonnte' => $this->kirimFonnte($tujuan, $pesan),
            'log' => $this->kirimLog($tujuan, $pesan),
            default => [
                'berhasil' => false,
                'keterangan' => "Driver \"{$driver}\" tidak dikenal. Isi WA_DRIVER di file .env dengan fonnte atau log, lalu jalankan php artisan config:clear.",
            ],
        };
    }

    /**
     * Ubah nomor ke format internasional tanpa plus: 0812... menjadi 62812...
     * Mengembalikan null kalau nomor tidak masuk akal.
     */
    public static function normalisasi(string $nomor): ?string
    {
        $angka = preg_replace('/\D+/', '', $nomor);

        if ($angka === null || $angka === '') {
            return null;
        }

        if (str_starts_with($angka, '0')) {
            $angka = '62' . substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62' . $angka;
        } elseif (! str_starts_with($angka, '62')) {
            return null;
        }

        $panjang = strlen($angka);

        return ($panjang >= 10 && $panjang <= 15) ? $angka : null;
    }

        /**
     * Samarkan nomor untuk log: 6281234567890 menjadi 6281******890.
     */
    public static function samarkan(string $nomor): string
    {
        $panjang = strlen($nomor);

        if ($panjang <= 7) {
            return str_repeat('*', $panjang);
        }

        return substr($nomor, 0, 4) . str_repeat('*', $panjang - 7) . substr($nomor, -3);
    }

    private function kirimLog(string $tujuan, string $pesan): array
    {
        // Isi pesan memuat nama pasien, jadi hanya dicatat di lingkungan lokal
        Log::info('[WhatsApp - driver log] ke ' . self::samarkan($tujuan), [
            'pesan' => app()->isLocal() ? $pesan : '(isi pesan tidak dicatat di luar lingkungan lokal)',
        ]);

        return [
            'berhasil' => true,
            'keterangan' => 'Driver log: pesan hanya ditulis ke storage/logs/laravel.log dan TIDAK dikirim ke WhatsApp.',
        ];
    }

    private function kirimFonnte(string $tujuan, string $pesan): array
    {
        $token = config('whatsapp.fonnte_token');

        if (! $token) {
            return ['berhasil' => false, 'keterangan' => 'WA_FONNTE_TOKEN belum diisi di file .env.'];
        }

        try {
            $respons = Http::withHeaders(['Authorization' => $token])
                ->timeout(15)
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $tujuan,
                    'message' => $pesan,
                ]);

            $json = $respons->json() ?? [];
            $berhasil = $respons->successful() && ($json['status'] ?? false) === true;
            $keterangan = (string) ($json['detail'] ?? $json['reason'] ?? $respons->body());

                        $tampil = self::samarkan($tujuan);

            if ($berhasil) {
                Log::info("[WhatsApp - Fonnte] terkirim ke {$tampil}: {$keterangan}");
            } else {
                Log::warning("[WhatsApp - Fonnte] gagal ke {$tampil}: {$keterangan}");
            }

            return ['berhasil' => $berhasil, 'keterangan' => $keterangan];
        } catch (\Throwable $e) {
            Log::error('[WhatsApp - Fonnte] error: ' . $e->getMessage());

            return ['berhasil' => false, 'keterangan' => 'Gagal menghubungi Fonnte: ' . $e->getMessage()];
        }
    }
}
