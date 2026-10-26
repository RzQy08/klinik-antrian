<?php

namespace Database\Seeders;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * RESET data demo: menghapus SEMUA akun user, pasien, dan antrian,
 * lalu membuat akun dan pasien baru. Poli, dokter, dan jadwal tidak disentuh.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Catatan: antrian ikut terhapus karena terhubung ke pasien (cascade).
 * Riwayat simulasi 30 hari dibuat ulang di akhir (lihat BUAT_RIWAYAT_30_HARI).
 */
class DemoSeeder extends Seeder
{
    /** Ubah ke false kalau tidak ingin riwayat simulasi 30 hari dibuat ulang. */
    private const BUAT_RIWAYAT_30_HARI = true;

    /** [peran, nama, email, password] - setiap akun punya password sendiri. */
    private const AKUN_STAF = [
        ['admin', 'Admin Klinik', 'admin@klinik.test', 'Admin@Klinik26'],
        ['petugas', 'Petugas Klinik', 'petugas@klinik.test', 'Petugas@Klinik26'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->error('Seeder ini menghapus semua akun dan pasien, jadi tidak boleh dijalankan di produksi.');

            return;
        }

        $this->command->warn('Seeder ini akan MENGHAPUS semua akun user, semua pasien, dan semua antrian, lalu membuat data demo baru.');

        if (! $this->command->confirm('Lanjutkan?', false)) {
            $this->command->info('Dibatalkan. Tidak ada data yang berubah.');

            return;
        }

        // Poli, dokter, dan jadwal dibutuhkan; siapkan dulu kalau database masih kosong
        if (Poli::count() === 0) {
            $this->call([PoliSeeder::class, DokterSeeder::class, JadwalPraktikSeeder::class]);
        }

        DB::transaction(function () {
            Antrian::query()->delete();
            Pasien::query()->delete();
            User::query()->delete(); // dokters.user_id otomatis menjadi null (nullOnDelete)
        });

        $akun = array_merge($this->buatAkunStaf(), $this->buatAkunDokter());
        $pasiens = $this->buatPasien();
        $this->buatAntrianHariIni($pasiens);

        if (self::BUAT_RIWAYAT_30_HARI) {
            $this->call(AntrianSeeder::class);
        }

        $this->command->newLine();
        $this->command->info('Selesai. Akun untuk login (password tiap akun berbeda):');
        $this->command->table(['Peran', 'Email', 'Password'], $akun);

        $this->command->newLine();
        $this->command->info('Pasien dummy (bisa dipakai di form ambil antrian):');
        $this->command->table(
            ['No. RM', 'Nama', 'NIK'],
            $pasiens->map(fn (Pasien $p) => [$p->no_rm, $p->nama, $p->nik])->all()
        );
    }

    private function buatAkunStaf(): array
    {
        $baris = [];

        foreach (self::AKUN_STAF as [$peran, $nama, $email, $password]) {
            User::create([
                'name' => $nama,
                'email' => $email,
                'password' => $password,
                'role' => $peran,
            ]);

            $baris[] = [ucfirst($peran), $email, $password];
        }

        return $baris;
    }

    /**
     * Satu akun per dokter, dihubungkan lewat dokters.user_id.
     * Email dan password dibuat berurutan: dokter1, dokter2, dst.
     */
    private function buatAkunDokter(): array
    {
        $baris = [];

        foreach (Dokter::orderBy('id')->get() as $i => $dokter) {
            $nomor = $i + 1;
            $email = "dokter{$nomor}@klinik.test";
            $password = "Dokter{$nomor}@Klinik26";

            $user = User::create([
                'name' => $dokter->nama,
                'email' => $email,
                'password' => $password,
                'role' => 'dokter',
            ]);

            $dokter->update(['user_id' => $user->id]);

            $baris[] = ["Dokter ({$dokter->nama})", $email, $password];
        }

        return $baris;
    }

    /**
     * Delapan pasien dengan NIK tetap (16 digit), sehingga pasien lama
     * dikenali otomatis saat ambil antrian dengan NIK yang sama.
     */
    private function buatPasien()
    {
        $data = [
            ['3201011503900001', 'Budi Hartono', '1990-03-15', 'L', '081234560001', 'Jl. Melati No. 12, Bandung'],
            ['3201015206850002', 'Siti Rahmawati', '1985-06-12', 'P', '081234560002', 'Jl. Kenanga No. 4, Bandung'],
            ['3201010108010003', 'Ahmad Fauzan', '2001-08-01', 'L', '081234560003', 'Jl. Anggrek No. 27, Cimahi'],
            ['3201014412780004', 'Dewi Lestari', '1978-12-04', 'P', '081234560004', 'Jl. Dahlia No. 9, Bandung'],
            ['3201012009150005', 'Rizky Pratama', '2015-09-20', 'L', '081234560005', 'Jl. Mawar No. 31, Lembang'],
            ['3201011010600006', 'Aminah', '1960-10-10', 'P', '081234560006', 'Jl. Cempaka No. 18, Bandung'],
            ['3201010505950007', 'Andi Saputra', '1995-05-05', 'L', '081234560007', 'Jl. Flamboyan No. 6, Cimahi'],
            ['3201012807880008', 'Maya Anggraini', '1988-07-28', 'P', '081234560008', 'Jl. Seruni No. 15, Bandung'],
        ];

        $pasiens = collect();

        foreach ($data as $i => [$nik, $nama, $lahir, $jk, $hp, $alamat]) {
            $pasiens->push(Pasien::create([
                'no_rm' => sprintf('RM-DEMO-%03d', $i + 1),
                'nama' => $nama,
                'nik' => $nik,
                'tanggal_lahir' => $lahir,
                'jenis_kelamin' => $jk,
                'no_hp' => $hp,
                'alamat' => $alamat,
            ]));
        }

        return $pasiens;
    }

    /**
     * Antrian hari ini dengan status campuran supaya semua tombol bisa dicoba:
     * Poli pertama : 2 selesai, 1 dilayani, 1 dipanggil, 3 menunggu
     * Poli kedua   : 2 menunggu
     */
    private function buatAntrianHariIni($pasiens): void
    {
        $polis = Poli::where('aktif', true)
            ->orderBy('kode_poli')
            ->with(['dokters' => fn ($q) => $q->where('aktif', true)->orderBy('id')])
            ->take(2)
            ->get();

        // [status, menit lalu daftar, dipanggil, mulai dilayani, selesai]
        $skenarioPertama = [
            ['selesai', 100, 92, 90, 78],
            ['selesai', 90, 78, 76, 64],
            ['dilayani', 80, 62, 60, null],
            ['dipanggil', 70, 5, null, null],
            ['menunggu', 60, null, null, null],
            ['menunggu', 45, null, null, null],
            ['menunggu', 30, null, null, null],
        ];

        $skenarioKedua = [
            ['menunggu', 25, null, null, null],
            ['menunggu', 10, null, null, null],
        ];

        $putar = 0;

        foreach ($polis as $indeks => $poli) {
            if ($poli->dokters->isEmpty()) {
                $this->command->warn("Poli {$poli->nama_poli} belum punya dokter aktif, antrian hari ini dilewati.");
                continue;
            }

            $skenario = $indeks === 0 ? $skenarioPertama : $skenarioKedua;

            foreach ($skenario as $urut => [$status, $daftar, $dipanggil, $mulai, $selesai]) {
                $pasien = $pasiens[$putar % $pasiens->count()];
                $putar++;

                $dokter = $poli->dokters[$urut % $poli->dokters->count()];

                Antrian::create([
                    'pasien_id' => $pasien->id,
                    'poli_id' => $poli->id,
                    'dokter_id' => $status === 'menunggu' ? null : $dokter->id,
                    'nomor_antrian' => $poli->kode_poli . str_pad((string) ($urut + 1), 3, '0', STR_PAD_LEFT),
                    'status' => $status,
                    'waktu_daftar' => now()->subMinutes($daftar),
                    'waktu_dipanggil' => $dipanggil !== null ? now()->subMinutes($dipanggil) : null,
                    'waktu_mulai_dilayani' => $mulai !== null ? now()->subMinutes($mulai) : null,
                    'waktu_selesai' => $selesai !== null ? now()->subMinutes($selesai) : null,
                    'estimasi_tunggu_detik' => $status === 'menunggu' ? 600 : null,
                    'tanggal' => now()->toDateString(),
                ]);
            }

            $this->command->info("Antrian hari ini untuk {$poli->nama_poli}: " . count($skenario) . ' data dibuat.');
        }
    }
}
