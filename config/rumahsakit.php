<?php

/*
|--------------------------------------------------------------------------
| Identitas rumah sakit
|--------------------------------------------------------------------------
| Dipakai di navbar, tiket, layar TV, halaman login, dan judul tab.
| Ganti di sini, atau lewat .env (RS_NAMA, RS_NAMA_LENGKAP), lalu jalankan:
|   php artisan config:clear
*/

return [
    // Nama pendek untuk navbar dan judul tab
    'nama' => env('RS_NAMA', 'RS Bersama'),

    // Nama lengkap untuk halaman login dan footer
    'nama_lengkap' => env('RS_NAMA_LENGKAP', 'Rumah Sakit Bersama'),

    // Keterangan kecil di bawah nama
    'sub' => 'Sistem Antrian Poliklinik',
];
