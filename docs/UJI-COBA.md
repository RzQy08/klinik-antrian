# Panduan Uji Coba — Klinik Antrian

Dokumen ini menjelaskan cara memakai data uji yang sudah disiapkan, apa yang harus dicek, dan hasil pengujian otomatis yang sudah dijalankan.

> Semua file di bawah adalah **file baru**. File project yang sudah ada tidak diubah.

## 1. File yang ditambahkan

| File | Fungsi |
|---|---|
| `database/seeders/DataUjiSeeder.php` | Membuat akun uji, 12 pasien uji, antrian hari ini dengan semua status, dan riwayat 14 hari. |
| `database/seeders/HapusDataUjiSeeder.php` | Menghapus hanya data yang dibuat `DataUjiSeeder`. |
| `tests/Feature/DataUjiSeederTest.php` | Menguji isi seeder, idempotensi, kestabilan queue theory, dan pembersih. |
| `tests/Feature/UjiCobaAlurTest.php` | Uji alur utama: login, ambil nomor, panggil, layani, selesai, tidak hadir, panggil ulang, analitik, riwayat, CSV. |
| `docs/DATA-UJI-MANUAL.md` | Data contoh untuk Anda input sendiri lewat tampilan aplikasi (bukan seeder). |
| `docs/data-uji/*.csv` | Versi CSV dari data manual, enak dibuka di Excel. |

## 2. Cara cepat menjalankan

```bash
# 1. Pastikan database sudah dimigrasi dan punya data master
php artisan migrate
php artisan db:seed

# 2. Masukkan data uji (aman dijalankan berulang)
php artisan db:seed --class=DataUjiSeeder

# 3. Jalankan test otomatis khusus data uji + alur
php artisan test tests/Feature/DataUjiSeederTest.php tests/Feature/UjiCobaAlurTest.php
```

Kalau database masih kosong, `php artisan db:seed` akan membuat poli, dokter, dan jadwal bawaan. `DataUjiSeeder` juga otomatis memanggil seeder master itu kalau datanya belum ada.

## 3. Akun uji

Semua password hanya untuk lingkungan lokal.

| Peran | Email | Password |
|---|---|---|
| Admin | `admin.uji@klinik.test` | `AdminUji#2026` |
| Petugas | `petugas.uji@klinik.test` | `PetugasUji#2026` |
| Dokter Poli Umum | `dr-andi-wijaya.uji@klinik.test` | `DokterUji#2026` |
| Dokter Poli Umum | `dr-siti-rahma.uji@klinik.test` | `DokterUji#2026` |
| Dokter Poli Gigi | `drg-budi-santoso.uji@klinik.test` | `DokterUji#2026` |
| Dokter Poli Anak | `dr-citra-dewi-spa.uji@klinik.test` | `DokterUji#2026` |
| Dokter Poli KIA | `dr-dewi-lestari-spog.uji@klinik.test` | `DokterUji#2026` |
| Dokter/Bidan Poli KIA | `bidan-fitri.uji@klinik.test` | `DokterUji#2026` |

Email dokter dibuat dari nama dokter yang ada di database. Kalau Anda menambah dokter lain, jalankan seeder lagi atau cek tabel yang dicetak di terminal.

## 4. Data yang dibuat

### 4.1 Pasien uji

| No. RM | Nama | NIK | No. HP |
|---|---|---|---|
| RM-UJI-001 | Raka Pratama | 3273010101900001 | 081230000001 |
| RM-UJI-002 | Sinta Maharani | 3273015206850002 | 081230000002 |
| RM-UJI-003 | Ahmad Fauzan | 3273010108010003 | 081230000003 |
| RM-UJI-004 | Dewi Lestari | 3273014412780004 | 081230000004 |
| RM-UJI-005 | Rizky Pratama | 3273012009150005 | 081230000005 |
| RM-UJI-006 | Aminah | 3273011010600006 | 081230000006 |
| RM-UJI-007 | Andi Saputra | 3273010505950007 | 081230000007 |
| RM-UJI-008 | Maya Anggraini | 3273012807880008 | 081230000008 |
| RM-UJI-009 | Bagas Nugroho | 3273011204020009 | 081230000009 |
| RM-UJI-010 | Nadia Putri | 3273016709990010 | 081230000010 |
| RM-UJI-011 | Fajar Hidayat | 3273011507930011 | 081230000011 |
| RM-UJI-012 | Kartini | 3273012103660012 | 081230000012 |

### 4.2 Antrian hari ini (database baru)

Nomor bisa bergeser kalau database sudah punya antrian hari ini. Nomor yang sebenarnya selalu dicetak di terminal setelah seeder selesai.

| Poli | Nomor | Status | Pasien | Dokter |
|---|---|---|---|---|
| Poli Umum | A001 | selesai | Raka Pratama | dr. Andi Wijaya |
| Poli Umum | A002 | selesai | Sinta Maharani | dr. Siti Rahma |
| Poli Umum | A003 | dilayani | Ahmad Fauzan | dr. Andi Wijaya |
| Poli Umum | A004 | dipanggil | Dewi Lestari | dr. Siti Rahma |
| Poli Umum | A005 | tidak_hadir | Rizky Pratama | dr. Andi Wijaya |
| Poli Umum | A006 | batal | Aminah | - |
| Poli Umum | A007 | menunggu | Andi Saputra | - |
| Poli Umum | A008 | menunggu | Maya Anggraini | - |
| Poli Umum | A009 | menunggu | Bagas Nugroho | - |
| Poli Umum | A010 | menunggu (limpahan) | Kartini | - |
| Poli Gigi | B001 | selesai | Nadia Putri | drg. Budi Santoso |
| Poli Gigi | B002 | menunggu | Fajar Hidayat | - |
| Poli Gigi | B003 | menunggu | Kartini | - |
| Poli Anak | C001 | dipanggil | Raka Pratama | dr. Citra Dewi, Sp.A |
| Poli Anak | C002 | menunggu | Sinta Maharani | - |
| Poli Anak | C003 | menunggu | Ahmad Fauzan | - |
| Poli KIA | D001 | selesai | Dewi Lestari | dr. Dewi Lestari, Sp.OG |
| Poli KIA | D002 | dilayani | Rizky Pratama | Bidan Fitri |
| Poli KIA | D003 | menunggu | Aminah | - |

Catatan penting:

- **A010 adalah limpahan dari kemarin.** Walaupun nomornya paling besar, `waktu_daftar`-nya dibuat pukul 00.00.02 sehingga harus muncul dan dipanggil paling depan.
- A005 berstatus `tidak_hadir` dan bisa dicoba tombol **Panggil ulang**.
- A006 berstatus `batal` untuk menguji filter riwayat.
- A003 dan A004 bisa dicoba tombol **Mulai layani** dan **Selesai**.
- Poli Umum punya 2 dokter (c = 2), Poli Gigi/Anak punya 1 dokter (c = 1), Poli KIA punya 2 tenaga.

### 4.3 Riwayat simulasi

- 14 hari terakhir sampai kemarin.
- Hari Minggu dilewati.
- 1.440 baris antrian selesai (48 Poli Umum + 16 Poli Gigi + 24 Poli Anak + 32 Poli KIA per hari kerja).
- Dipakai halaman **Analitik** dan **Riwayat**.

Contoh hasil `queue-metrics:generate` pada data uji:

| Poli | Sampel | rho | Status |
|---|---:|---:|---|
| Poli Umum | 48 | 0,598 | Stabil |
| Poli Gigi | 16 | 0,662 | Stabil |
| Poli Anak | 24 | 0,750 | Stabil |
| Poli KIA | 32 | 0,499 | Stabil |

## 5. Skenario uji manual

Centang satu per satu. Kalau ada yang tidak sesuai, catat langkahnya.

### 5.1 Halaman publik

- [ ] Beranda terbuka dan panel antrian menampilkan nomor yang sedang dipanggil/dilayani.
- [ ] `/antrian/ambil` terbuka tanpa login.
- [ ] Ambil nomor dengan NIK `3273010101900001` (Raka) di **Poli Gigi** → tiket baru B004 (atau nomor berikutnya), nama dan No. RM `RM-UJI-001` dikenali otomatis.
- [ ] Ulangi NIK dan poli yang sama → diarahkan ke tiket B004 yang sama, bukan membuat nomor baru.
- [ ] Ambil nomor NIK yang sama di poli berbeda → boleh, dapat nomor baru.
- [ ] Isi NIK 15 digit → muncul error “NIK harus terdiri dari 16 digit angka.” dan tidak ada tiket baru.
- [ ] Halaman tiket menampilkan nomor, jumlah orang di depan, dan perkiraan tunggu.
- [ ] `/antrian/layar` menampilkan papan semua poli.
- [ ] `/antrian/display/{poli}` menampilkan layar satu poli.

### 5.2 Login dan peran

- [ ] Login admin → diarahkan ke dashboard admin.
- [ ] Login petugas → diarahkan ke antrian hari ini.
- [ ] Login dokter → diarahkan ke “Pasien saya”.
- [ ] Dokter membuka `/admin/dashboard` → 403.
- [ ] Petugas membuka `/admin/dashboard` → 403.

### 5.3 Petugas

- [ ] Halaman **Antrian hari ini** menampilkan ringkasan jumlah per poli.
- [ ] Buka **Kelola** Poli Umum.
- [ ] Urutan menunggu: **A010 (limpahan) paling atas**, lalu A007, A008, A009.
- [ ] Klik **Panggil berikutnya** dengan dr. Andi → A010 berubah menjadi `dipanggil`.
- [ ] Buka akun dr. Andi → pasien A010 muncul di “Pasien saya”.
- [ ] Klik **Mulai layani** → status `dilayani`.
- [ ] Klik **Selesai** → status `selesai` dan waktu selesai terisi.
- [ ] Panggil A007, lalu klik **Tidak hadir** → status `tidak_hadir`.
- [ ] Klik **Panggil ulang** pada A005 atau A007 → status kembali `dipanggil`, jumlah panggil bertambah.
- [ ] Halaman **Analitik** menampilkan 4 poli stabil dan perbandingan Wq teori vs aktual.
- [ ] Halaman **Riwayat** menampilkan data 7 hari terakhir; filter status, poli, dan pencarian nama/NIK bekerja.
- [ ] Tombol **Unduh CSV** menghasilkan file CSV yang bisa dibuka di Excel.

### 5.4 Dokter

- [ ] Login `dr-andi-wijaya.uji@klinik.test`.
- [ ] Hanya pasien yang ditugaskan ke dr. Andi yang terlihat.
- [ ] Mulai dan selesaikan pelayanan dari dashboard dokter.
- [ ] Coba akses pasien milik dokter lain → 403 (bisa diuji lewat request langsung atau data A004 milik dr. Siti).

### 5.5 Admin

- [ ] Dashboard admin menampilkan jumlah poli, dokter, user, dan antrian hari ini.
- [ ] Tambah poli baru, lalu aktif/nonaktifkan.
- [ ] Tambah dokter baru beserta akun login, lalu coba login.
- [ ] Atur jadwal praktik dokter (Senin–Sabtu) dan simpan.
- [ ] Coba hapus poli yang sudah punya riwayat → ditolak dengan pesan yang jelas.
- [ ] Coba hapus dokter yang sudah punya riwayat → ditolak.

### 5.6 Perintah artisan

```bash
# Metrik Queue Theory untuk kemarin
php artisan queue-metrics:generate

# Pindahkan sisa antrian kemarin ke hari ini (data uji: harus 0 karena sumber sudah dialihkan)
php artisan antrian:limpahkan --tanpa-wa

# Uji koneksi WhatsApp; sebaiknya ubah WA_DRIVER=log dulu supaya tidak mengirim pesan sungguhan
php artisan wa:uji
```

> `antrian:limpahkan` tanpa argumen memindahkan antrian **kemarin** ke **hari ini**. Untuk mencoba perpindahan hari ini ke besok, gunakan salinan database atau jalankan dengan tanggal eksplisit:
>
> ```bash
> php artisan antrian:limpahkan 2026-11-04 --ke=2026-11-05 --tanpa-wa
> ```

## 6. Hasil test otomatis

Sudah dijalankan pada project ini:

```text
php artisan test
150 tests, 150 passed, 505 assertions
```

Rincian file baru:

```text
php artisan test tests/Feature/DataUjiSeederTest.php tests/Feature/UjiCobaAlurTest.php
6 tests, 6 passed, 99 assertions
```

Yang diuji:

1. Akun admin, petugas, dan dokter terbentuk dan terhubung ke data dokter.
2. 12 pasien uji terbentuk.
3. Semua status antrian ada: menunggu, dipanggil, dilayani, selesai, tidak_hadir, batal.
4. Limpahan dari kemarin dibuat dan dikenali.
5. Riwayat 14 hari terbentuk, hari Minggu tidak ikut.
6. Seeder dijalankan dua kali tidak menggandakan data.
7. Perhitungan queue theory untuk semua poli stabil (rho < 1).
8. Pembersih hanya menghapus data uji.
9. Login dan semua halaman utama per peran bisa dibuka.
10. Alur ambil nomor → panggil → mulai layani → selesai → tidak hadir → panggil ulang berjalan.
11. Analitik, riwayat, dan CSV bisa dibuka setelah alur berjalan.

Uji tambahan yang sudah dijalankan pada database SQLite sementara:

- `php artisan migrate:fresh` berhasil.
- `DataUjiSeeder` berhasil dan bisa dijalankan dua kali.
- `queue-metrics:generate` berhasil untuk 4 poli.
- `antrian:limpahkan --tanpa-wa` berhasil (0 antrian dipindahkan, sesuai kondisi data uji).
- Halaman `/`, `/antrian/ambil`, `/antrian/layar`, `/antrian/hari-ini`, dan `/login` merespons HTTP 200.
- `HapusDataUjiSeeder` menghapus 8 akun, 12 pasien, dan 1.460 antrian, tanpa menyentuh poli/dokter.

## 7. Membersihkan data uji

```bash
php artisan db:seed --class=HapusDataUjiSeeder
```

Yang dihapus hanya:

- pasien dengan No. RM berawalan `RM-UJI-`;
- akun dengan email berakhiran `.uji@klinik.test`;
- seluruh antrian milik pasien uji tersebut.

Poli, dokter, jadwal, dan data pengguna lain tidak dihapus.

## 8. Catatan dan troubleshooting

- **Database MySQL**: project ini memakai MySQL di `.env`. Seeder berjalan di MySQL maupun SQLite.
- **Nomor antrian bergeser**: kalau sudah ada antrian hari ini, nomor data uji melanjutkan nomor yang ada. Lihat tabel yang dicetak seeder.
- **Seeder mengulang data uji**: antrian milik pasien uji untuk hari ini dan 14 hari terakhir dihapus lalu dibuat ulang agar statusnya konsisten. Data pengguna lain tidak disentuh.
- **WhatsApp**: `.env` contoh memakai `WA_DRIVER=fonnte` dan `WA_REDIRECT_KE`. Untuk uji aman, set `WA_DRIVER=log` lalu `php artisan config:clear`.
- **Jam operasional**: `OPERASIONAL_AKTIF=false` membuat pendaftaran dan pemanggilan bisa dilakukan kapan saja. Ini memudahkan uji di luar jam kerja.
- **Cache**: setelah mengubah `.env`, jalankan `php artisan config:clear`.
- **Queue**: notifikasi WhatsApp memakai queue. Untuk melihat prosesnya, jalankan `php artisan queue:work` atau `composer dev`.
- **Reset total**: kalau ingin database benar-benar bersih, `php artisan migrate:fresh --seed` (menghapus semua data). Jangan jalankan di database yang berisi data penting.
