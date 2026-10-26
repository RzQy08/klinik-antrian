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
        $judul = $panggilanKe > 1
            ? "*Panggilan ke-{$panggilanKe}.* Giliran Anda sudah tiba, silakan menuju poli sekarang."
            : '*Giliran Anda sudah tiba.* Silakan menuju poli sekarang.';

        $baris = [
            "Halo, {$namaPasien}.",
            '',
            $judul,
            '',
            "Nomor antrian: *{$nomorAntrian}*",
            "Poli: {$namaPoli}",
        ];

        if ($namaDokter) {
            $baris[] = "Dokter: {$namaDokter}";
        }

        if ($jamPanggil) {
            $baris[] = "Dipanggil pukul: {$jamPanggil}";
        }

        $baris[] = '';
        $baris[] = 'Mohon segera datang. Selama jam layanan masih berlangsung, nomor Anda masih bisa dipanggil ulang oleh petugas.';
        $baris[] = '';
        $baris[] = 'Terima kasih,';
        $baris[] = config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama');

        return implode("\n", $baris);
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
            "Halo, {$namaPasien}.",
            '',
            "Antrian Anda pada {$tanggalAsal} (nomor {$nomorLama}, {$namaPoli}) belum sempat terpanggil. Kami pindahkan ke {$tanggalBaru} dengan prioritas.",
            '',
            "Nomor antrian baru: *{$nomorBaru}*",
            "Poli: {$namaPoli}",
            '',
            'Silakan datang pagi ini dan pantau layar antrian di ruang tunggu. Anda tidak perlu mendaftar ulang.',
            '',
            'Terima kasih,',
            config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama'),
        ]);
    }
}
