<?php

namespace App\Http\Controllers;

use App\Jobs\KirimNotifikasiPanggilan;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Pasien;
use App\Models\Poli;
use App\Services\JamOperasional;
use App\Services\QueueTheoryService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AntrianController extends Controller
{
    /**
     * Dashboard petugas: ringkasan jumlah antrian per poli hari ini.
     */
    public function index(): View
    {
        $polis = Poli::where('aktif', true)
            ->withCount([
                'antrians as menunggu_count' => fn ($q) => $q->hariIni()->menunggu(),
                'antrians as selesai_count' => fn ($q) => $q->hariIni()->selesai(),
                'antrians as dipanggil_count' => fn ($q) => $q->hariIni()->where('status', 'dipanggil'),
            ])
            ->get();

        return view('antrian.index', compact('polis'));
    }

    /**
     * Halaman kelola satu poli untuk petugas.
     */
    public function kelola(Poli $poli): View
    {
        $daftarMenunggu = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->menunggu()
            ->with('pasien')
            ->orderBy('waktu_daftar')
            ->get();

        $sedangDilayani = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('pasien', 'dokter')
            ->orderByDesc('waktu_dipanggil')
            ->get();

        // Pasien yang dipanggil tetapi tidak hadir: masih bisa dipanggil ulang sampai jam layanan selesai
        $tidakHadir = Antrian::untukPoli($poli->id)
            ->hariIni()
            ->where('status', 'tidak_hadir')
            ->with('pasien', 'dokter')
            ->orderBy('waktu_dipanggil')
            ->get();

        $dokters = Dokter::where('poli_id', $poli->id)->where('aktif', true)->get();
        $layananBerjalan = JamOperasional::layananBerjalan();

        return view('antrian.kelola', compact('poli', 'daftarMenunggu', 'sedangDilayani', 'tidakHadir', 'dokters', 'layananBerjalan'));
    }

    /**
     * Dashboard dokter yang sedang login.
     */
    public function dokterDashboard(Request $request): View
    {
        $dokter = $request->user()->dokter;

        abort_if(! $dokter, 403, 'Akun ini tidak terhubung dengan data dokter.');

        $antrianSaya = Antrian::where('dokter_id', $dokter->id)
            ->hariIni()
            ->whereIn('status', ['dipanggil', 'dilayani'])
            ->with('pasien')
            ->orderBy('waktu_dipanggil')
            ->get();

        $riwayatSelesaiHariIni = Antrian::where('dokter_id', $dokter->id)
            ->hariIni()
            ->selesai()
            ->with('pasien')
            ->orderByDesc('waktu_selesai')
            ->limit(10)
            ->get();

        return view('antrian.dokter', compact('dokter', 'antrianSaya', 'riwayatSelesaiHariIni'));
    }

    /**
     * Form ambil nomor antrian (pasien / kios).
     */
    public function create(): View
    {
        $polis = Poli::where('aktif', true)->get();

        $pendaftaranDibuka = JamOperasional::pendaftaranDibuka();
        $pesanTutup = $pendaftaranDibuka ? null : JamOperasional::pesanPendaftaranTutup();

        return view('antrian.create', compact('polis', 'pendaftaranDibuka', 'pesanTutup'));
    }

    /**
     * Proses ambil nomor antrian. Pasien dicari berdasarkan NIK; kalau belum ada, dibuat baru.
     */
    public function store(Request $request): RedirectResponse
    {
        if (! JamOperasional::pendaftaranDibuka()) {
            return back()->withInput()->with('error', JamOperasional::pesanPendaftaranTutup());
        }

        $validated = $request->validate([
            'poli_id' => ['required', 'exists:polis,id'],
            'nik' => ['required', 'digits:16'],
            'nama' => ['required', 'string', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
        ]);

        // Transaksi + lock supaya nomor antrian tidak bentrok kalau dua pasien daftar bersamaan
        $antrian = DB::transaction(function () use ($validated) {
            $pasien = Pasien::where('nik', $validated['nik'])->first()
                ?? $this->daftarkanPasienBaru($validated);

            // Pasien lama: perbarui nomor HP kalau diisi ulang, supaya WhatsApp sampai ke nomor terbaru
            if (! empty($validated['no_hp']) && $pasien->no_hp !== $validated['no_hp']) {
                $pasien->update(['no_hp' => $validated['no_hp']]);
            }

            $poli = Poli::lockForUpdate()->findOrFail($validated['poli_id']);

            // Termasuk antrian limpahan, sehingga nomor pendaftar baru melanjutkan nomor limpahan
            $nomorUrut = Antrian::where('poli_id', $poli->id)
                ->padaTanggal(now())
                ->lockForUpdate()
                ->count() + 1;

            $nomorAntrian = $poli->kode_poli . str_pad((string) $nomorUrut, 3, '0', STR_PAD_LEFT);

            $posisiMenunggu = Antrian::untukPoli($poli->id)->hariIni()->menunggu()->count();

            $estimasiDetik = $this->hitungEstimasi($poli->id, $posisiMenunggu + 1);

            return Antrian::create([
                'pasien_id' => $pasien->id,
                'poli_id' => $poli->id,
                'nomor_antrian' => $nomorAntrian,
                'status' => 'menunggu',
                'waktu_daftar' => now(),
                'estimasi_tunggu_detik' => $estimasiDetik,
                'tanggal' => now()->toDateString(),
            ]);
        });

        return redirect()
            ->route('antrian.tiket', $antrian->kode_tiket)
            ->with('success', 'Nomor antrian berhasil dibuat.');
    }

        /**
     * Membuat pasien baru. Nomor RM dibentuk dari ID pasien (selalu unik), bukan dari jumlah baris,
     * sehingga dua pendaftar bersamaan atau pasien yang pernah dihapus tidak membuat nomor bentrok.
     */
    private function daftarkanPasienBaru(array $data): Pasien
    {
        try {
            $pasien = Pasien::create([
                'no_rm' => 'TMP-' . Str::uuid(), // sementara, langsung diganti di bawah (masih dalam transaksi)
                'nama' => $data['nama'],
                'nik' => $data['nik'],
                'no_hp' => $data['no_hp'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
                'jenis_kelamin' => $data['jenis_kelamin'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // NIK yang sama baru saja didaftarkan permintaan lain (misalnya klik ganda).
            // lockForUpdate membuat query membaca data terbaru, bukan snapshot transaksi ini.
            return Pasien::where('nik', $data['nik'])->lockForUpdate()->firstOrFail();
        }

        $pasien->update([
            'no_rm' => 'RM-' . now()->format('ymd') . '-' . str_pad((string) $pasien->id, 4, '0', STR_PAD_LEFT),
        ]);

        return $pasien;
    }

    /**
     * Tiket antrian setelah berhasil daftar.
     */

    

    public function tiket(Antrian $antrian): View
    {
        $antrian->load('poli', 'pasien');

        $posisiDidepan = Antrian::untukPoli($antrian->poli_id)
            ->hariIni()
            ->menunggu()
            ->where('waktu_daftar', '<', $antrian->waktu_daftar)
            ->count();

        return view('antrian.tiket', compact('antrian', 'posisiDidepan'));
    }

    /**
     * Layar antrian untuk TV ruang tunggu. Data realtime ada di komponen Livewire AntrianDisplay.
     */
    public function display(Poli $poli): View
    {
        return view('antrian.display', compact('poli'));
    }

    /**
     * Petugas memanggil antrian berikutnya (FIFO) dan pasien diberi tahu lewat WhatsApp.
     * Antrian limpahan dari hari sebelumnya otomatis berada paling depan.
     */
    public function panggilBerikutnya(Request $request, Poli $poli): RedirectResponse
    {
        if (! JamOperasional::layananBerjalan()) {
            return back()->with('error', JamOperasional::pesanLayananSelesai());
        }

                // Dokter harus aktif dan bertugas di poli ini (sama seperti pilihan di halaman kelola)
        $validated = $request->validate([
            'dokter_id' => [
                'required',
                Rule::exists('dokters', 'id')->where('poli_id', $poli->id)->where('aktif', true),
            ],
        ]);

        // Lock baru bekerja di dalam transaksi: dua petugas yang menekan "Panggil" bersamaan
        // tidak akan lagi mendapat pasien yang sama.
        $antrian = DB::transaction(function () use ($poli, $validated) {
            $antrian = Antrian::untukPoli($poli->id)
                ->hariIni()
                ->menunggu()
                ->orderBy('waktu_daftar')
                ->lockForUpdate()
                ->first();

            $antrian?->update([
                'status' => 'dipanggil',
                'dokter_id' => $validated['dokter_id'],
                'waktu_dipanggil' => now(),
                'jumlah_panggil' => 1,
            ]);

            return $antrian;
        });

        if (! $antrian) {
            return back()->with('error', 'Tidak ada pasien yang menunggu.');
        }

        KirimNotifikasiPanggilan::dispatchAfterResponse($antrian->id);

        return back()->with('success', "Memanggil nomor antrian {$antrian->nomor_antrian}");
    }

    /**
     * Panggil ulang pasien yang sudah dipanggil tetapi belum datang, atau yang ditandai tidak hadir.
     * Boleh berulang kali selama jam layanan belum selesai.
     */
    public function panggilUlang(Request $request, Antrian $antrian): RedirectResponse
    {
        if (! $antrian->tanggal->isSameDay(now())) {
            return back()->with('error', 'Hanya antrian hari ini yang bisa dipanggil ulang.');
        }

        if (! in_array($antrian->status, ['dipanggil', 'tidak_hadir'], true)) {
            return back()->with('error', 'Antrian ini tidak berada dalam status yang bisa dipanggil ulang.');
        }

        if (! JamOperasional::layananBerjalan()) {
            return back()->with('error', JamOperasional::pesanLayananSelesai());
        }

        $validated = $request->validate([
            'dokter_id' => [
                'nullable',
                Rule::exists('dokters', 'id')->where('poli_id', $antrian->poli_id)->where('aktif', true),
            ],
        ]);

        $dokterId = $validated['dokter_id'] ?? $antrian->dokter_id;

        if (! $dokterId) {
            return back()->with('error', 'Pilih dokter yang bertugas sebelum memanggil ulang.');
        }

        $antrian->update([
            'status' => 'dipanggil',
            'dokter_id' => $dokterId,
            'waktu_dipanggil' => now(),
            'waktu_mulai_dilayani' => null,
            'jumlah_panggil' => (int) $antrian->jumlah_panggil + 1,
        ]);

        KirimNotifikasiPanggilan::dispatchAfterResponse($antrian->id);

        return back()->with('success', "Memanggil ulang nomor {$antrian->nomor_antrian} (panggilan ke-{$antrian->jumlah_panggil})");
    }

    /**
     * Dokter mulai melayani pasien yang sudah dipanggil.
     */
    public function mulaiLayani(Request $request, Antrian $antrian): RedirectResponse
    {
        $this->pastikanBolehMengelola($request, $antrian);

        $diubah = Antrian::whereKey($antrian->id)
            ->where('status', 'dipanggil')
            ->update(['status' => 'dilayani', 'waktu_mulai_dilayani' => now()]);

        if (! $diubah) {
            return back()->with('error', 'Hanya pasien yang sudah dipanggil yang bisa mulai dilayani.');
        }

        return back()->with('success', 'Pelayanan dimulai.');
    }

    /**
     * Dokter menyelesaikan pelayanan.
     */
    public function selesaiLayani(Request $request, Antrian $antrian): RedirectResponse
    {
        $this->pastikanBolehMengelola($request, $antrian);

        $diubah = Antrian::whereKey($antrian->id)
            ->where('status', 'dilayani')
            ->update(['status' => 'selesai', 'waktu_selesai' => now()]);

        if (! $diubah) {
            return back()->with('error', 'Hanya pasien yang sedang dilayani yang bisa diselesaikan.');
        }

        return back()->with('success', 'Pelayanan selesai.');
    }

    /**
     * Tandai pasien tidak hadir saat dipanggil. Masih bisa dipanggil ulang selama jam layanan.
     */
    public function tidakHadir(Request $request, Antrian $antrian): RedirectResponse
    {
        $this->pastikanBolehMengelola($request, $antrian);

        $diubah = Antrian::whereKey($antrian->id)
            ->where('status', 'dipanggil')
            ->update(['status' => 'tidak_hadir']);

        if (! $diubah) {
            return back()->with('error', 'Hanya pasien yang sudah dipanggil yang bisa ditandai tidak hadir.');
        }

        return back()->with('success', "Antrian {$antrian->nomor_antrian} ditandai tidak hadir.");
    }

    /**
     * Dokter hanya boleh mengubah pasien yang ditugaskan kepadanya.
     * Admin dan petugas boleh mengelola semua antrian.
     */
    private function pastikanBolehMengelola(Request $request, Antrian $antrian): void
    {
        $user = $request->user();

        if (! $user->isDokter()) {
            return;
        }

        $milikDokterIni = $antrian->dokter_id
            && Dokter::whereKey($antrian->dokter_id)->where('user_id', $user->id)->exists();

        abort_unless($milikDokterIni, 403, 'Pasien ini bukan pasien Anda.');
    }

    /**
     * Estimasi waktu tunggu dari data historis 14 hari terakhir; kalau data belum cukup, pakai perkiraan sederhana.
     */
    private function hitungEstimasi(int $poliId, int $posisiAntrian): ?int
    {
        try {
            // Parameter (lambda, mu, c) dihitung dari 14 hari data dan cukup diperbarui tiap 10 menit,
            // supaya setiap pendaftaran tidak perlu memuat ribuan baris antrian lama.
            $parameter = Cache::remember("antrian.parameter.{$poliId}", now()->addMinutes(10), function () use ($poliId) {
                $hitung = QueueTheoryService::dariData(
                    poliId: $poliId,
                    dari: now()->subDays(14),
                    sampai: now()->subDay(),
                );

                return ['lambda' => $hitung->lambda, 'mu' => $hitung->mu, 'c' => $hitung->c];
            });

            $service = new QueueTheoryService($parameter['lambda'], $parameter['mu'], $parameter['c']);

            if (! $service->stabil()) {
                // Sistem overload: jangan beri estimasi yang menyesatkan
                return null;
            }

            return $service->estimasiTungguDetik($posisiAntrian);
        } catch (\InvalidArgumentException) {
            $dokterAktif = Dokter::where('poli_id', $poliId)->where('aktif', true)->count();
            $dokterAktif = max(1, $dokterAktif);

            return (int) (($posisiAntrian / $dokterAktif) * 10 * 60);
        }
    }
}
