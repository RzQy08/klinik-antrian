<?php

namespace App\Livewire\Admin;

use App\Models\HariLibur;
use App\Models\JadwalRumahSakit;
use App\Models\Pengaturan;
use App\Services\JamOperasional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * Jadwal rumah sakit: saklar pembatasan jam, jam buka tiap hari, dan daftar tanggal libur.
 * Aksi Livewire tidak melewati middleware role, jadi setiap aksi memeriksa admin sendiri.
 */
class JadwalRumahSakitManager extends Component
{
    public bool $aktif = false;

    /** @var array<string, array{buka: bool, pendaftaran_buka: string, pendaftaran_tutup: string, layanan_selesai: string}> */
    public array $hari = [];

    public string $liburTanggal = '';
    public string $liburKeterangan = '';

    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    public function mount(): void
    {
        $this->muat();
    }

    public function simpan(): void
    {
        $this->pastikanAdmin();
        $this->resetErrorBag();
        $galat = false;

        foreach (array_keys(JadwalRumahSakit::HARI) as $nama) {
            $d = $this->hari[$nama] ?? [];

            if (! ($d['buka'] ?? false)) {
                continue;
            }

            $v = Validator::make($d, [
                'pendaftaran_buka' => ['required', 'date_format:H:i'],
                'pendaftaran_tutup' => ['required', 'date_format:H:i', 'after:pendaftaran_buka'],
                'layanan_selesai' => ['required', 'date_format:H:i', 'after_or_equal:pendaftaran_tutup'],
            ], [
                'pendaftaran_tutup.after' => 'Pendaftaran tutup harus setelah pendaftaran buka.',
                'layanan_selesai.after_or_equal' => 'Layanan selesai tidak boleh lebih awal dari pendaftaran tutup.',
            ], ['pendaftaran_buka' => 'pendaftaran buka', 'pendaftaran_tutup' => 'pendaftaran tutup', 'layanan_selesai' => 'layanan selesai']);

            if ($v->fails()) {
                foreach ($v->errors()->messages() as $kolom => $pesan) {
                    $this->addError("hari.{$nama}.{$kolom}", $pesan[0]);
                }
                $galat = true;
            }
        }

        if ($galat) {
            $this->toast('error', 'Ada jam yang belum benar. Periksa hari yang ditandai merah.');

            return;
        }

        DB::transaction(function () {
            foreach (array_keys(JadwalRumahSakit::HARI) as $nama) {
                $d = $this->hari[$nama];

                JadwalRumahSakit::updateOrCreate(['hari' => $nama], [
                    'buka' => (bool) $d['buka'],
                    'pendaftaran_buka' => $d['pendaftaran_buka'],
                    'pendaftaran_tutup' => $d['pendaftaran_tutup'],
                    'layanan_selesai' => $d['layanan_selesai'],
                ]);
            }

            Pengaturan::simpan('operasional_aktif', $this->aktif ? '1' : '0');
        });

        JamOperasional::lupakan();
        $this->muat();
        $this->toast('success', 'Jadwal rumah sakit berhasil disimpan dan langsung berlaku.');
    }

    /** Isi cepat, belum tersimpan sampai tombol Simpan ditekan. */
    public function preset(string $nama): void
    {
        $this->pastikanAdmin();

        $buka = match ($nama) {
            'senin-sabtu' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'],
            'senin-jumat' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
            'setiap-hari' => array_keys(JadwalRumahSakit::HARI),
            default => null,
        };

        if ($nama === 'samakan') {
            $acuan = collect($this->hari)->first(fn ($d) => $d['buka']);

            if (! $acuan) {
                $this->toast('error', 'Buka minimal satu hari dulu sebagai acuan.');

                return;
            }

            foreach ($this->hari as $k => $d) {
                if ($d['buka']) {
                    $this->hari[$k] = ['buka' => true] + collect($acuan)->only(['pendaftaran_buka', 'pendaftaran_tutup', 'layanan_selesai'])->all();
                }
            }
            $this->toast('success', 'Jam disamakan. Tekan Simpan untuk menyimpan.');

            return;
        }

        if ($buka === null) {
            return;
        }

        foreach (array_keys(JadwalRumahSakit::HARI) as $k) {
            $this->hari[$k]['buka'] = in_array($k, $buka, true);
        }
        $this->toast('success', 'Hari buka diatur. Tekan Simpan untuk menyimpan.');
    }

    public function tambahLibur(): void
    {
        $this->pastikanAdmin();

        $data = $this->validate([
            'liburTanggal' => ['required', 'date', 'after_or_equal:today', 'unique:hari_libur,tanggal'],
            'liburKeterangan' => ['required', 'string', 'max:120'],
        ], [
            'liburTanggal.required' => 'Pilih tanggalnya.',
            'liburTanggal.after_or_equal' => 'Tanggal libur tidak boleh di masa lalu.',
            'liburTanggal.unique' => 'Tanggal itu sudah ada di daftar libur.',
            'liburKeterangan.required' => 'Isi keterangan libur, misalnya Natal.',
        ]);

        HariLibur::create(['tanggal' => $data['liburTanggal'], 'keterangan' => trim($data['liburKeterangan'])]);

        JamOperasional::lupakan();
        $this->reset('liburTanggal', 'liburKeterangan');
        $this->toast('success', 'Hari libur ditambahkan.');
    }

    public function hapusLibur(int $id): void
    {
        $this->pastikanAdmin();

        HariLibur::whereKey($id)->delete();

        JamOperasional::lupakan();
        $this->toast('success', 'Hari libur dihapus.');
    }

    public function render()
    {
        return view('livewire.admin.jadwal-rumah-sakit-manager', [
            'libur' => HariLibur::where('tanggal', '>=', now()->toDateString())->orderBy('tanggal')->get(),
            'liburLewat' => HariLibur::where('tanggal', '<', now()->toDateString())->count(),
        ]);
    }

    private function muat(): void
    {
        $this->aktif = Pengaturan::ambil('operasional_aktif', config('operasional.aktif') ? '1' : '0') === '1';
        $ada = JadwalRumahSakit::all()->keyBy('hari');
        $this->hari = [];

        foreach (array_keys(JadwalRumahSakit::HARI) as $nama) {
            $j = $ada->get($nama);
            $this->hari[$nama] = [
                'buka' => $j ? (bool) $j->buka : true,
                'pendaftaran_buka' => $j->pendaftaran_buka ?? config('operasional.pendaftaran_buka', '07:00'),
                'pendaftaran_tutup' => $j->pendaftaran_tutup ?? config('operasional.pendaftaran_tutup', '11:00'),
                'layanan_selesai' => $j->layanan_selesai ?? config('operasional.layanan_selesai', '14:00'),
            ];
        }
    }

    private function pastikanAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    private function toast(string $tipe, string $teks): void
    {
        $this->tipePesan = $tipe;
        $this->pesan = $teks;
        $this->pesanId++;
    }
}
