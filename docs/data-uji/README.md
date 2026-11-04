# Data Uji Manual (CSV)

Folder ini berisi versi CSV dari data di [`../DATA-UJI-MANUAL.md`](../DATA-UJI-MANUAL.md).

> **CSV ini hanya lembar referensi, bukan fitur impor.** Aplikasi tidak punya menu impor CSV. Data tetap diinput lewat tampilan admin dan form `/antrian/ambil`.

## Cara membuka

- **Excel/LibreOffice:** klik dua kali file. Pemisah kolom yang dipakai adalah titik koma (`;`).
- **PowerShell:** `Import-Csv docs/data-uji/pasien.csv -Delimiter ';'`
- **Editor teks:** file memakai UTF-8 dengan BOM dan akhir baris CRLF.

## Daftar file

| File | Isi | Baris data |
|---|---|---:|
| `poli.csv` | Daftar poli | 4 |
| `dokter.csv` | Daftar dokter dan akun login | 4 |
| `jadwal.csv` | Jadwal praktik mingguan | 11 |
| `pasien.csv` | Data pasien dan perkiraan tiket | 10 |
| `skenario-antrian.csv` | Urutan aksi antrian | 26 |

## Arti kolom

### `poli.csv`

| Kolom | Arti |
|---|---|
| `no` | Nomor urut |
| `nama_poli` | Nama yang diisi di form poli |
| `kode_poli` | Awalan nomor antrian, maksimal 5 karakter |
| `aktif` | `Ya` atau `Tidak` |
| `catatan` | Keterangan untuk penguji |

### `dokter.csv`

| Kolom | Arti |
|---|---|
| `nama` | Nama dokter sesuai form |
| `poli_kode` | Kode poli tempat bertugas |
| `no_sip` | Nomor SIP untuk menandai data manual |
| `aktif` | `Ya` atau `Tidak` |
| `buat_akun` | `Ya` kalau centang “Buatkan akun login” dicentang |
| `email` | Email akun dokter (kosong kalau tanpa akun) |
| `password` | Password awal akun dokter |
| `catatan` | Keterangan untuk penguji |

### `jadwal.csv`

| Kolom | Arti |
|---|---|
| `dokter` | Nama dokter |
| `poli_kode` | Kode poli |
| `hari` | `senin` sampai `minggu`, huruf kecil |
| `jam_mulai` | Format 24 jam `HH:MM` |
| `jam_selesai` | Format 24 jam `HH:MM`, harus setelah `jam_mulai` |
| `kuota` | Jumlah pasien per sesi (1–200) |

### `pasien.csv`

| Kolom | Arti |
|---|---|
| `urutan` | Urutan pendaftaran lewat `/antrian/ambil` |
| `nama` | Nama lengkap |
| `nik` | NIK 16 digit; dipakai aplikasi untuk mengenali pasien lama |
| `tanggal_lahir` | Format `YYYY-MM-DD` |
| `jenis_kelamin` | `L` atau `P` |
| `no_hp` | Nomor WhatsApp |
| `alamat` | Alamat pasien |
| `poli_kode` | Poli yang dipilih saat mendaftar |
| `perkiraan_tiket` | Perkiraan nomor kalau poli belum punya antrian hari ini |
| `status_akhir` | Status yang dituju setelah skenario |

`no_rm` tidak dicantumkan karena dibuat otomatis oleh aplikasi dengan pola `RM-yymmdd-xxxx`.

### `skenario-antrian.csv`

| Kolom | Arti |
|---|---|
| `langkah` | Urutan aksi |
| `poli_kode` | Poli tempat aksi dilakukan |
| `perkiraan_nomor` | Tiket yang disentuh |
| `pasien` | Nama pasien |
| `aksi` | Tombol/aksi yang dijalankan |
| `status_setelah` | Status tiket setelah aksi |
| `catatan` | Petunjuk tambahan |

## Catatan penting

1. **Nomor tiket bisa bergeser.** Kalau Poli Mata/Saraf/Fisioterapi sudah punya antrian hari ini, nomor melanjutkan nomor yang ada. Nomor sebenarnya selalu ada di tiket.
2. **NIK `327310...` khusus data manual ini.** Jangan memakai NIK contoh di produksi.
3. **Akun `.manual@klinik.test` hanya untuk lokal.** Password ada di `dokter.csv`.
4. **Data ini tidak dibuat oleh seeder.** Untuk membuatnya, ikuti langkah di `DATA-UJI-MANUAL.md`.
5. **Data ini tidak punya pembersih otomatis.** Cara menghapusnya ada di bagian 11 dokumen panduan.
