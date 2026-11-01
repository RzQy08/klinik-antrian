<?php

namespace App\Livewire\Admin;

use App\Models\Antrian;
use App\Models\Poli;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Halaman kelola poli yang interaktif: cari, saring, urutkan, aktif/nonaktifkan,
 * dan hapus tanpa memuat ulang halaman.
 *
 * PENTING: aksi Livewire dikirim ke /livewire/update, bukan lewat middleware role di routes/web.php,
 * jadi setiap aksi yang mengubah data wajib memeriksa peran admin sendiri (lihat pastikanAdmin()).
 */
class PoliManager extends Component
{
    private const STATUS = ['semua', 'aktif', 'nonaktif'];
    private const URUTAN = ['kode', 'nama', 'ramai'];

    #[Url(as: 'q', except: '')]
    public string $cari = '';

    #[Url(as: 'status', except: 'semua')]
    public string $status = 'semua';

    #[Url(as: 'urut', except: 'kode')]
    public string $urut = 'kode';

    // poli yang sedang menunggu konfirmasi hapus
    public ?int $hapusId = null;

    // pemberitahuan singkat (toast)
    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    public function updatedStatus(): void
    {
        if (! in_array($this->status, self::STATUS, true)) {
            $this->status = 'semua';
        }
    }

    public function updatedUrut(): void
    {
        if (! in_array($this->urut, self::URUTAN, true)) {
            $this->urut = 'kode';
        }
    }

    public function resetFilter(): void
    {
        $this->reset('cari', 'status', 'urut');
    }

    public function toggleAktif(int $id): void
    {
        $this->pastikanAdmin();

        $poli = Poli::withCount(['antrians as menunggu_hari_ini' => fn ($q) => $q->hariIni()->menunggu()])->findOrFail($id);

        // Poli nonaktif hilang dari daftar kelola petugas, jadi pasien yang masih menunggu akan tertinggal.
        if ($poli->aktif && $poli->menunggu_hari_ini > 0) {
            $this->toast('error', "{$poli->nama_poli} belum bisa dinonaktifkan: masih ada {$poli->menunggu_hari_ini} pasien menunggu hari ini. Layani atau pindahkan dulu.");

            return;
        }

        $poli->update(['aktif' => ! $poli->aktif]);

        $this->toast('success', $poli->aktif
            ? "{$poli->nama_poli} diaktifkan. Pasien sudah bisa memilihnya."
            : "{$poli->nama_poli} dinonaktifkan. Poli ini tidak tampil lagi untuk pasien.");
    }

    public function konfirmasiHapus(int $id): void
    {
        $this->pastikanAdmin();

        $this->hapusId = Poli::findOrFail($id)->id;
    }

    public function batalHapus(): void
    {
        $this->hapusId = null;
    }

    public function hapus(): void
    {
        $this->pastikanAdmin();

        $poli = $this->hapusId ? Poli::find($this->hapusId) : null;
        $this->hapusId = null;

        if (! $poli) {
            return;
        }

        // Jaga integritas data historis, sama seperti PoliController::destroy()
        if ($poli->antrians()->exists()) {
            $this->toast('error', "{$poli->nama_poli} punya riwayat antrian dan tidak bisa dihapus. Nonaktifkan saja.");

            return;
        }

        $nama = $poli->nama_poli;
        $poli->delete();

        $this->toast('success', "{$nama} berhasil dihapus.");
    }

    public function render()
    {
        $kata = trim($this->cari);

        $polis = Poli::query()
            ->withCount([
                'dokters',
                'dokters as dokter_aktif_count' => fn ($q) => $q->where('aktif', true),
                'antrians',
                'antrians as menunggu_count' => fn ($q) => $q->hariIni()->menunggu(),
                'antrians as selesai_count' => fn ($q) => $q->hariIni()->selesai(),
            ])
            ->when($this->status === 'aktif', fn ($q) => $q->where('aktif', true))
            ->when($this->status === 'nonaktif', fn ($q) => $q->where('aktif', false))
            ->when($kata !== '', function ($q) use ($kata) {
                // Karakter khusus LIKE dicari apa adanya, bukan sebagai wildcard
                $aman = addcslashes($kata, '%_\\');

                $q->where(fn ($w) => $w->where('nama_poli', 'like', '%'.$aman.'%')->orWhere('kode_poli', 'like', $aman.'%'));
            })
            ->when($this->urut === 'ramai', fn ($q) => $q->orderByDesc('menunggu_count'))
            ->when($this->urut === 'nama', fn ($q) => $q->orderBy('nama_poli'))
            ->orderBy('kode_poli')
            ->get();

        $total = Poli::count();
        $aktif = Poli::where('aktif', true)->count();

        return view('livewire.admin.poli-manager', [
            'polis' => $polis,
            'ringkasan' => [
                'total' => $total,
                'aktif' => $aktif,
                'nonaktif' => $total - $aktif,
                'tanpa_dokter' => Poli::where('aktif', true)->whereDoesntHave('dokters', fn ($q) => $q->where('aktif', true))->count(),
                'menunggu' => Antrian::hariIni()->menunggu()->count(),
            ],
            'hapus' => $this->hapusId ? Poli::withCount('dokters')->find($this->hapusId) : null,
            'adaFilter' => $kata !== '' || $this->status !== 'semua',
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
