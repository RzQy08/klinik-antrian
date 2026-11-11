<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\User;
use App\Services\JamOperasional;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Halaman utama admin: ringkasan hari ini, status tiap poli, tren 7 hari,
     * hal yang perlu perhatian, dan akses cepat ke semua fitur.
     */
    public function index(): View
    {
        $ringkasan = [
            'jumlah_poli' => Poli::count(),
            'jumlah_dokter' => Dokter::count(),
            'jumlah_user' => User::count(),
        ];

        // Satu query untuk semua hitungan status hari ini
        $perStatus = Antrian::hariIni()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $hariIni = [
            'total' => (int) $perStatus->sum(),
            'menunggu' => (int) ($perStatus['menunggu'] ?? 0),
            'ditangani' => (int) (($perStatus['dipanggil'] ?? 0) + ($perStatus['dilayani'] ?? 0)),
            'selesai' => (int) ($perStatus['selesai'] ?? 0),
            'tidak_hadir' => (int) ($perStatus['tidak_hadir'] ?? 0),
        ];

        // Rata-rata waktu tunggu aktual hari ini (tanpa limpahan), dalam menit
        $tunggu = Antrian::sqlSelisihDetik('waktu_daftar', 'waktu_mulai_dilayani');
        $rata = Antrian::hariIni()
            ->whereNull('tanggal_asal')
            ->whereNotNull('waktu_mulai_dilayani')
            ->selectRaw("AVG({$tunggu}) as rata")
            ->value('rata');
        $rataTungguMenit = $rata !== null ? (int) round($rata / 60) : null;

        // Status tiap poli aktif
        $polis = Poli::where('aktif', true)
            ->orderBy('kode_poli')
            ->withCount([
                'antrians as menunggu_count' => fn ($q) => $q->hariIni()->menunggu(),
                'antrians as ditangani_count' => fn ($q) => $q->hariIni()->whereIn('status', ['dipanggil', 'dilayani']),
                'antrians as selesai_count' => fn ($q) => $q->hariIni()->selesai(),
            ])
            ->get();

        // Tren pasien selesai 7 hari terakhir (tanpa limpahan)
        $awal = Carbon::today()->subDays(6);
        $hitung = Antrian::antaraTanggal($awal, Carbon::today())
            ->selesai()
            ->whereNull('tanggal_asal')
            ->selectRaw('tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->get()
            ->mapWithKeys(fn ($r) => [Carbon::parse($r->tanggal)->toDateString() => (int) $r->jumlah]);

        $tren = collect(range(6, 0))->map(function (int $mundur) use ($hitung) {
            $tanggal = Carbon::today()->subDays($mundur);

            return [
                'label' => $tanggal->copy()->locale('id')->translatedFormat('D'),
                'tanggal' => $tanggal->copy()->locale('id')->translatedFormat('j M'),
                'jumlah' => (int) $hitung->get($tanggal->toDateString(), 0),
                'hari_ini' => $mundur === 0,
            ];
        });

        // Aktivitas pendaftaran terbaru hari ini
        $terbaru = Antrian::hariIni()
            ->with('pasien:id,nama', 'poli:id,kode_poli,nama_poli')
            ->latest('waktu_daftar')
            ->limit(6)
            ->get();

        return view('admin.dashboard', [
            'ringkasan' => $ringkasan,
            'hariIni' => $hariIni,
            'rataTungguMenit' => $rataTungguMenit,
            'polis' => $polis,
            'tren' => $tren,
            'trenMaks' => max(1, (int) $tren->max('jumlah')),
            'terbaru' => $terbaru,
            'perhatian' => $this->perhatian(),
        ]);
    }

    /**
     * Hal yang sebaiknya diperiksa admin. Daftar kosong berarti semuanya beres.
     *
     * @return array<int, array{tone: string, ikon: string, judul: string, teks: string, url: ?string, aksi: ?string}>
     */
    private function perhatian(): array
    {
        $daftar = [];

        // Kesalahan konfigurasi yang membuka data pasien selalu paling atas
        foreach (app(\App\Services\PemeriksaKeamanan::class)->untukDashboard() as $k) {
            $daftar[] = [
                'tone' => $k['level'] === 'bahaya' ? 'danger' : 'warning', 'ikon' => 'shield-exclamation',
                'judul' => $k['judul'], 'teks' => $k['teks'],
                'url' => null, 'aksi' => null,
            ];
        }

        $poliTanpaDokter = Poli::where('aktif', true)
            ->whereDoesntHave('dokters', fn ($q) => $q->where('aktif', true))
            ->pluck('nama_poli');

        if ($poliTanpaDokter->isNotEmpty()) {
            $daftar[] = [
                'tone' => 'danger', 'ikon' => 'exclamation-octagon',
                'judul' => $poliTanpaDokter->count().' poli belum punya dokter aktif',
                'teks' => $poliTanpaDokter->implode(', ').'. Pasien tidak bisa dipanggil sebelum ada dokter.',
                'url' => route('admin.dokter.create'), 'aksi' => 'Tambah dokter',
            ];
        }

        $dokterTanpaAkun = Dokter::where('aktif', true)->whereNull('user_id')->count();

        if ($dokterTanpaAkun > 0) {
            $daftar[] = [
                'tone' => 'warning', 'ikon' => 'person-exclamation',
                'judul' => $dokterTanpaAkun.' dokter belum punya akun login',
                'teks' => 'Dokter tanpa akun tidak bisa memulai dan menyelesaikan pelayanan sendiri.',
                'url' => route('admin.dokter.index'), 'aksi' => 'Kelola dokter',
            ];
        }

        if (config('whatsapp.driver') !== 'fonnte') {
            $daftar[] = [
                'tone' => 'info', 'ikon' => 'whatsapp',
                'judul' => 'Notifikasi WhatsApp belum aktif',
                'teks' => 'WA_DRIVER masih "'.config('whatsapp.driver').'", jadi pesan hanya dicatat di log dan tidak sampai ke pasien.',
                'url' => null, 'aksi' => null,
            ];
        }

        if (! JamOperasional::aktif()) {
            $daftar[] = [
                'tone' => 'info', 'ikon' => 'clock-history',
                'judul' => 'Pembatasan jam operasional nonaktif',
                'teks' => 'Pendaftaran dan pemanggilan bisa dilakukan kapan saja. Atur OPERASIONAL_AKTIF=true bila ingin dibatasi.',
                'url' => null, 'aksi' => null,
            ];
        }

        return $daftar;
    }
}
