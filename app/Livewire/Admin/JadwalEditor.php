<?php

namespace App\Livewire\Admin;

use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Editor jadwal praktik mingguan satu dokter. Semua hari diedit sekaligus lalu disimpan sekali.
 * Aksi Livewire tidak melewati middleware role, jadi simpan() dan preset() memeriksa admin sendiri.
 */
class JadwalEditor extends Component
{
    public const HARI = [
        'senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis',
        'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu',
    ];

    #[Locked]
    public int $dokterId;

    /** @var array<string, array{aktif: bool, mulai: string, selesai: string, kuota: int|string}> */
    public array $jadwal = [];

    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    public function mount(Dokter $dokter): void
    {
        $this->dokterId = $dokter->id;
        $this->muat();
    }

    public function simpan(): void
    {
        $this->pastikanAdmin();
        $this->resetErrorBag();

        $galat = false;

        foreach (array_keys(self::HARI) as $hari) {
            $d = $this->jadwal[$hari] ?? [];

            if (! ($d['aktif'] ?? false)) {
                continue;
            }

            $v = Validator::make($d, [
                'mulai' => ['required', 'date_format:H:i'],
                'selesai' => ['required', 'date_format:H:i', 'after:mulai'],
                'kuota' => ['required', 'integer', 'min:1', 'max:200'],
            ], [
                'selesai.after' => 'Jam selesai harus setelah jam mulai.',
                'kuota.min' => 'Kuota minimal 1 pasien.',
                'kuota.max' => 'Kuota maksimal 200 pasien.',
            ], ['mulai' => 'jam mulai', 'selesai' => 'jam selesai', 'kuota' => 'kuota']);

            if ($v->fails()) {
                foreach ($v->errors()->messages() as $kolom => $pesan) {
                    $this->addError("jadwal.{$hari}.{$kolom}", $pesan[0]);
                }
                $galat = true;
            }
        }

        if ($galat) {
            $this->toast('error', 'Ada isian yang belum benar. Periksa hari yang ditandai merah.');

            return;
        }

        DB::transaction(function () {
            foreach (array_keys(self::HARI) as $hari) {
                $d = $this->jadwal[$hari];

                if ($d['aktif'] ?? false) {
                    JadwalPraktik::updateOrCreate(
                        ['dokter_id' => $this->dokterId, 'hari' => $hari],
                        ['jam_mulai' => $d['mulai'], 'jam_selesai' => $d['selesai'], 'kuota' => (int) $d['kuota']]
                    );
                } else {
                    JadwalPraktik::where('dokter_id', $this->dokterId)->where('hari', $hari)->delete();
                }
            }
        });

        $this->muat();
        $this->toast('success', 'Jadwal praktik berhasil disimpan.');
    }

    /** Isi cepat. Hasilnya belum tersimpan sampai tombol Simpan ditekan. */
    public function preset(string $nama): void
    {
        $this->pastikanAdmin();

        $hariAktif = match ($nama) {
            'senin-sabtu' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'],
            'senin-jumat' => ['senin', 'selasa', 'rabu', 'kamis', 'jumat'],
            'kosong' => [],
            default => null,
        };

        if ($nama === 'samakan') {
            $acuan = collect($this->jadwal)->first(fn ($d) => $d['aktif']);

            if (! $acuan) {
                $this->toast('error', 'Aktifkan minimal satu hari dulu sebagai acuan.');

                return;
            }

            foreach ($this->jadwal as $hari => $d) {
                if ($d['aktif']) {
                    $this->jadwal[$hari] = ['aktif' => true, 'mulai' => $acuan['mulai'], 'selesai' => $acuan['selesai'], 'kuota' => $acuan['kuota']];
                }
            }

            $this->toast('success', 'Jam dan kuota disamakan. Tekan Simpan jadwal untuk menyimpan.');

            return;
        }

        if ($hariAktif === null) {
            return;
        }

        foreach (array_keys(self::HARI) as $hari) {
            $this->jadwal[$hari]['aktif'] = in_array($hari, $hariAktif, true);
        }

        $this->toast('success', $nama === 'kosong' ? 'Semua hari dikosongkan. Tekan Simpan jadwal untuk menyimpan.' : 'Hari praktik diatur. Tekan Simpan jadwal untuk menyimpan.');
    }

    public function render()
    {
        $dokter = Dokter::with('poli:id,nama_poli', 'user:id,email')->findOrFail($this->dokterId);
        $tersimpan = JadwalPraktik::where('dokter_id', $this->dokterId)->get(['hari', 'jam_mulai', 'jam_selesai', 'kuota']);

        $jamPerMinggu = $tersimpan->sum(function ($j) {
            [$h1, $m1] = array_map('intval', explode(':', $j->jam_mulai));
            [$h2, $m2] = array_map('intval', explode(':', $j->jam_selesai));

            return max(0, ($h2 * 60 + $m2) - ($h1 * 60 + $m1)) / 60;
        });

        return view('livewire.admin.jadwal-editor', [
            'dokter' => $dokter,
            'ringkasan' => [
                'hari' => $tersimpan->count(),
                'jam' => round($jamPerMinggu, 1),
                'kuota' => (int) $tersimpan->sum('kuota'),
            ],
        ]);
    }

    private function muat(): void
    {
        $ada = JadwalPraktik::where('dokter_id', $this->dokterId)->get()->keyBy('hari');
        $this->jadwal = [];

        foreach (array_keys(self::HARI) as $hari) {
            $j = $ada->get($hari);

            $this->jadwal[$hari] = [
                'aktif' => (bool) $j,
                'mulai' => $j ? substr($j->jam_mulai, 0, 5) : '08:00',
                'selesai' => $j ? substr($j->jam_selesai, 0, 5) : '16:00',
                'kuota' => $j ? (int) $j->kuota : 40,
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
