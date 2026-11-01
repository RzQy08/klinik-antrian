<?php

namespace App\Livewire\Admin;

use App\Models\Antrian;
use App\Models\Dokter;
use App\Models\Poli;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar dokter berbentuk tabel yang tetap ringan walau datanya ratusan:
 * pencarian, saringan, dan urutan dikerjakan di database, hasilnya dibagi per halaman,
 * dan hanya kolom yang dipakai tampilan yang diambil.
 *
 * PENTING: aksi Livewire tidak melewati middleware role di routes/web.php,
 * jadi setiap aksi yang mengubah data memeriksa peran admin sendiri (pastikanAdmin()).
 */
class DokterManager extends Component
{
    use WithPagination;

    private const STATUS = ['semua', 'aktif', 'nonaktif'];
    private const AKUN = ['semua', 'ada', 'tanpa'];
    private const URUTAN = ['nama', 'poli', 'terbaru'];
    private const PER_HALAMAN = [10, 25, 50];

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'poli', except: '')]
    public string $poliId = '';

    #[Url(as: 'status', except: 'semua')]
    public string $status = 'semua';

    #[Url(as: 'akun', except: 'semua')]
    public string $akun = 'semua';

    #[Url(as: 'urut', except: 'nama')]
    public string $urut = 'nama';

    #[Url(as: 'tampil', except: 10)]
    public int $perHalaman = 10;

    public ?int $hapusId = null;

    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    // Setiap saringan berubah, kembali ke halaman pertama
    public function updatingCari(): void { $this->resetPage(); }
    public function updatingPoliId(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingAkun(): void { $this->resetPage(); }
    public function updatingUrut(): void { $this->resetPage(); }
    public function updatingPerHalaman(): void { $this->resetPage(); }

    public function updated(string $nama): void
    {
        // Nilai dari URL atau browser tidak dipercaya begitu saja
        if (! in_array($this->status, self::STATUS, true)) { $this->status = 'semua'; }
        if (! in_array($this->akun, self::AKUN, true)) { $this->akun = 'semua'; }
        if (! in_array($this->urut, self::URUTAN, true)) { $this->urut = 'nama'; }
        if (! in_array($this->perHalaman, self::PER_HALAMAN, true)) { $this->perHalaman = 10; }
        if ($this->poliId !== '' && ! ctype_digit($this->poliId)) { $this->poliId = ''; }
    }

    public function resetFilter(): void
    {
        $this->reset('cari', 'poliId', 'status', 'akun', 'urut');
        $this->resetPage();
    }

    public function toggleAktif(int $id): void
    {
        $this->pastikanAdmin();

        $dokter = Dokter::findOrFail($id);

        if ($dokter->aktif && Antrian::where('dokter_id', $dokter->id)->hariIni()->whereIn('status', ['dipanggil', 'dilayani'])->exists()) {
            $this->toast('error', "{$dokter->nama} sedang menangani pasien. Selesaikan dulu sebelum menonaktifkan.");

            return;
        }

        $dokter->update(['aktif' => ! $dokter->aktif]);

        $this->toast('success', $dokter->aktif
            ? "{$dokter->nama} diaktifkan dan bisa dipilih saat memanggil pasien."
            : "{$dokter->nama} dinonaktifkan.");
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->pastikanAdmin();

        $this->hapusId = Dokter::findOrFail($id)->id;
    }

    public function batalHapus(): void
    {
        $this->hapusId = null;
    }

    public function hapus(): void
    {
        $this->pastikanAdmin();

        $dokter = $this->hapusId ? Dokter::find($this->hapusId) : null;
        $this->hapusId = null;

        if (! $dokter) {
            return;
        }

        // Jaga integritas data historis, sama seperti DokterController::destroy()
        if ($dokter->antrians()->exists()) {
            $this->toast('error', "{$dokter->nama} punya riwayat antrian dan tidak bisa dihapus. Nonaktifkan saja.");

            return;
        }

        $nama = $dokter->nama;
        $dokter->delete();

        $this->toast('success', "{$nama} berhasil dihapus.");
    }

    public function render()
    {
        $kata = trim($this->cari);

        $dokters = Dokter::query()
            ->select(['dokters.id', 'dokters.poli_id', 'dokters.user_id', 'dokters.nama', 'dokters.no_sip', 'dokters.aktif', 'dokters.created_at'])
            ->with(['poli:id,nama_poli,kode_poli', 'user:id,email', 'jadwalPraktiks:id,dokter_id,hari'])
            ->withExists('antrians')
            ->when($this->poliId !== '', fn ($q) => $q->where('poli_id', (int) $this->poliId))
            ->when($this->status === 'aktif', fn ($q) => $q->where('aktif', true))
            ->when($this->status === 'nonaktif', fn ($q) => $q->where('aktif', false))
            ->when($this->akun === 'ada', fn ($q) => $q->whereNotNull('user_id'))
            ->when($this->akun === 'tanpa', fn ($q) => $q->whereNull('user_id'))
            ->when($kata !== '', function ($q) use ($kata) {
                // Karakter khusus LIKE dicari apa adanya, bukan sebagai wildcard
                $aman = addcslashes($kata, '%_\\');

                $q->where(fn ($w) => $w->where('nama', 'like', '%'.$aman.'%')->orWhere('no_sip', 'like', '%'.$aman.'%'));
            })
            ->when($this->urut === 'poli', fn ($q) => $q->orderBy(Poli::select('nama_poli')->whereColumn('polis.id', 'dokters.poli_id')))
            ->when($this->urut === 'terbaru', fn ($q) => $q->orderByDesc('dokters.id'))
            ->orderBy('dokters.nama')
            ->paginate($this->perHalaman);

        // Satu query agregat untuk semua angka ringkasan
        $hitung = Dokter::query()
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN aktif = 1 THEN 1 ELSE 0 END) as aktif, SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as tanpa_akun')
            ->first();

        return view('livewire.admin.dokter-manager', [
            'dokters' => $dokters,
            'polis' => Poli::orderBy('nama_poli')->get(['id', 'nama_poli']),
            'ringkasan' => [
                'total' => (int) $hitung->total,
                'aktif' => (int) $hitung->aktif,
                'tanpa_akun' => (int) $hitung->tanpa_akun,
                'tanpa_jadwal' => Dokter::whereDoesntHave('jadwalPraktiks')->count(),
            ],
            'hapus' => $this->hapusId ? Dokter::find($this->hapusId) : null,
            'adaFilter' => $kata !== '' || $this->poliId !== '' || $this->status !== 'semua' || $this->akun !== 'semua',
        ]);
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
