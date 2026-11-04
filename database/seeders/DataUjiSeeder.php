<?php

namespace Database\Seeders;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\Pasien;
use App\Models\Poli;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Data uji untuk mencoba semua fitur tanpa menghapus data pengguna lain.
 *
 * Cara pakai:
 *   php artisan db:seed --class=DataUjiSeeder
 *
 * Sifat seeder:
 * - Menambah akun uji, pasien uji, antrian hari ini, dan riwayat 14 hari.
 * - Tidak menghapus pasien/antrian milik pengguna lain.
 * - Hanya mengulang (hapus lalu buat ulang) antrian milik pasien uji sendiri,
 *   supaya seeder aman dijalankan berkali-kali tanpa menggandakan data.
 * - Menolak berjalan di produksi.
 */
class DataUjiSeeder extends Seeder
{
    public const EMAIL_ADMIN = 'admin.uji@klinik.test';

    public const EMAIL_PETUGAS = 'petugas.uji@klinik.test';

    public const PASSWORD_ADMIN = 'AdminUji#2026';

    public const PASSWORD_PETUGAS = 'PetugasUji#2026';

    public const PASSWORD_DOKTER = 'DokterUji#2026';

    private const DOMAIN_UJI = 'klinik.test';

    /** Jumlah hari riwayat ke belakang (termasuk hari ini? tidak: hanya sampai kemarin). */
    private const HARI_RIWAYAT = 14;

    /**
     * Pasien uji dengan NIK tetap. NIK sengaja 16 digit dan berbeda dari
     * pasien DemoSeeder (320101...) supaya mudah dibedakan.
     *
     * @var array<int, array{no_rm: string, nik: string, nama: string, tanggal_lahir: string, jenis_kelamin: string, no_hp: string, alamat: string}>
     */
    private const PASIEN_UJI = [
        [
            'no_rm' => 'RM-UJI-001',
            'nik' => '3273010101900001',
            'nama' => 'Raka Pratama',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000001',
            'alamat' => 'Jl. Cihampelas No. 10, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-002',
            'nik' => '3273015206850002',
            'nama' => 'Sinta Maharani',
            'tanggal_lahir' => '1985-06-12',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000002',
            'alamat' => 'Jl. Dago No. 18, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-003',
            'nik' => '3273010108010003',
            'nama' => 'Ahmad Fauzan',
            'tanggal_lahir' => '2001-08-01',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000003',
            'alamat' => 'Jl. Anggrek No. 27, Cimahi',
        ],
        [
            'no_rm' => 'RM-UJI-004',
            'nik' => '3273014412780004',
            'nama' => 'Dewi Lestari',
            'tanggal_lahir' => '1978-12-04',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000004',
            'alamat' => 'Jl. Dahlia No. 9, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-005',
            'nik' => '3273012009150005',
            'nama' => 'Rizky Pratama',
            'tanggal_lahir' => '2015-09-20',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000005',
            'alamat' => 'Jl. Mawar No. 31, Lembang',
        ],
        [
            'no_rm' => 'RM-UJI-006',
            'nik' => '3273011010600006',
            'nama' => 'Aminah',
            'tanggal_lahir' => '1960-10-10',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000006',
            'alamat' => 'Jl. Cempaka No. 18, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-007',
            'nik' => '3273010505950007',
            'nama' => 'Andi Saputra',
            'tanggal_lahir' => '1995-05-05',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000007',
            'alamat' => 'Jl. Flamboyan No. 6, Cimahi',
        ],
        [
            'no_rm' => 'RM-UJI-008',
            'nik' => '3273012807880008',
            'nama' => 'Maya Anggraini',
            'tanggal_lahir' => '1988-07-28',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000008',
            'alamat' => 'Jl. Seruni No. 15, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-009',
            'nik' => '3273011204020009',
            'nama' => 'Bagas Nugroho',
            'tanggal_lahir' => '2002-04-12',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000009',
            'alamat' => 'Jl. Sukajadi No. 21, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-010',
            'nik' => '3273016709990010',
            'nama' => 'Nadia Putri',
            'tanggal_lahir' => '1999-09-27',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000010',
            'alamat' => 'Jl. Pasteur No. 8, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-011',
            'nik' => '3273011507930011',
            'nama' => 'Fajar Hidayat',
            'tanggal_lahir' => '1993-07-15',
            'jenis_kelamin' => 'L',
            'no_hp' => '081230000011',
            'alamat' => 'Jl. Setiabudi No. 44, Bandung',
        ],
        [
            'no_rm' => 'RM-UJI-012',
            'nik' => '3273012103660012',
            'nama' => 'Kartini',
            'tanggal_lahir' => '1966-03-21',
            'jenis_kelamin' => 'P',
            'no_hp' => '081230000012',
            'alamat' => 'Jl. Kopo No. 30, Bandung',
        ],
    ];

    /**
     * Rata-rata lama layanan per poli (menit) untuk riwayat simulasi.
     *
     * @var array<string, int>
     */
    private const LAYANAN_MENIT = [
        'A' => 12,
        'B' => 20,
        'C' => 15,
        'D' => 15,
    ];

    /**
     * Jumlah pasien selesai per hari per poli untuk riwayat simulasi.
     * Angka ini dipilih supaya rho (λ / (c·μ)) tetap < 1.
     *
     * @var array<string, int>
     */
    private const PASIEN_PER_HARI = [
        'A' => 48,
        'B' => 16,
        'C' => 24,
        'D' => 32,
    ];

    /** @var Collection<int, Pasien> */
    private Collection $pasiens;

    /** @var array<int, array{peran: string, email: string, password: string, catatan: string}> */
    private array $akun = [];

    /** @var array<int, array{poli: string, nomor: string, status: string, pasien: string, dokter: string}> */
    private array $ringkasanAntrian = [];

    private int $jumlahRiwayat = 0;

    private int $rotasiPasien = 0;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DataUjiSeeder membuat akun dan data palsu, jadi tidak boleh dijalankan di produksi.');

            return;
        }

        $this->siapkanMaster();
        $this->pasiens = $this->siapkanPasien();

        if ($this->pasiens->isEmpty()) {
            $this->command?->error('Tidak ada pasien uji yang bisa dibuat. Seeder dihentikan.');

            return;
        }

        $this->siapkanAkun();
        $this->buatRiwayat();
        $this->buatAntrianHariIni();
        $this->cetakRingkasan();
    }

    /**
     * Pastikan database punya data master minimal. Kalau sudah ada, tidak diubah.
     */
    private function siapkanMaster(): void
    {
        if (Poli::query()->count() === 0) {
            $this->call(PoliSeeder::class);
        }

        if (Dokter::query()->count() === 0) {
            $this->call(DokterSeeder::class);
        }

        if (JadwalPraktik::query()->count() === 0) {
            $this->call(JadwalPraktikSeeder::class);
        }
    }

    /**
     * @return Collection<int, Pasien>
     */
    private function siapkanPasien(): Collection
    {
        $hasil = collect();

        foreach (self::PASIEN_UJI as $data) {
            $pasien = Pasien::query()->where('nik', $data['nik'])->first();

            if (! $pasien) {
                $pasien = Pasien::query()->firstOrCreate(
                    ['no_rm' => $data['no_rm']],
                    $data
                );
            }

            $hasil->push($pasien);
        }

        return $hasil->unique('id')->values();
    }

    private function siapkanAkun(): void
    {
        $this->simpanAkun(
            self::EMAIL_ADMIN,
            'Admin Uji Coba',
            self::PASSWORD_ADMIN,
            'admin',
            'Akses penuh ke dashboard admin'
        );

        $this->simpanAkun(
            self::EMAIL_PETUGAS,
            'Petugas Uji Coba',
            self::PASSWORD_PETUGAS,
            'petugas',
            'Antrian hari ini, analitik, dan riwayat'
        );

        foreach ($this->poliAktifDenganDokter() as $poli) {
            foreach ($poli->dokters as $dokter) {
                if ($dokter->user_id !== null) {
                    $user = $dokter->user;

                    if ($user && str_ends_with($user->email, '.uji@'.self::DOMAIN_UJI)) {
                        $email = $user->email;

                        $user->update([
                            'name' => $dokter->nama,
                            'password' => self::PASSWORD_DOKTER,
                            'role' => 'dokter',
                        ]);
                    } else {
                        $this->akun[] = [
                            'peran' => 'Dokter',
                            'email' => $user?->email ?? '-',
                            'password' => '-',
                            'catatan' => "Dilewati: {$dokter->nama} sudah punya akun sendiri",
                        ];

                        continue;
                    }
                } else {
                    $email = $this->emailDokter($dokter);

                    $user = User::query()->updateOrCreate(
                        ['email' => $email],
                        [
                            'name' => $dokter->nama,
                            'password' => self::PASSWORD_DOKTER,
                            'role' => 'dokter',
                        ]
                    );

                    $dokter->update(['user_id' => $user->id]);
                }

                $this->akun[] = [
                    'peran' => 'Dokter',
                    'email' => $email,
                    'password' => self::PASSWORD_DOKTER,
                    'catatan' => "{$dokter->nama} ({$poli->nama_poli})",
                ];
            }
        }
    }

    private function simpanAkun(string $email, string $nama, string $password, string $peran, string $catatan): void
    {
        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $nama,
                'password' => $password,
                'role' => $peran,
            ]
        );

        $this->akun[] = [
            'peran' => ucfirst($peran),
            'email' => $email,
            'password' => $password,
            'catatan' => $catatan,
        ];
    }

    private function emailDokter(Dokter $dokter): string
    {
        $dasar = Str::slug($dokter->nama) ?: 'dokter-'.$dokter->id;
        $email = $dasar.'.uji@'.self::DOMAIN_UJI;

        $pemilik = User::query()->where('email', $email)->value('id');

        if ($pemilik !== null && (int) $pemilik !== (int) $dokter->user_id) {
            $email = $dasar.'.'.$dokter->id.'.uji@'.self::DOMAIN_UJI;
        }

        return $email;
    }

    /**
     * @return Collection<int, Poli>
     */
    private function poliAktifDenganDokter(): Collection
    {
        return Poli::query()
            ->where('aktif', true)
            ->with(['dokters' => fn ($q) => $q->where('aktif', true)->orderBy('id')])
            ->orderBy('kode_poli')
            ->get()
            ->filter(fn (Poli $poli) => $poli->dokters->isNotEmpty())
            ->values();
    }

    /**
     * Riwayat 14 hari sampai kemarin untuk mengisi halaman analitik.
     * Hanya memakai pasien uji, sehingga data pengguna lain tidak tersentuh.
     */
    private function buatRiwayat(): void
    {
        $mulai = Carbon::today()->subDays(self::HARI_RIWAYAT);
        $akhir = Carbon::yesterday();

        Antrian::query()
            ->whereIn('pasien_id', $this->pasiens->pluck('id'))
            ->antaraTanggal($mulai, $akhir)
            ->delete();

        $polis = $this->poliAktifDenganDokter();

        if ($polis->isEmpty()) {
            $this->command?->warn('Riwayat tidak dibuat: belum ada poli aktif yang punya dokter aktif.');

            return;
        }

        foreach (CarbonPeriod::create($mulai, $akhir) as $tanggal) {
            $hari = Carbon::parse($tanggal);

            // Klinik tutup hari Minggu, sama seperti JadwalPraktikSeeder.
            if ($hari->isSunday()) {
                continue;
            }

            foreach ($polis as $poli) {
                $this->jumlahRiwayat += $this->buatRiwayatSatuHari($poli, $hari);
            }
        }
    }

    /**
     * Simulasi M/M/c sederhana untuk satu poli pada satu hari:
     * pasien datang dalam dua gelombang, lalu dilayani dokter yang paling cepat kosong.
     */
    private function buatRiwayatSatuHari(Poli $poli, Carbon $hari): int
    {
        $dokters = $poli->dokters;

        if ($dokters->isEmpty()) {
            return 0;
        }

        $kode = $poli->kode_poli;
        $jumlah = self::PASIEN_PER_HARI[$kode] ?? 16;
        $layanan = self::LAYANAN_MENIT[$kode] ?? 12;

        $pagi = max(1, (int) ceil($jumlah * 0.6));
        $siang = max(0, $jumlah - $pagi);

        $kedatangan = [];

        // Gelombang pagi: 08.00 - 11.00
        for ($i = 0; $i < $pagi; $i++) {
            $kedatangan[] = 8 * 60 + ($pagi <= 1 ? 0 : (int) round($i * 180 / ($pagi - 1)));
        }

        // Gelombang siang: 13.00 - 15.30
        for ($i = 0; $i < $siang; $i++) {
            $kedatangan[] = 13 * 60 + ($siang <= 1 ? 0 : (int) round($i * 150 / ($siang - 1)));
        }

        $bebas = array_fill(0, $dokters->count(), 8 * 60);
        $baris = [];

        foreach ($kedatangan as $i => $menitDatang) {
            // Variasi kecil supaya kedatangan tidak terlihat terlalu rapi.
            $menitDatang += (($i * 7) % 5) - 2;
            $menitDatang = max(8 * 60, min((16 * 60) - 1, $menitDatang));

            $server = array_keys($bebas, min($bebas), true)[0];
            $menitMulai = max($menitDatang, $bebas[$server]);
            $lamaLayanan = max(2, $layanan + (($i * 3) % 5) - 2);
            $menitSelesai = $menitMulai + $lamaLayanan;
            $bebas[$server] = $menitSelesai;

            $pasien = $this->pasiens[$this->rotasiPasien % $this->pasiens->count()];
            $this->rotasiPasien++;

            $waktuDaftar = $hari->copy()->startOfDay()->addMinutes($menitDatang);
            $waktuMulai = $hari->copy()->startOfDay()->addMinutes($menitMulai);
            $waktuSelesai = $hari->copy()->startOfDay()->addMinutes($menitSelesai);
            $waktuDipanggil = $waktuMulai->copy()->subMinute();

            if ($waktuDipanggil->lessThan($waktuDaftar)) {
                $waktuDipanggil = $waktuDaftar->copy();
            }

            $baris[] = [
                'pasien_id' => $pasien->id,
                'poli_id' => $poli->id,
                'dokter_id' => $dokters[$server]->id,
                'nomor_antrian' => $kode.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'kode_tiket' => Str::random(32),
                'status' => 'selesai',
                'waktu_daftar' => $waktuDaftar->toDateTimeString(),
                'waktu_dipanggil' => $waktuDipanggil->toDateTimeString(),
                'waktu_mulai_dilayani' => $waktuMulai->toDateTimeString(),
                'waktu_selesai' => $waktuSelesai->toDateTimeString(),
                'estimasi_tunggu_detik' => null,
                'jumlah_panggil' => 1,
                'tanggal' => $hari->toDateString(),
                'created_at' => $waktuDaftar->toDateTimeString(),
                'updated_at' => $waktuSelesai->toDateTimeString(),
            ];
        }

        foreach (array_chunk($baris, 200) as $potongan) {
            Antrian::query()->insert($potongan);
        }

        return count($baris);
    }

    /**
     * Antrian hari ini dengan semua status penting:
     * menunggu, dipanggil, dilayani, selesai, tidak_hadir, dan batal.
     */
    private function buatAntrianHariIni(): void
    {
        $hariIni = Carbon::today();

        Antrian::query()
            ->whereIn('pasien_id', $this->pasiens->pluck('id'))
            ->padaTanggal($hariIni)
            ->delete();

        $polis = $this->poliAktifDenganDokter();

        if ($polis->isEmpty()) {
            $this->command?->warn('Antrian hari ini tidak dibuat: belum ada poli aktif yang punya dokter aktif.');

            return;
        }

        foreach ($polis as $indeks => $poli) {
            $dokters = $poli->dokters;
            $urut = Antrian::query()
                ->where('poli_id', $poli->id)
                ->padaTanggal($hariIni)
                ->count() + 1;

            foreach ($this->skenarioHariIni($indeks) as $data) {
                $nomor = $poli->kode_poli.str_pad((string) $urut, 3, '0', STR_PAD_LEFT);
                $urut++;

                $waktuDaftar = $this->waktuLalu($data['daftar']);
                $waktuDipanggil = $data['dipanggil'] !== null ? $this->waktuLalu($data['dipanggil']) : null;
                $waktuMulai = $data['mulai'] !== null ? $this->waktuLalu($data['mulai']) : null;
                $waktuSelesai = $data['selesai'] !== null ? $this->waktuLalu($data['selesai']) : null;

                // Jaga urutan waktu tetap masuk akal walaupun now() sangat pagi.
                if ($waktuDipanggil && $waktuDipanggil->lessThan($waktuDaftar)) {
                    $waktuDipanggil = $waktuDaftar->copy();
                }

                if ($waktuMulai && $waktuDipanggil && $waktuMulai->lessThan($waktuDipanggil)) {
                    $waktuMulai = $waktuDipanggil->copy();
                }

                if ($waktuSelesai && $waktuMulai && $waktuSelesai->lessThan($waktuMulai)) {
                    $waktuSelesai = $waktuMulai->copy()->addMinutes(5);
                }

                $dokter = $data['dokter'] !== null
                    ? $dokters[$data['dokter'] % $dokters->count()]
                    : null;

                $pasien = $this->pasiens[$this->rotasiPasien % $this->pasiens->count()];
                $this->rotasiPasien++;

                $posisiMenunggu = Antrian::query()
                    ->untukPoli($poli->id)
                    ->padaTanggal($hariIni)
                    ->menunggu()
                    ->count();

                $antrian = Antrian::query()->create([
                    'pasien_id' => $pasien->id,
                    'poli_id' => $poli->id,
                    'dokter_id' => $dokter?->id,
                    'nomor_antrian' => $nomor,
                    'status' => $data['status'],
                    'waktu_daftar' => $waktuDaftar,
                    'waktu_dipanggil' => $waktuDipanggil,
                    'waktu_mulai_dilayani' => $waktuMulai,
                    'waktu_selesai' => $waktuSelesai,
                    'estimasi_tunggu_detik' => $data['status'] === 'menunggu' ? ($posisiMenunggu + 1) * 600 : null,
                    'jumlah_panggil' => in_array($data['status'], ['dipanggil', 'dilayani', 'selesai', 'tidak_hadir'], true) ? 1 : 0,
                    'tanggal' => $hariIni->toDateString(),
                ]);

                $this->ringkasanAntrian[] = [
                    'poli' => $poli->nama_poli,
                    'nomor' => $antrian->nomor_antrian,
                    'status' => $antrian->status,
                    'pasien' => $pasien->nama,
                    'dokter' => $dokter?->nama ?? '-',
                ];
            }
        }

        $this->buatLimpahan($polis->first(), $hariIni);
    }

    /**
     * Satu antrian limpahan dari kemarin, supaya bisa diuji bahwa limpahan
     * muncul paling depan walaupun nomor barunya lebih besar.
     */
    private function buatLimpahan(?Poli $poli, Carbon $hariIni): void
    {
        if (! $poli || $poli->dokters->isEmpty() || Carbon::yesterday()->isSunday()) {
            return;
        }

        $pasien = $this->pasiens->last();

        $sumber = Antrian::query()->create([
            'pasien_id' => $pasien->id,
            'poli_id' => $poli->id,
            'nomor_antrian' => $poli->kode_poli.'099',
            'status' => 'menunggu',
            'waktu_daftar' => Carbon::yesterday()->setTime(9, 0),
            'estimasi_tunggu_detik' => 600,
            'jumlah_panggil' => 0,
            'tanggal' => Carbon::yesterday()->toDateString(),
        ]);

        $sumber->update(['dialihkan_at' => $hariIni->copy()->setTime(0, 1)]);

        $nomorBaru = $poli->kode_poli.str_pad(
            (string) (Antrian::query()
                ->where('poli_id', $poli->id)
                ->padaTanggal($hariIni)
                ->count() + 1),
            3,
            '0',
            STR_PAD_LEFT
        );

        $baru = Antrian::query()->create([
            'pasien_id' => $pasien->id,
            'poli_id' => $poli->id,
            'nomor_antrian' => $nomorBaru,
            'status' => 'menunggu',
            'waktu_daftar' => $hariIni->copy()->startOfDay()->addSeconds(2),
            'estimasi_tunggu_detik' => 600,
            'jumlah_panggil' => 0,
            'tanggal' => $hariIni->toDateString(),
            'tanggal_asal' => Carbon::yesterday()->toDateString(),
            'nomor_lama' => $sumber->nomor_antrian,
        ]);

        $this->ringkasanAntrian[] = [
            'poli' => $poli->nama_poli,
            'nomor' => $baru->nomor_antrian,
            'status' => 'menunggu (limpahan)',
            'pasien' => $pasien->nama,
            'dokter' => '-',
        ];
    }

    /**
     * @return array<int, array{status: string, daftar: int, dipanggil: ?int, mulai: ?int, selesai: ?int, dokter: ?int}>
     */
    private function skenarioHariIni(int $indeks): array
    {
        $skenario = [
            // Poli pertama: lengkap, semua status ada.
            [
                ['status' => 'selesai', 'daftar' => 150, 'dipanggil' => 138, 'mulai' => 135, 'selesai' => 120, 'dokter' => 0],
                ['status' => 'selesai', 'daftar' => 130, 'dipanggil' => 115, 'mulai' => 112, 'selesai' => 100, 'dokter' => 1],
                ['status' => 'dilayani', 'daftar' => 45, 'dipanggil' => 25, 'mulai' => 23, 'selesai' => null, 'dokter' => 0],
                ['status' => 'dipanggil', 'daftar' => 30, 'dipanggil' => 4, 'mulai' => null, 'selesai' => null, 'dokter' => 1],
                ['status' => 'tidak_hadir', 'daftar' => 60, 'dipanggil' => 35, 'mulai' => null, 'selesai' => null, 'dokter' => 0],
                ['status' => 'batal', 'daftar' => 70, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
                ['status' => 'menunggu', 'daftar' => 25, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
                ['status' => 'menunggu', 'daftar' => 15, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
                ['status' => 'menunggu', 'daftar' => 5, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
            ],
            // Poli kedua: satu selesai + dua menunggu.
            [
                ['status' => 'selesai', 'daftar' => 120, 'dipanggil' => 108, 'mulai' => 105, 'selesai' => 92, 'dokter' => 0],
                ['status' => 'menunggu', 'daftar' => 20, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
                ['status' => 'menunggu', 'daftar' => 10, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
            ],
            // Poli ketiga: satu dipanggil + dua menunggu.
            [
                ['status' => 'dipanggil', 'daftar' => 40, 'dipanggil' => 3, 'mulai' => null, 'selesai' => null, 'dokter' => 0],
                ['status' => 'menunggu', 'daftar' => 20, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
                ['status' => 'menunggu', 'daftar' => 5, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
            ],
            // Poli keempat: selesai + sedang dilayani + menunggu.
            [
                ['status' => 'selesai', 'daftar' => 100, 'dipanggil' => 88, 'mulai' => 85, 'selesai' => 70, 'dokter' => 0],
                ['status' => 'dilayani', 'daftar' => 50, 'dipanggil' => 30, 'mulai' => 28, 'selesai' => null, 'dokter' => 1],
                ['status' => 'menunggu', 'daftar' => 15, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
            ],
        ];

        return $skenario[$indeks] ?? [
            ['status' => 'menunggu', 'daftar' => 20, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
            ['status' => 'menunggu', 'daftar' => 10, 'dipanggil' => null, 'mulai' => null, 'selesai' => null, 'dokter' => null],
        ];
    }

    private function waktuLalu(int $menit): Carbon
    {
        $sekarang = Carbon::now();
        $batasAwal = $sekarang->copy()->startOfDay()->addMinute();
        $waktu = $sekarang->copy()->subMinutes($menit);

        return $waktu->lessThan($batasAwal) ? $batasAwal : $waktu;
    }

    private function cetakRingkasan(): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->newLine();
        $this->command->info('DATA UJI SIAP DIGUNAKAN');
        $this->command->line('Semua akun di bawah hanya untuk pengujian lokal.');

        $this->command->newLine();
        $this->command->table(
            ['Peran', 'Email', 'Password', 'Catatan'],
            $this->akun
        );

        $this->command->newLine();
        $this->command->info('Pasien uji (pakai NIK saat ambil nomor antrian):');
        $this->command->table(
            ['No. RM', 'Nama', 'NIK', 'No. HP'],
            $this->pasiens->map(fn (Pasien $p) => [$p->no_rm, $p->nama, $p->nik, $p->no_hp])->all()
        );

        $this->command->newLine();
        $this->command->info('Antrian hari ini:');
        $this->command->table(
            ['Poli', 'Nomor', 'Status', 'Pasien', 'Dokter'],
            $this->ringkasanAntrian
        );

        $this->command->newLine();
        $this->command->line("Riwayat simulasi: {$this->jumlahRiwayat} baris (14 hari terakhir, Minggu dilewati).");

        if (Poli::query()->where('aktif', true)->whereDoesntHave('dokters', fn ($q) => $q->where('aktif', true))->exists()) {
            $this->command->warn('Ada poli aktif yang belum punya dokter aktif; poli itu dilewati.');
        }

        $this->command->newLine();
        $this->command->line('Verifikasi otomatis: php artisan test --filter=UjiCobaAlurTest');
        $this->command->line('Bersihkan data uji   : php artisan db:seed --class=HapusDataUjiSeeder');
    }
}
