<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\User;
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
     * Informasi penting untuk admin. Daftar kosong berarti tidak ada yang perlu ditindaklanjuti.
     *
     * @return array<int, array{tone: string, ikon: string, judul: string, teks: string, url: ?string, aksi: ?string}>
     */
    private function perhatian(): array
    {
        $daftar = [];

        $permintaan = \App\Models\PermintaanJadwal::menunggu()->count();

        if ($permintaan > 0) {
            $daftar[] = [
                'tone' => 'info', 'ikon' => 'calendar2-check',
                'judul' => $permintaan.' permintaan ganti jadwal dokter',
                'teks' => null,
                'url' => route('admin.penjadwalan', 'dokter'), 'aksi' => 'Tinjau',
            ];
        }

        // Percobaan masuk yang mencurigakan dalam 24 jam terakhir
        $login = \App\Models\LogAktivitas::where('created_at', '>=', now()->subDay())
            ->whereIn('aksi', ['login_gagal', 'login_terkunci'])
            ->selectRaw('aksi, COUNT(*) as n')->groupBy('aksi')->pluck('n', 'aksi');

        if (($login['login_terkunci'] ?? 0) > 0 || ($login['login_gagal'] ?? 0) >= 10) {
            $daftar[] = [
                'tone' => 'warning', 'ikon' => 'shield-exclamation',
                'judul' => ($login['login_gagal'] ?? 0).' percobaan masuk gagal dalam 24 jam',
                'teks' => ($login['login_terkunci'] ?? 0) > 0 ? $login['login_terkunci'].' kali dikunci sementara' : null,
                'url' => route('admin.log-aktivitas', ['aksi' => 'login_gagal']), 'aksi' => 'Lihat log',
            ];
        }

        $poliTanpaDokter = Poli::where('aktif', true)
            ->whereDoesntHave('dokters', fn ($q) => $q->where('aktif', true))
            ->pluck('nama_poli');

        if ($poliTanpaDokter->isNotEmpty()) {
            $daftar[] = [
                'tone' => 'danger', 'ikon' => 'exclamation-octagon',
                'judul' => $poliTanpaDokter->count().' poli belum punya dokter aktif',
                'teks' => $poliTanpaDokter->implode(', '),
                'url' => route('admin.dokter.create'), 'aksi' => 'Tambah dokter',
            ];
        }

        $dokterTanpaAkun = Dokter::where('aktif', true)->whereNull('user_id')->count();

        if ($dokterTanpaAkun > 0) {
            $daftar[] = [
                'tone' => 'warning', 'ikon' => 'person-exclamation',
                'judul' => $dokterTanpaAkun.' dokter belum punya akun login',
                'teks' => null,
                'url' => route('admin.dokter.index'), 'aksi' => 'Kelola',
            ];
        }

        return $daftar;
    }
}
