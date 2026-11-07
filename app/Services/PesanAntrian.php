<?php

namespace App\Services;

/**
 * Susunan teks pesan WhatsApp. Dipisah dari pengiriman supaya mudah diubah.
 * Tanda *teks* membuat huruf tebal di WhatsApp.
 */
class PesanAntrian
{
    public static function panggilan(
        string $namaPasien,
        string $nomorAntrian,
        string $namaPoli,
        ?string $namaDokter,
        ?string $jamPanggil,
        int $panggilanKe = 1
    ): string {
        $tempat = $namaDokter ? "{$namaPoli} ({$namaDokter})" : $namaPoli;
        $jam = $jamPanggil ? " pukul {$jamPanggil}" : '';

        if ($panggilanKe > 1) {
            $isi = "*Panggilan ke-{$panggilanKe}.* Nomor *{$nomorAntrian}* dipanggil lagi di {$tempat}{$jam}, tapi belum ada yang masuk.\n\nMohon segera ke ruang poli.";
        } else {
            $isi = "*Giliran Anda sudah tiba.* Nomor *{$nomorAntrian}* dipanggil di {$tempat}{$jam}.\n\nSilakan langsung masuk ke ruang poli.";
        }

        return implode("\n", [
            "Halo {$namaPasien},",
            '',
            $isi,
            '',
            'Kalau Anda belum sempat, nomor ini masih bisa dipanggil ulang selama jam layanan.',
            '',
            'Salam,',
            config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama'),
        ]);
    }

    /**
     * Pesan untuk pasien yang antriannya belum terpanggil dan dipindah ke hari berikutnya.
     */
    public static function limpahan(
        string $namaPasien,
        string $nomorBaru,
        string $nomorLama,
        string $namaPoli,
        string $tanggalAsal,
        string $tanggalBaru
    ): string {
        return implode("\n", [
            "Halo {$namaPasien},",
            '',
            "Nomor {$nomorLama} di {$namaPoli} pada {$tanggalAsal} belum sempat dipanggil karena jam layanan sudah habis. Kami pindahkan ke {$tanggalBaru}, dan Anda didahulukan dari pendaftar baru.",
            '',
            "Nomor baru Anda: *{$nomorBaru}*",
            '',
            'Datang saja pagi ini dan lihat layar antrian di ruang tunggu. Tidak perlu daftar ulang.',
            '',
            'Salam,',
            config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama'),
        ]);
    }
}
