<?php

/*
|--------------------------------------------------------------------------
| Jam operasional poliklinik
|--------------------------------------------------------------------------
| Format jam selalu HH:MM (dua digit), misalnya 07:00 bukan 7:00.
|
|   OPERASIONAL_AKTIF  false (default) = tidak ada pembatasan jam, aman untuk
|                      pengembangan dan pengujian. Ubah ke true di server nyata.
|   PENDAFTARAN_BUKA   pendaftaran antrian dibuka mulai jam ini
|   PENDAFTARAN_TUTUP  pendaftaran baru ditutup mulai jam ini
|   LAYANAN_SELESAI    sampai jam ini pasien masih boleh dipanggil dan dipanggil ulang
*/

return [
    'aktif' => env('OPERASIONAL_AKTIF', false),

    'pendaftaran_buka' => env('PENDAFTARAN_BUKA', '07:00'),
    'pendaftaran_tutup' => env('PENDAFTARAN_TUTUP', '11:00'),
    'layanan_selesai' => env('LAYANAN_SELESAI', '14:00'),

    // Status antrian yang dipindah ke hari berikutnya saat penutupan hari.
    // Default hanya yang belum pernah terpanggil. Tambahkan 'tidak_hadir' bila ingin
    // pasien yang terlewat juga dipindah.
    'limpahan_status' => ['menunggu'],
];
