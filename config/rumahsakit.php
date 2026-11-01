<?php

/*
|--------------------------------------------------------------------------
| Identitas rumah sakit
|--------------------------------------------------------------------------
| Dipakai di navbar, tiket, layar TV, halaman login, judul tab, dan footer.
| Ganti lewat .env, lalu jalankan: php artisan config:clear
|
| Data kontak bersifat opsional. Yang dikosongkan tidak ditampilkan di footer,
| jadi tidak akan ada baris kosong atau data palsu.
*/

return [
    // Nama pendek untuk navbar dan judul tab
    'nama' => env('RS_NAMA', 'RS Bersama'),

    // Nama lengkap untuk halaman login dan footer
    'nama_lengkap' => env('RS_NAMA_LENGKAP', 'Rumah Sakit Bersama'),

    // Keterangan kecil di bawah nama
    'sub' => 'Sistem Antrian Poliklinik',

    // ---- Kontak (opsional) ----
    'alamat' => env('RS_ALAMAT'),               // contoh: Jl. Melati No. 12, Bogor, Jawa Barat
    'telepon' => env('RS_TELEPON'),             // contoh: (0251) 123456
    'whatsapp' => env('RS_WHATSAPP'),           // contoh: 081234567890
    'email' => env('RS_EMAIL'),                 // contoh: info@rsbersama.id
    'jam_layanan' => env('RS_JAM_LAYANAN'),     // contoh: Senin sampai Sabtu, 07.00 sampai 14.00 WIB

    // ---- Media sosial (opsional, isi alamat lengkapnya) ----
    'instagram' => env('RS_INSTAGRAM'),
    'facebook' => env('RS_FACEBOOK'),
    'maps' => env('RS_MAPS'),                   // tautan Google Maps
];
