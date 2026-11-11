<?php

namespace App\Services;

use App\Models\User;
use App\Support\AksesLokal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Mencari kesalahan konfigurasi dan kebiasaan yang membuka data pasien, yaitu kesalahan manusia
 * yang paling sering terjadi saat aplikasi dipindah dari komputer sendiri ke server.
 * Dipakai oleh "php artisan keamanan:periksa" dan peringatan di dashboard admin.
 */
class PemeriksaKeamanan
{
    /** Password bawaan seeder yang tertulis di repositori, jadi harus dianggap diketahui publik. */
    private const PASSWORD_BAWAAN = ['password', 'Admin@Klinik26', 'Petugas@Klinik26'];

    /**
     * @return list<array{level: 'bahaya'|'waspada', judul: string, teks: string}>
     */
    public function periksa(bool $dariWeb = false): array
    {
        $hasil = [];
        $tambah = function (string $level, string $judul, string $teks) use (&$hasil) {
            $hasil[] = compact('level', 'judul', 'teks');
        };

        $produksi = app()->isProduction();
        $lokal = app()->isLocal();

        if (config('app.debug') && ! $lokal) {
            $tambah('bahaya', 'APP_DEBUG masih true di luar lokal', 'Halaman error menampilkan isi .env (kunci aplikasi dan password database). Ubah ke APP_DEBUG=false lalu jalankan php artisan config:clear.');
        }

        if ($produksi && config('app.debug')) {
            $tambah('bahaya', 'APP_DEBUG=true di produksi', 'Aplikasi memaksa debug mati untuk pengunjung, tetapi nilai di .env tetap harus diperbaiki.');
        }

        if ($dariWeb && $lokal && ! AksesLokal::hostLokal(request()->getHost())) {
            $tambah('bahaya', 'APP_ENV=local diakses dari alamat luar', 'Aplikasi sedang dibuka lewat '.request()->getHost().' tetapi mode lokal. Untuk server atau tunnel yang dipakai orang lain, ubah ke APP_ENV=production.');
        }

        if (blank(config('app.key'))) {
            $tambah('bahaya', 'APP_KEY kosong', 'Sesi dan data terenkripsi tidak aman. Jalankan php artisan key:generate.');
        }

        if ($produksi && ! str_starts_with((string) config('app.url'), 'https://')) {
            $tambah('waspada', 'APP_URL belum https', 'Data pasien dan password dikirim tanpa enkripsi bila situs tidak memakai HTTPS.');
        }

        if ($produksi && is_file(public_path('hot'))) {
            $tambah('waspada', 'File public/hot ada di produksi', 'File ini hanya untuk npm run dev. Hapus supaya aset tidak dicari ke localhost.');
        }

        if ((int) config('session.lifetime') > 60) {
            $tambah('waspada', 'Sesi login terlalu panjang', 'SESSION_LIFETIME='.config('session.lifetime').' menit. Komputer loket yang ditinggal tetap terbuka. Disarankan 30.');
        }

        if ($produksi && ! config('session.secure')) {
            $tambah('waspada', 'Cookie sesi belum khusus HTTPS', 'Isi SESSION_SECURE_COOKIE=true di .env.');
        }

        foreach ($this->berkasTerbuka() as $berkas) {
            $tambah('bahaya', 'Berkas sensitif ada di folder public', "{$berkas} bisa diunduh siapa pun. Pastikan web server mengarah ke folder public, bukan ke folder proyek.");
        }

        $bawaan = $this->akunBawaan();

        if ($bawaan !== []) {
            $tambah($produksi ? 'bahaya' : 'waspada', count($bawaan).' akun masih memakai password bawaan seeder', 'Password-nya tertulis di repositori GitHub. Ganti atau hapus: '.implode(', ', array_slice($bawaan, 0, 4)).(count($bawaan) > 4 ? ', dan lainnya' : '').'.');
        }

        return $hasil;
    }

    /** Hasil ringkas untuk dashboard admin (di-cache supaya tidak memeriksa hash password tiap muat halaman). */
    public function untukDashboard(): array
    {
        return Cache::remember('keamanan.peringatan.v1', now()->addMinutes(10), fn () => $this->periksa(true));
    }

    /** @return list<string> */
    private function berkasTerbuka(): array
    {
        $ketemu = [];

        foreach (['.env', '.env.example', '.git', 'artisan', 'composer.json', 'phpinfo.php', 'info.php', 'database.sqlite'] as $nama) {
            if (file_exists(public_path($nama))) {
                $ketemu[] = 'public/'.$nama;
            }
        }

        foreach (glob(public_path('*.{sql,sqlite,zip,bak,log}'), GLOB_BRACE) ?: [] as $berkas) {
            $ketemu[] = 'public/'.basename($berkas);
        }

        return $ketemu;
    }

    /** @return list<string> email akun yang password-nya masih bawaan seeder */
    private function akunBawaan(): array
    {
        $daftar = [];

        // Hanya akun dengan email seeder yang diperiksa, supaya cepat (hash bcrypt sengaja lambat)
        foreach (User::where('email', 'like', '%@klinik.test')->get(['id', 'email', 'password']) as $user) {
            $kandidat = self::PASSWORD_BAWAAN;

            if (preg_match('/^dokter(\d+)@/i', $user->email, $m)) {
                $kandidat[] = "Dokter{$m[1]}@Klinik26";
            }

            foreach ($kandidat as $password) {
                if (Hash::check($password, $user->password)) {
                    $daftar[] = $user->email;
                    break;
                }
            }
        }

        return $daftar;
    }
}
