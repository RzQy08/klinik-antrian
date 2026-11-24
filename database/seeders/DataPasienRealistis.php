<?php

namespace Database\Seeders;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Daftar pasien contoh yang dipakai bersama oleh DemoSeeder dan DataUjiSeeder.
 *
 * Semua orang di sini fiktif. Supaya tidak kebetulan sama dengan orang sungguhan:
 * - NIK disusun sesuai struktur asli (kode kecamatan + tanggal lahir + nomor urut), tetapi nomor
 *   urutnya selalu 9001 ke atas, rentang yang jarang dipakai pada NIK sungguhan;
 * - nomor HP memakai blok "0000" di tengah, yang tidak dialokasikan ke pelanggan.
 * Setiap nama, NIK, dan nomor HP dijamin unik (diperiksa oleh tes).
 */
final class DataPasienRealistis
{
    /** Delapan pertama dipakai DemoSeeder, sisanya DataUjiSeeder, jadi tidak ada orang ganda. */
    public const JUMLAH_DEMO = 8;

    /**
     * Jalan => [kecamatan, kode kecamatan menurut Kemendagri, kota].
     *
     * @var array<string, array{0: string, 1: string, 2: string}>
     */
    private const JALAN = [
        'Jl. Cihampelas' => ['Coblong', '327327', 'Kota Bandung'],
        'Jl. Dago' => ['Coblong', '327327', 'Kota Bandung'],
        'Jl. Kopo' => ['Bojongloa Kaler', '327303', 'Kota Bandung'],
        'Jl. Buah Batu' => ['Lengkong', '327307', 'Kota Bandung'],
        'Jl. Pasteur' => ['Sukajadi', '327328', 'Kota Bandung'],
        'Jl. Sukajadi' => ['Sukajadi', '327328', 'Kota Bandung'],
        'Jl. Setiabudi' => ['Sukasari', '327329', 'Kota Bandung'],
        'Jl. Gegerkalong Tengah' => ['Sukasari', '327329', 'Kota Bandung'],
        'Jl. Cicaheum' => ['Kiaracondong', '327319', 'Kota Bandung'],
        'Jl. Asia Afrika' => ['Sumur Bandung', '327321', 'Kota Bandung'],
        'Jl. Pasirkaliki' => ['Cicendo', '327323', 'Kota Bandung'],
        'Jl. Antapani Lama' => ['Antapani', '327317', 'Kota Bandung'],
        'Jl. Holis' => ['Babakan Ciparay', '327302', 'Kota Bandung'],
        'Jl. Leuwipanjang' => ['Bojongloa Kidul', '327304', 'Kota Bandung'],
        'Jl. Baros' => ['Cimahi Tengah', '327702', 'Kota Cimahi'],
        'Jl. Cihanjuang' => ['Cimahi Utara', '327703', 'Kota Cimahi'],
        'Jl. Mahar Martanegara' => ['Cimahi Selatan', '327701', 'Kota Cimahi'],
    ];

    /**
     * [nama, tanggal lahir, jenis kelamin]
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    private const ORANG = [
        ['Muhammad Rizki Ramadhan', '1998-03-14', 'L'],
        ['Siti Aisyah', '1990-05-11', 'P'],
        ['Agus Setiawan', '1985-07-22', 'L'],
        ['Ratna Sari Dewi', '1984-08-16', 'P'],
        ['Dedi Kurniawan', '1979-11-05', 'L'],
        ['Nur Fadilah', '1997-01-23', 'P'],
        ['Yusuf Ramadhan', '2003-01-30', 'L'],
        ['Aulia Rahmawati', '2001-09-14', 'P'],
        ['Bagus Prasetyo', '1992-09-17', 'L'],
        ['Putri Maharani', '1999-12-29', 'P'],
        ['Hendra Saputra', '1968-04-09', 'L'],
        ['Ani Suryani', '1972-07-08', 'P'],
        ['Asep Suryana', '1974-12-21', 'L'],
        ['Euis Kartika', '1961-03-02', 'P'],
        ['Dimas Aditya Nugraha', '2000-06-08', 'L'],
        ['Neneng Rohaeni', '1968-11-27', 'P'],
        ['Eko Wahyudi', '1981-02-26', 'L'],
        ['Tuti Handayani', '1957-09-19', 'P'],
        ['Fahri Ramdani', '2008-10-12', 'L'],
        ['Rina Marlina', '1986-04-04', 'P'],
        ['Iwan Hermawan', '1963-08-03', 'L'],
        ['Dewi Anggraeni', '1991-10-06', 'P'],
        ['Rudi Hartono', '1971-05-19', 'L'],
        ['Lilis Setiawati', '1980-02-18', 'P'],
        ['Naufal Hakim', '2016-02-11', 'L'],
        ['Wulan Sari', '1994-06-30', 'P'],
        ['Taufik Hidayat', '1989-09-28', 'L'],
        ['Dian Pratiwi', '1988-01-12', 'P'],
        ['Wahyu Nugroho', '1995-12-02', 'L'],
        ['Indah Permatasari', '2004-05-20', 'P'],
        ['Joko Santoso', '1958-06-15', 'L'],
        ['Salsabila Nur Azizah', '2010-03-09', 'P'],
        ['Ridwan Fauzi', '1987-10-24', 'L'],
        ['Kayla Putri Ramadhani', '2018-11-21', 'P'],
        ['Arief Budiman', '1987-03-07', 'L'],
        ['Mega Lestari', '1982-09-01', 'P'],
        ['Galih Permana', '1999-07-25', 'L'],
        ['Yuni Astuti', '1975-12-15', 'P'],
        ['Ilham Syahputra', '2001-11-18', 'L'],
        ['Sri Wahyuni', '1954-04-26', 'P'],
        ['Sutrisno', '1955-01-20', 'L'],
        ['Citra Anindya', '1996-02-07', 'P'],
        ['Cecep Mulyana', '1966-10-30', 'L'],
        ['Nia Kurniasih', '1987-07-31', 'P'],
        ['Ujang Solihin', '1977-04-13', 'L'],
        ['Hana Zahira', '2006-08-24', 'P'],
        ['Bayu Anggara', '1996-08-21', 'L'],
        ['Rosmawati Siregar', '1965-01-05', 'P'],
        ['Reza Pahlevi', '1993-05-09', 'L'],
        ['Nurul Hidayah', '1993-10-19', 'P'],
        ['Alif Rahman Hakim', '2012-09-03', 'L'],
        ['Eva Susanti', '1979-06-22', 'P'],
        ['Anton Sutanto', '1976-02-14', 'L'],
        ['Alya Khairunnisa', '2013-12-08', 'P'],
        ['Rahmat Hidayat', '1983-06-06', 'L'],
        ['Tiara Amelia', '2000-03-27', 'P'],
        ['Teguh Prakoso', '1969-03-25', 'L'],
        ['Maya Sari Nasution', '1989-05-13', 'P'],
        ['Yoga Pratama', '2002-04-17', 'L'],
        ['Endang Rahayu', '1962-08-29', 'P'],
    ];

    /**
     * Seluruh pasien contoh, berurutan dan tetap (tidak acak).
     *
     * @return array<int, array{nik: string, nama: string, tanggal_lahir: string, jenis_kelamin: string, no_hp: string, alamat: string}>
     */
    public static function semua(): array
    {
        $jalan = array_keys(self::JALAN);
        $operator = ['0812', '0813', '0821', '0857', '0878', '0896'];
        $hasil = [];

        foreach (self::ORANG as $i => [$nama, $lahir, $jk]) {
            $namaJalan = $jalan[$i % count($jalan)];
            [$kecamatan, $kode, $kota] = self::JALAN[$namaJalan];

            $hasil[] = [
                'nik' => self::nik($kode, $lahir, $jk, 9001 + $i),
                'nama' => $nama,
                'tanggal_lahir' => $lahir,
                'jenis_kelamin' => $jk,
                'no_hp' => $operator[$i % count($operator)].'0000'.sprintf('%04d', 1001 + $i),
                'alamat' => sprintf(
                    '%s No. %d, RT %02d/RW %02d, Kec. %s, %s',
                    $namaJalan,
                    10 + (($i * 17) % 190),
                    1 + ($i % 9),
                    1 + (($i * 3) % 12),
                    $kecamatan,
                    $kota
                ),
            ];
        }

        return $hasil;
    }

    /** @return array<int, array<string, string>> Pasien untuk DemoSeeder (RM-DEMO-001 dst). */
    public static function untukDemo(): array
    {
        return array_slice(self::semua(), 0, self::JUMLAH_DEMO);
    }

    /** @return array<int, array<string, string>> Pasien untuk DataUjiSeeder (RM-UJI-001 dst). */
    public static function untukUji(): array
    {
        return array_slice(self::semua(), self::JUMLAH_DEMO);
    }

    /**
     * NIK 16 digit: 6 digit kode kecamatan, 6 digit tanggal lahir (DDMMYY, perempuan ditambah 40
     * pada tanggalnya), dan 4 digit nomor urut.
     */
    public static function nik(string $kodeKecamatan, string $tanggalLahir, string $jenisKelamin, int $urut): string
    {
        if (strlen($kodeKecamatan) !== 6 || ! in_array($jenisKelamin, ['L', 'P'], true) || $urut < 1 || $urut > 9999) {
            throw new InvalidArgumentException('Data NIK tidak valid.');
        }

        $lahir = Carbon::parse($tanggalLahir);
        $hari = $lahir->day + ($jenisKelamin === 'P' ? 40 : 0);

        return $kodeKecamatan.sprintf('%02d%02d%02d', $hari, $lahir->month, $lahir->year % 100).sprintf('%04d', $urut);
    }
}
