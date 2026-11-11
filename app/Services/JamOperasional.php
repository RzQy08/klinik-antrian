<?php

namespace App\Services;

use App\Models\HariLibur;
use App\Models\JadwalRumahSakit;
use App\Models\Pengaturan;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Aturan jam operasional rumah sakit. Datanya diatur admin lewat menu Jadwal
 * (jam buka per hari, hari libur, dan saklar pembatasan). Kalau tabelnya belum ada
 * atau saklar pembatasan mati, semuanya dianggap terbuka seperti sebelumnya.
 */
class JamOperasional
{
    private const CACHE = 'jam_operasional.v1';

    private const MEMO = 'jam_operasional.memo';

    private const NAMA_HARI = [1 => 'senin', 2 => 'selasa', 3 => 'rabu', 4 => 'kamis', 5 => 'jumat', 6 => 'sabtu', 7 => 'minggu'];

    // ---------- data ----------

    /** @return array{aktif: bool, hari: array<string, array>, libur: array<string, string>} */
    private static function data(): array
    {
        // satu kali baca per permintaan; ikut hilang bersama instance aplikasi (aman untuk pengujian)
        if (app()->bound(self::MEMO)) {
            return app(self::MEMO);
        }

        return tap(self::muat(), fn ($d) => app()->instance(self::MEMO, $d));
    }

    private static function muat(): array
    {
        try {
            return Cache::rememberForever(self::CACHE, fn () => [
                'aktif' => Pengaturan::ambil('operasional_aktif', config('operasional.aktif') ? '1' : '0') === '1',
                'hari' => JadwalRumahSakit::all()->keyBy('hari')->map->only(['buka', 'pendaftaran_buka', 'pendaftaran_tutup', 'layanan_selesai'])->all(),
                'libur' => HariLibur::where('tanggal', '>=', now()->subDays(2)->toDateString())->get()
                    ->mapWithKeys(fn ($l) => [$l->tanggal->toDateString() => $l->keterangan])->all(),
            ]);
        } catch (\Throwable) {
            // tabel belum dimigrasi atau cache bermasalah: pakai config lama
            return ['aktif' => (bool) config('operasional.aktif'), 'hari' => [], 'libur' => []];
        }
    }

    /** Panggil setelah admin menyimpan jadwal supaya perubahan langsung berlaku. */
    public static function lupakan(): void
    {
        app()->forgetInstance(self::MEMO);

        try {
            Cache::forget(self::CACHE);
        } catch (\Throwable) {
        }
    }

    // ---------- status ----------

    public static function aktif(): bool
    {
        return self::data()['aktif'];
    }

    /** Alasan rumah sakit tutup pada tanggal itu (libur atau hari tutup mingguan), atau null bila buka. */
    public static function alasanTutup(?CarbonInterface $waktu = null): ?string
    {
        $waktu ??= now();
        $data = self::data();

        if ($libur = $data['libur'][$waktu->toDateString()] ?? null) {
            return $libur;
        }

        $baris = $data['hari'][self::NAMA_HARI[$waktu->dayOfWeekIso]] ?? null;

        if ($baris && ! $baris['buka']) {
            return 'tutup hari '.ucfirst(self::NAMA_HARI[$waktu->dayOfWeekIso]);
        }

        return null;
    }

    public static function tutupHariIni(?CarbonInterface $waktu = null): bool
    {
        return self::aktif() && self::alasanTutup($waktu) !== null;
    }

    /** Jam (HH:MM) untuk hari tertentu. Kunci: pendaftaran_buka, pendaftaran_tutup, layanan_selesai. */
    public static function jam(string $kunci, ?CarbonInterface $waktu = null): string
    {
        $waktu ??= now();
        $baris = self::data()['hari'][self::NAMA_HARI[$waktu->dayOfWeekIso]] ?? null;

        return (string) ($baris[$kunci] ?? config('operasional.'.$kunci));
    }

    public static function pendaftaranDibuka(?CarbonInterface $waktu = null): bool
    {
        if (! self::aktif()) {
            return true;
        }

        $waktu ??= now();

        if (self::alasanTutup($waktu)) {
            return false;
        }

        $jam = $waktu->format('H:i');

        return $jam >= self::jam('pendaftaran_buka', $waktu) && $jam < self::jam('pendaftaran_tutup', $waktu);
    }

    public static function layananBerjalan(?CarbonInterface $waktu = null): bool
    {
        if (! self::aktif()) {
            return true;
        }

        $waktu ??= now();

        if (self::alasanTutup($waktu)) {
            return false;
        }

        return $waktu->format('H:i') < self::jam('layanan_selesai', $waktu);
    }

    // ---------- tampilan mingguan (footer, halaman publik) ----------

    /**
     * Jam buka satu minggu, hari berurutan yang jamnya sama digabung ("Senin - Jumat").
     *
     * @return list<array{label: string, buka: bool, pendaftaran: string, layanan: string, hari_ini: bool}>
     */
    public static function mingguan(): array
    {
        $baris = [];

        foreach (self::NAMA_HARI as $no => $kunci) {
            $d = self::data()['hari'][$kunci] ?? null;
            $buka = $d ? (bool) $d['buka'] : true;

            $baris[] = [
                'nama' => ucfirst($kunci),
                'buka' => $buka,
                'pendaftaran' => $buka ? self::tampil((string) ($d['pendaftaran_buka'] ?? config('operasional.pendaftaran_buka'))).' - '.self::tampil((string) ($d['pendaftaran_tutup'] ?? config('operasional.pendaftaran_tutup'))) : '',
                'layanan' => $buka ? self::tampil((string) ($d['layanan_selesai'] ?? config('operasional.layanan_selesai'))) : '',
                'hari_ini' => now()->dayOfWeekIso === $no,
            ];
        }

        $kelompok = [];

        foreach ($baris as $b) {
            $akhir = count($kelompok) - 1;

            if ($akhir >= 0 && $kelompok[$akhir]['buka'] === $b['buka'] && $kelompok[$akhir]['pendaftaran'] === $b['pendaftaran'] && $kelompok[$akhir]['layanan'] === $b['layanan']) {
                $kelompok[$akhir]['sampai'] = $b['nama'];
                $kelompok[$akhir]['hari_ini'] = $kelompok[$akhir]['hari_ini'] || $b['hari_ini'];

                continue;
            }

            $kelompok[] = $b + ['sampai' => null];
        }

        return array_map(function ($k) {
            $k['label'] = $k['sampai'] ? $k['nama'].' - '.$k['sampai'] : $k['nama'];

            return $k;
        }, $kelompok);
    }

    /** Libur terdekat yang belum lewat (hari ini atau sesudahnya), atau null. */
    public static function liburBerikutnya(): ?array
    {
        $hariIni = now()->toDateString();
        $libur = collect(self::data()['libur'])->filter(fn ($ket, $tgl) => $tgl >= $hariIni)->sortKeys();

        if ($libur->isEmpty()) {
            return null;
        }

        return ['tanggal' => \Carbon\Carbon::parse($libur->keys()->first())->locale('id'), 'keterangan' => $libur->first()];
    }

    // ---------- pesan ----------

    public static function pesanPendaftaranTutup(?CarbonInterface $waktu = null): string
    {
        $waktu ??= now();

        if ($alasan = self::alasanTutup($waktu)) {
            return "Rumah sakit tutup hari ini ({$alasan}). ".self::kalimatBukaBerikutnya($waktu);
        }

        $jam = $waktu->format('H:i');
        $buka = self::tampil(self::jam('pendaftaran_buka', $waktu));
        $tutup = self::tampil(self::jam('pendaftaran_tutup', $waktu));

        if ($jam < self::jam('pendaftaran_buka', $waktu)) {
            return "Pendaftaran antrian belum dibuka. Pendaftaran dibuka mulai pukul {$buka}.";
        }

        return "Pendaftaran antrian hari ini sudah ditutup pukul {$tutup}. ".self::kalimatBukaBerikutnya($waktu);
    }

    public static function pesanLayananSelesai(): string
    {
        if ($alasan = self::alasanTutup()) {
            return "Rumah sakit tutup hari ini ({$alasan}), jadi belum ada pelayanan.";
        }

        $selesai = self::tampil(self::jam('layanan_selesai'));

        return "Jam layanan sudah selesai (pukul {$selesai}). Antrian yang belum terpanggil akan dipindahkan ke hari berikutnya.";
    }

    /** Kalimat "Silakan datang kembali ..." berdasarkan hari buka terdekat setelah hari ini. */
    public static function kalimatBukaBerikutnya(?CarbonInterface $waktu = null): string
    {
        $waktu ??= now();
        $sebelumBuka = ! self::alasanTutup($waktu) && $waktu->format('H:i') < self::jam('pendaftaran_buka', $waktu);

        for ($i = $sebelumBuka ? 0 : 1; $i <= 21; $i++) {
            $hari = $waktu->copy()->addDays($i);

            if (! self::alasanTutup($hari)) {
                $nama = $i === 0 ? 'hari ini' : ($i === 1 ? 'besok' : ucfirst(self::NAMA_HARI[$hari->dayOfWeekIso]).', '.$hari->locale('id')->translatedFormat('j F'));

                return "Silakan datang kembali {$nama} mulai pukul ".self::tampil(self::jam('pendaftaran_buka', $hari)).'.';
            }
        }

        return 'Silakan hubungi rumah sakit untuk informasi jadwal.';
    }

    /** 07:00 menjadi 07.00, sesuai kebiasaan penulisan jam di Indonesia. */
    public static function tampil(string $jam): string
    {
        return str_replace(':', '.', $jam);
    }
}
