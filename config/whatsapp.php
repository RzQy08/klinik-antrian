<?php

/*
|--------------------------------------------------------------------------
| Notifikasi WhatsApp
|--------------------------------------------------------------------------
| Semua nilai diatur lewat file .env supaya token tidak tersimpan di kode.
|
|   WA_DRIVER       log (default, hanya menulis ke storage/logs/laravel.log)
|                   atau fonnte (benar-benar mengirim lewat Fonnte)
|   WA_FONNTE_TOKEN token perangkat dari dashboard Fonnte
|   WA_REDIRECT_KE  kalau diisi, SEMUA pesan dialihkan ke nomor ini. Aman untuk
|                   pengujian karena pasien dummy tidak ikut menerima pesan.
|   WA_NOMOR_UJI    nomor tujuan default untuk php artisan wa:uji
*/

return [
    'driver' => env('WA_DRIVER', 'log'),

    'fonnte_token' => env('WA_FONNTE_TOKEN'),

    'redirect_ke' => env('WA_REDIRECT_KE'),

    'nomor_uji' => env('WA_NOMOR_UJI'),
];
