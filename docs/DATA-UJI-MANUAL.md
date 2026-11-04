# Data Uji Manual — Klinik Antrian

Dokumen ini berisi contoh data yang **Anda input sendiri lewat tampilan aplikasi**. Ini bukan seeder: tidak ada perintah `db:seed` untuk data di bawah.

> Semua data hanya untuk pengujian lokal. Jangan memakai NIK, email, atau password contoh di server produksi.
>
> File project yang sudah ada tidak diubah. Dokumen ini dan file CSV di `docs/data-uji/` adalah file baru.

## 1. Cocok untuk apa?

| Cocok untuk | Kurang cocok untuk |
|---|---|
| Mencoba tambah/ubah poli, dokter, dan jadwal dari menu admin | Mengisi analitik dengan riwayat 14 hari |
| Mencoba alur pendaftaran pasien baru dan pasien lama | Uji beban banyak antrian sekaligus |
| Mencoba seluruh tombol petugas: panggil, mulai, selesai, tidak hadir, panggil ulang | Menguji data limpahan tanpa menunggu pergantian hari |
| Mencoba login dokter dan pembatasan pasien per dokter | Menguji status `batal` (tidak ada tombol batal di aplikasi) |
| Mencoba filter riwayat dan unduh CSV | — |

Untuk analitik dan riwayat yang langsung penuh, pakai `database/seeders/DataUjiSeeder.php` seperti dijelaskan di `docs/UJI-COBA.md`.

## 2. Sebelum mulai

1. Pastikan aplikasi berjalan dan Anda bisa login sebagai **admin**.
2. Kalau belum punya akun admin/petugas, jalankan `php artisan db:seed --class=DataUjiSeeder` sekali, lalu login dengan `admin.uji@klinik.test` / `AdminUji#2026`. Data manual ini tetap bisa ditambahkan di atasnya.
3. Untuk pengujian di luar jam kerja, pastikan `.env` berisi:

   ```env
   OPERASIONAL_AKTIF=false
   WA_DRIVER=log
   ```

   Lalu jalankan `php artisan config:clear`.
4. Catat bahwa **nomor tiket di dokumen ini adalah perkiraan**. Kalau poli M/N/F sudah punya antrian hari ini, nomor akan melanjutkan nomor yang ada. Nomor sebenarnya selalu terlihat di tiket setelah mendaftar.

Kode poli `M`, `N`, `F`, `K` dan NIK berawalan `327310` sengaja dipilih supaya tidak bentrok dengan data demo (`A`–`D`, NIK `320101`) atau data uji seeder (`RM-UJI-`, NIK `327301`).

## 3. Ringkasan data

| Data | Jumlah | Keterangan |
|---|---:|---|
| Poli | 4 | 3 aktif + 1 nonaktif |
| Dokter | 4 | 3 aktif + 1 nonaktif |
| Akun dokter | 2 | Maya dan Nanda |
| Jadwal praktik | 11 baris | Tersebar Senin–Sabtu |
| Pasien | 10 | Dibuat otomatis saat ambil nomor |
| Tiket antrian | 10 | 4 Poli Mata, 4 Poli Saraf, 2 Poli Fisioterapi |

Peta poli dan dokter:

| Poli | Kode | Dokter aktif | Akun login dokter |
|---|---|---|---|
| Poli Mata | `M` | dr. Maya Kusuma, Sp.M | `maya.manual@klinik.test` |
| Poli Saraf | `N` | dr. Nanda Pratama, Sp.S | `nanda.manual@klinik.test` |
| Poli Fisioterapi | `F` | dr. Fajar Ramadhan | Tidak ada (dikelola petugas) |
| Poli Kulit | `K` | — | Poli nonaktif |

## 4. Input poli

**Langkah:** login admin → menu **Poli** → **Tambah poli** → isi → **Simpan poli**.

| No | Nama poli | Kode poli | Aktif? | Catatan |
|---:|---|---|---|---|
| 1 | Poli Mata | `M` | Ya | Poli utama skenario |
| 2 | Poli Saraf | `N` | Ya | Dokter punya akun login |
| 3 | Poli Fisioterapi | `F` | Ya | Dokter tanpa akun login |
| 4 | Poli Kulit | `K` | **Tidak** | Untuk menguji poli nonaktif |

Untuk Poli Kulit, hilangkan centang **“Aktif, bisa dipilih pasien saat ambil antrian”** sebelum menyimpan.

Yang perlu dicek:

- [ ] Daftar poli menampilkan 4 kartu: 3 aktif, 1 nonaktif.
- [ ] Poli Kulit **tidak muncul** di halaman `/antrian/ambil`.
- [ ] Poli Kulit masih terlihat di menu admin (supaya bisa diaktifkan lagi).

## 5. Input dokter

**Langkah:** menu **Dokter** → **Tambah dokter** → pilih poli → isi nama dan No. SIP → atur status → simpan.

| No | Nama dokter | Poli | No. SIP | Aktif? | Buat akun? | Email | Password |
|---:|---|---|---|---|---|---|---|
| 1 | dr. Maya Kusuma, Sp.M | `M` | `SIP-MANUAL-001` | Ya | **Ya** | `maya.manual@klinik.test` | `MayaManual#2026` |
| 2 | dr. Nanda Pratama, Sp.S | `N` | `SIP-MANUAL-002` | Ya | **Ya** | `nanda.manual@klinik.test` | `NandaManual#2026` |
| 3 | dr. Fajar Ramadhan | `F` | `SIP-MANUAL-003` | Ya | Tidak | — | — |
| 4 | drg. Rina Marlina | `M` | `SIP-MANUAL-004` | **Tidak** | Tidak | — | — |

Catatan:

- Untuk Maya dan Nanda, centang **“Buatkan akun login untuk dokter ini”**, lalu isi email dan password.
- Untuk Fajar, biarkan centang akun **tidak dicentang**. Petugas tetap bisa memilih Fajar saat memanggil pasien.
- Untuk Rina, hilangkan centang **“Aktif bertugas”**. Rina hanya untuk menguji bahwa dokter nonaktif tidak muncul di pilihan pemanggilan.

Yang perlu dicek:

- [ ] Daftar dokter menampilkan email Maya dan Nanda.
- [ ] Fajar tampil dengan keterangan **“Belum ada”** pada kolom akun.
- [ ] Rina tampil nonaktif.
- [ ] Logout, lalu login `maya.manual@klinik.test` → masuk ke halaman **Pasien saya**.
- [ ] Logout, lalu login `nanda.manual@klinik.test` → masuk ke halaman **Pasien saya**.

## 6. Input jadwal praktik

**Langkah:** menu **Dokter** → klik **“Atur jadwal”** (atau lingkaran hari) pada dokter → aktifkan hari → isi jam dan kuota → **Simpan jadwal**.

| No | Dokter | Poli | Hari | Jam mulai | Jam selesai | Kuota |
|---:|---|---|---|---|---|---:|
| 1 | dr. Maya Kusuma, Sp.M | `M` | Senin | 08:00 | 12:00 | 24 |
| 2 | dr. Maya Kusuma, Sp.M | `M` | Rabu | 08:00 | 12:00 | 24 |
| 3 | dr. Maya Kusuma, Sp.M | `M` | Jumat | 13:00 | 16:00 | 20 |
| 4 | dr. Nanda Pratama, Sp.S | `N` | Selasa | 08:00 | 12:00 | 20 |
| 5 | dr. Nanda Pratama, Sp.S | `N` | Kamis | 08:00 | 12:00 | 20 |
| 6 | dr. Nanda Pratama, Sp.S | `N` | Sabtu | 08:00 | 11:00 | 16 |
| 7 | dr. Fajar Ramadhan | `F` | Senin | 14:00 | 17:00 | 16 |
| 8 | dr. Fajar Ramadhan | `F` | Selasa | 14:00 | 17:00 | 16 |
| 9 | dr. Fajar Ramadhan | `F` | Rabu | 14:00 | 17:00 | 16 |
| 10 | dr. Fajar Ramadhan | `F` | Kamis | 14:00 | 17:00 | 16 |
| 11 | dr. Fajar Ramadhan | `F` | Jumat | 14:00 | 17:00 | 16 |

Tips untuk Fajar: klik **“Senin sampai Jumat”**, lalu samakan jam `14:00`–`17:00` dan kuota `16`, kemudian simpan.

Catatan:

- Satu dokter hanya boleh punya satu jadwal per hari.
- Jadwal memengaruhi tampilan “Dokter praktik hari ini” di beranda dan kuota di dashboard dokter. Pendaftaran antrian **tidak** ditolak hanya karena dokter tidak punya jadwal hari itu.
- Kalau hari ini bukan jadwal Maya/Nanda, skenario antrian tetap bisa dijalankan.

## 7. Input pasien dan ambil tiket

Aplikasi **tidak punya menu tambah pasien**. Pasien otomatis dibuat saat pertama kali mengambil nomor di `/antrian/ambil`. Jadi tabel pasien di bawah sekaligus menjadi urutan pendaftaran.

**Langkah untuk setiap pasien:**

1. Buka `/antrian/ambil` (atau klik **Halaman pasien** di menu atas).
2. Pilih poli sesuai tabel.
3. Isi NIK, nama lengkap, tanggal lahir, jenis kelamin, dan No. WhatsApp.
4. Klik **Ambil nomor antrian**.
5. Catat nomor tiket yang muncul.
6. Buka kembali `/antrian/ambil` untuk pasien berikutnya.

Contoh pengisian pasien pertama:

| Kolom | Nilai |
|---|---|
| Poli | Poli Mata (`M`) |
| NIK | `3273101403920001` |
| Nama lengkap | Aditya Nugraha |
| Tanggal lahir | `1992-03-14` |
| Jenis kelamin | Laki-laki |
| No. WhatsApp | `081240000001` |

Tabel pasien (daftarkan **berurutan**):

| Urutan | Nama | NIK | Tanggal lahir | JK | No. HP | Alamat | Poli | Perkiraan tiket | Status akhir yang dituju |
|---:|---|---|---|---|---|---|---|---|---|
| 1 | Aditya Nugraha | `3273101403920001` | 1992-03-14 | L | `081240000001` | Jl. Buah Batu No. 12, Bandung | `M` | `M001` | `selesai` |
| 2 | Bunga Permatasari | `3273106107950002` | 1995-07-21 | P | `081240000002` | Jl. Kiaracondong No. 45, Bandung | `M` | `M002` | `selesai` (lewat tidak hadir + panggil ulang) |
| 3 | Candra Wijaya | `3273100211880003` | 1988-11-02 | L | `081240000003` | Jl. Sukarno-Hatta No. 88, Bandung | `M` | `M003` | `dipanggil` |
| 4 | Dinda Maharani | `3273107001000004` | 2000-01-30 | P | `081240000004` | Jl. Riau No. 21, Bandung | `N` | `N001` | `dilayani` |
| 5 | Eko Prasetyo | `3273100905790005` | 1979-05-09 | L | `081240000005` | Jl. Cikutra No. 7, Bandung | `N` | `N002` | `tidak_hadir` |
| 6 | Fitri Handayani | `3273105712980006` | 1998-12-17 | P | `081240000006` | Jl. Pahlawan No. 33, Bandung | `N` | `N003` | `menunggu` |
| 7 | Galih Ramadhan | `3273102508030007` | 2003-08-25 | L | `081240000007` | Jl. Dipatiukur No. 56, Bandung | `F` | `F001` | `selesai` |
| 8 | Hana Safitri | `3273104506900008` | 1990-06-05 | P | `081240000008` | Jl. Gatot Subroto No. 19, Bandung | `F` | `F002` | `menunggu` |
| 9 | Irfan Maulana | `3273101209850009` | 1985-09-12 | L | `081240000009` | Jl. Soekarno-Hatta No. 101, Bandung | `M` | `M004` | `menunggu` |
| 10 | Jihan Aulia | `3273106804010010` | 2001-04-28 | P | `081240000010` | Jl. Antapani No. 14, Bandung | `N` | `N004` | `menunggu` |

Nomor RM dibuat otomatis dengan pola `RM-yymmdd-xxxx`. Nomor RM bisa dilihat di dashboard dokter saat pasien dipanggil.

Sebelum menjalankan skenario, coba uji pasien lama:

- [ ] Daftarkan lagi Aditya (`3273101403920001`) di **Poli Mata**. Karena M001 masih aktif (`menunggu`), aplikasi harus mengembalikan tiket **M001** yang sama, bukan membuat nomor baru.
- [ ] Daftarkan NIK 15 digit (misalnya `327310140392001`) di poli mana pun. Harus muncul pesan bahwa NIK harus 16 digit, dan tidak ada tiket baru.

## 8. Jalankan skenario antrian

Buka menu **Antrian hari ini** → **Kelola** pada poli yang dituju. Pilih dokter di dropdown, lalu jalankan aksi di bawah ini **sesuai urutan**.

Status yang dipakai:

| Status | Arti |
|---|---|
| `menunggu` | Sudah daftar, belum dipanggil |
| `dipanggil` | Sedang dipanggil, belum mulai dilayani |
| `dilayani` | Sedang dilayani dokter |
| `selesai` | Pelayanan selesai |
| `tidak_hadir` | Dipanggil tetapi tidak datang |
| `batal` | Dibatalkan (hanya ada di data seeder, tidak ada tombolnya di aplikasi) |

### 8.1 Poli Mata (`M`) — dokter Maya

| Langkah | Tiket | Aksi | Status setelah aksi | Catatan |
|---:|---|---|---|---|
| 1 | `M001` | Klik **Panggil M001** | `dipanggil` | Pilih dr. Maya Kusuma |
| 2 | `M001` | Klik **Mulai layani** | `dilayani` | — |
| 3 | `M001` | Klik **Selesai** | `selesai` | — |
| 4 | `M002` | Klik **Panggil M002** | `dipanggil` | Pilih dr. Maya Kusuma |
| 5 | `M002` | Klik **Tidak hadir** | `tidak_hadir` | — |
| 6 | `M003` | Klik **Panggil M003** | `dipanggil` | **Biarkan di status ini** |
| 7 | `M002` | Klik **Panggil ulang** pada bagian Tidak hadir | `dipanggil` | Jumlah panggil menjadi 2 |
| 8 | `M002` | Klik **Mulai layani** | `dilayani` | — |
| 9 | `M002` | Klik **Selesai** | `selesai` | — |

Hasil akhir Poli Mata:

| Tiket | Pasien | Status |
|---|---|---|
| `M001` | Aditya Nugraha | `selesai` |
| `M002` | Bunga Permatasari | `selesai` |
| `M003` | Candra Wijaya | `dipanggil` |
| `M004` | Irfan Maulana | `menunggu` |

### 8.2 Poli Saraf (`N`) — dokter Nanda

| Langkah | Tiket | Aksi | Status setelah aksi | Catatan |
|---:|---|---|---|---|
| 1 | `N001` | Klik **Panggil N001** | `dipanggil` | Pilih dr. Nanda Pratama |
| 2 | `N001` | Klik **Mulai layani** | `dilayani` | **Biarkan di status ini** |
| 3 | `N002` | Klik **Panggil N002** | `dipanggil` | Dinda yang sedang dilayani tidak menghalangi panggilan berikutnya |
| 4 | `N002` | Klik **Tidak hadir** | `tidak_hadir` | **Biarkan di status ini** |

Hasil akhir Poli Saraf:

| Tiket | Pasien | Status |
|---|---|---|
| `N001` | Dinda Maharani | `dilayani` |
| `N002` | Eko Prasetyo | `tidak_hadir` |
| `N003` | Fitri Handayani | `menunggu` |
| `N004` | Jihan Aulia | `menunggu` |

### 8.3 Poli Fisioterapi (`F`) — dokter Fajar

| Langkah | Tiket | Aksi | Status setelah aksi | Catatan |
|---:|---|---|---|---|
| 1 | `F001` | Klik **Panggil F001** | `dipanggil` | Pilih dr. Fajar Ramadhan |
| 2 | `F001` | Klik **Mulai layani** | `dilayani` | — |
| 3 | `F001` | Klik **Selesai** | `selesai` | — |

Hasil akhir Poli Fisioterapi:

| Tiket | Pasien | Status |
|---|---|---|
| `F001` | Galih Ramadhan | `selesai` |
| `F002` | Hana Safitri | `menunggu` |

### 8.4 Rekap status akhir

| Poli | `menunggu` | `dipanggil` | `dilayani` | `selesai` | `tidak_hadir` |
|---|---:|---:|---:|---:|---:|
| Poli Mata | 1 (M004) | 1 (M003) | 0 | 2 (M001, M002) | 0 |
| Poli Saraf | 2 (N003, N004) | 0 | 1 (N001) | 0 | 1 (N002) |
| Poli Fisioterapi | 1 (F002) | 0 | 0 | 1 (F001) | 0 |
| **Total** | **4** | **1** | **1** | **3** | **1** |

## 9. Yang perlu dicek setelah skenario

### 9.1 Halaman publik

- [ ] Beranda menampilkan ringkasan antrian hari ini.
- [ ] `/antrian/ambil` hanya menampilkan poli aktif (M, N, F), bukan Poli Kulit (K).
- [ ] Tiket menampilkan nomor, posisi orang di depan, dan perkiraan tunggu.
- [ ] `/antrian/layar` menampilkan papan semua poli.
- [ ] `/antrian/display/{poli}` menampilkan layar satu poli.

### 9.2 Petugas

- [ ] Ringkasan per poli menunjukkan jumlah menunggu/dipanggil/selesai yang sesuai rekap di atas.
- [ ] Urutan menunggu Poli Mata: M004 saja (M001–M003 sudah tidak menunggu).
- [ ] Urutan menunggu Poli Saraf: N003, N004.
- [ ] Bagian Tidak hadir Poli Saraf menampilkan N002 dan tombol **Panggil ulang**.
- [ ] Bagian Sedang ditangani Poli Saraf menampilkan N001 berstatus `dilayani`.
- [ ] Halaman **Riwayat** menampilkan 10 antrian manual hari ini.
- [ ] Filter status `dipanggil` menampilkan M003.
- [ ] Filter status `dilayani` menampilkan N001.
- [ ] Filter status `selesai` menampilkan M001, M002, F001.
- [ ] Filter status `tidak_hadir` menampilkan N002.
- [ ] Filter poli Poli Mata hanya menampilkan M001–M004.
- [ ] Pencarian nama `Bunga` dan NIK `327310` bekerja.
- [ ] Tombol **Unduh CSV** menghasilkan file yang bisa dibuka di Excel.

### 9.3 Dokter

- [ ] Login Maya → halaman **Pasien saya** menampilkan Candra (M003, `dipanggil`) dan riwayat selesai M001/M002.
- [ ] Login Nanda → halaman **Pasien saya** menampilkan Dinda (N001, `dilayani`), dengan angka **tidak hadir = 1** untuk Eko (N002).
- [ ] Fajar tidak punya akun, jadi tidak bisa login (ini memang disengaja).
- [ ] Maya tidak bisa melihat atau mengubah pasien milik Nanda.

### 9.4 Admin

- [ ] Dashboard admin menampilkan jumlah poli, dokter, dan antrian hari ini.
- [ ] Poli Kulit masih bisa diaktifkan/nonaktifkan.
- [ ] Rina masih bisa diaktifkan kalau ingin menguji tambahan dokter Poli Mata.
- [ ] Coba hapus Poli Mata yang sudah punya riwayat → tombol hapus terkunci.
- [ ] Coba hapus dokter Maya yang sudah punya riwayat → tombol hapus terkunci.

### 9.5 Analitik

Halaman **Analitik** memakai rentang yang berakhir **kemarin** secara default. Karena data manual ini baru dibuat hari ini, halaman analitik akan kosong atau belum menghitungnya.

- [ ] Buka analitik hari ini → wajar jika belum ada data.
- [ ] Besok, buka analitik dengan rentang yang mencakup hari ini → data mulai terlihat.
- [ ] Jumlah sampelnya kecil (3 selesai), jadi hasil teori bisa tidak stabil. Untuk analitik lengkap, gunakan `DataUjiSeeder`.

## 10. Uji tambahan (opsional)

### 10.1 Poli dan dokter nonaktif

- [ ] Nonaktifkan Poli Saraf lewat menu admin → Poli Saraf hilang dari form ambil antrian, tetapi antrian lama tetap ada di riwayat.
- [ ] Aktifkan lagi Poli Saraf.
- [ ] Biarkan Rina nonaktif, lalu pastikan Rina tidak muncul di dropdown **Dokter yang bertugas**.

### 10.2 Validasi form

- [ ] Tambah poli dengan kode yang sudah dipakai (misalnya `M`) → ditolak.
- [ ] Tambah poli dengan kode lebih dari 5 karakter → ditolak.
- [ ] Tambah jadwal dengan jam selesai lebih awal dari jam mulai → ditolak.
- [ ] Tambah jadwal kedua di hari yang sama untuk dokter yang sama → ditolak.

### 10.3 Limpahan ke hari berikutnya

Skenario ini sengaja meninggalkan beberapa pasien `menunggu` (M004, N003, N004, F002). Kalau aplikasi dibiarkan sampai besok, jalankan:

```bash
php artisan antrian:limpahkan --tanpa-wa
```

Lalu buka **Kelola** poli terkait. Pasien limpahan harus muncul paling depan walaupun nomornya baru lebih besar, dan diberi label **Limpahan**.

### 10.4 Status batal

Tidak ada tombol **Batal** di aplikasi. Status `batal` hanya bisa muncul dari data seeder. Kalau ingin menguji filter `batal`, gunakan `DataUjiSeeder` atau ubah satu baris lewat database secara sadar:

```sql
UPDATE antrians
SET status = 'batal'
WHERE nomor_antrian = 'M004'
  AND tanggal = CURDATE();
```

Jangan jalankan SQL di atas pada database produksi.

## 11. Membersihkan data manual

Data manual tidak punya seeder pembersih karena memang dibuat untuk diinput sendiri. Ada dua pilihan:

### Pilihan A — reset total (paling mudah, menghapus semua data)

```bash
php artisan migrate:fresh
php artisan db:seed
```

Setelah itu akun admin/petugas ikut terhapus. Buat lagi akunnya (misalnya dengan `php artisan db:seed --class=DataUjiSeeder`) sebelum login.

### Pilihan B — hapus hanya data manual lewat SQL

Jalankan berurutan di phpMyAdmin atau client MySQL. Ganti nama database sesuai `.env` (`klinik_antrian`).

```sql
-- 1. Antrian milik pasien manual
DELETE a FROM antrians a
JOIN pasiens p ON p.id = a.pasien_id
WHERE p.nik LIKE '327310%';

-- 2. Pasien manual
DELETE FROM pasiens WHERE nik LIKE '327310%';

-- 3. Jadwal dokter manual
DELETE j FROM jadwal_praktiks j
JOIN dokters d ON d.id = j.dokter_id
WHERE d.no_sip LIKE 'SIP-MANUAL-%';

-- 4. Dokter manual
DELETE FROM dokters WHERE no_sip LIKE 'SIP-MANUAL-%';

-- 5. Akun dokter manual
DELETE FROM users
WHERE email IN ('maya.manual@klinik.test', 'nanda.manual@klinik.test');

-- 6. Poli manual
DELETE FROM polis WHERE kode_poli IN ('M', 'N', 'F', 'K');
```

Kalau ada antrian pasien lain yang terlanjur ditugaskan ke dokter manual, langkah 4 membuat `dokter_id` antrian tersebut menjadi `NULL`, bukan menghapus antriannya.

## 12. File CSV

Versi CSV dari tabel di atas ada di `docs/data-uji/`:

| File | Isi |
|---|---|
| `poli.csv` | Daftar poli |
| `dokter.csv` | Daftar dokter dan akun |
| `jadwal.csv` | Jadwal praktik |
| `pasien.csv` | Data pasien dan tiket |
| `skenario-antrian.csv` | Urutan aksi antrian |

CSV hanya **lembar referensi**, bukan fitur impor. Buka dengan Excel/LibreOffice; pemisahnya titik koma (`;`) dan sudah memakai UTF-8 BOM supaya huruf beraksen tetap benar.

## 13. Troubleshooting

| Gejala | Penyebab dan solusi |
|---|---|
| Pendaftaran ditutup | `OPERASIONAL_AKTIF=true` dan sekarang di luar jam. Set `false` lalu `php artisan config:clear`. |
| “Poli belum punya dokter aktif” | Dokter belum dibuat, nonaktif, atau salah poli. Aktifkan dokter lewat menu **Dokter**. |
| Nomor tidak mulai dari 001 | Poli tersebut sudah punya antrian hari ini. Nomor melanjutkan yang ada; lihat tiket. |
| Tidak bisa daftar lagi untuk pasien yang sama | Pasien masih punya antrian aktif (`menunggu`/`dipanggil`/`dilayani`) di poli itu. Selesaikan dulu atau pakai poli lain. |
| Nama pasien tidak berubah setelah daftar ulang | Saat NIK sudah terdaftar, aplikasi hanya memperbarui No. HP. Nama dan tanggal lahir mengikuti data pertama. |
| Data manual bercampur dengan DataUjiSeeder | `DataUjiSeeder` ikut membuat riwayat/akun untuk semua poli aktif, termasuk M/N/F. Untuk hasil terpisah, pilih salah satu atau reset database. |
| Analitik kosong | Default analitik berakhir kemarin. Data manual hari ini belum masuk. |
| Tombol batal tidak ada | Status `batal` hanya berasal dari seeder; tidak ada aksi batal di UI. |
| WhatsApp tidak terkirim | `WA_DRIVER=log` hanya mencatat ke log. Untuk uji aman, biarkan `log`. |
