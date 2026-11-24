<?php

/*
|--------------------------------------------------------------------------
| Aturan pemanggilan antrian
|--------------------------------------------------------------------------
|   PANGGIL_OTOMATIS   true = saat dokter kosong, nomor berikutnya dipanggil otomatis.
|                      Petugas tetap bisa memanggil dan memanggil ulang secara manual.
|   BATAS_TELAT_MENIT  pasien yang dipanggil lalu tidak datang selama ini menjadi "telat".
|   BATAS_TIDAK_HADIR  pasien telat tanpa kabar selama ini menjadi "tidak hadir".
|   IKUTI_JADWAL       true = panggil otomatis hanya untuk dokter yang jadwal praktiknya
|                      hari ini sedang berjalan.
*/

return [
    'panggil_otomatis' => env('PANGGIL_OTOMATIS', true),
    'batas_telat_menit' => (int) env('BATAS_TELAT_MENIT', 5),
    'batas_tidak_hadir_menit' => (int) env('BATAS_TIDAK_HADIR_MENIT', 15),
    'ikuti_jadwal' => env('PANGGIL_IKUTI_JADWAL', true),
];
