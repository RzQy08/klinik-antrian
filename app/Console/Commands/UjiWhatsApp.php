<?php

namespace App\Console\Commands;

use App\Services\PesanAntrian;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class UjiWhatsApp extends Command
{
    /**
     * Contoh:
     *   php artisan wa:uji                 -> ke WA_NOMOR_UJI
     *   php artisan wa:uji 081234567890    -> ke nomor tertentu
     */
    protected $signature = 'wa:uji {nomor? : Nomor tujuan. Default: WA_NOMOR_UJI di .env}';

    protected $description = 'Kirim contoh pesan "giliran Anda" ke satu nomor WhatsApp untuk menguji koneksi.';

    public function handle(WhatsAppService $wa): int
    {
        $nomor = $this->argument('nomor') ?: config('whatsapp.nomor_uji');

        if (! $nomor) {
            $this->error('Nomor tujuan belum ada. Isi WA_NOMOR_UJI di .env atau tulis nomornya setelah perintah.');

            return self::FAILURE;
        }

        $this->line('Driver  : ' . config('whatsapp.driver'));
        $this->line('Tujuan  : ' . $nomor);

        if (config('whatsapp.redirect_ke')) {
            $this->warn('WA_REDIRECT_KE aktif, pesan dialihkan ke ' . config('whatsapp.redirect_ke'));
        }

        $pesan = PesanAntrian::panggilan('Budi Hartono', 'A005', 'Poli Umum', 'dr. Andi Wijaya', now()->format('H.i'));

        $this->newLine();
        $this->line($pesan);
        $this->newLine();

        $hasil = $wa->kirim((string) $nomor, $pesan);

        if ($hasil['berhasil']) {
            $this->info('Berhasil: ' . $hasil['keterangan']);

            return self::SUCCESS;
        }

        $this->error('Gagal: ' . $hasil['keterangan']);

        return self::FAILURE;
    }
}
