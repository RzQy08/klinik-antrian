<?php

namespace App\Livewire\Admin;

use App\Models\CutiDokter;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use App\Models\JadwalRumahSakit;
use App\Models\PermintaanJadwal;
use Livewire\Component;

/**
 * Ringkasan jadwal praktik semua dokter dalam satu tabel mingguan, plus pengaturan cuti dokter.
 * Jam praktik tiap dokter tetap diedit lewat halaman editor jadwal dokter.
 */
class JadwalDokterManager extends Component
{
    public string $dokterId = '';
    public string $mulai = '';
    public string $selesai = '';
    public string $alasan = '';

    public ?string $pesan = null;
    public string $tipePesan = 'success';
    public int $pesanId = 0;

    public function tambahCuti(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $data = $this->validate([
            'dokterId' => ['required', 'exists:dokters,id'],
            'mulai' => ['required', 'date', 'after_or_equal:today'],
            'selesai' => ['required', 'date', 'after_or_equal:mulai'],
            'alasan' => ['nullable', 'string', 'max:120'],
        ], [
            'dokterId.required' => 'Pilih dokternya.',
            'mulai.required' => 'Isi tanggal mulai cuti.',
            'mulai.after_or_equal' => 'Tanggal mulai tidak boleh di masa lalu.',
            'selesai.required' => 'Isi tanggal selesai cuti.',
            'selesai.after_or_equal' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ]);

        $bentrok = CutiDokter::where('dokter_id', $data['dokterId'])
            ->whereDate('tanggal_mulai', '<=', $data['selesai'])
            ->whereDate('tanggal_selesai', '>=', $data['mulai'])
            ->exists();

        if ($bentrok) {
            $this->addError('mulai', 'Dokter ini sudah punya cuti yang beririsan dengan tanggal itu.');

            return;
        }

        CutiDokter::create([
            'dokter_id' => $data['dokterId'],
            'tanggal_mulai' => $data['mulai'],
            'tanggal_selesai' => $data['selesai'],
            'alasan' => trim((string) $data['alasan']) ?: null,
        ]);

        $this->reset('dokterId', 'mulai', 'selesai', 'alasan');
        $this->toast('success', 'Cuti dokter dicatat. Selama cuti, dokter tidak muncul sebagai dokter bertugas.');
    }

    public function hapusCuti(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        CutiDokter::whereKey($id)->delete();
        $this->toast('success', 'Cuti dihapus.');
    }

    public function setujuiPermintaan(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $p = PermintaanJadwal::where('status', 'menunggu')->find($id);

        if (! $p) {
            $this->toast('error', 'Permintaan ini sudah ditinjau.');

            return;
        }

        JadwalPraktik::updateOrCreate(
            ['dokter_id' => $p->dokter_id, 'hari' => $p->hari],
            ['jam_mulai' => $p->jam_mulai_baru, 'jam_selesai' => $p->jam_selesai_baru, 'kuota' => $p->kuota_baru]
        );

        $p->update(['status' => 'disetujui', 'ditinjau_oleh' => auth()->id(), 'ditinjau_pada' => now()]);

        \App\Support\Aktivitas::catat('Menyetujui perubahan jadwal', "{$p->dokter?->nama}: {$p->hari}");
        $this->toast('success', "Jadwal {$p->dokter?->nama} diperbarui sesuai permintaan.");
    }

    public function tolakPermintaan(int $id): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $p = PermintaanJadwal::where('status', 'menunggu')->find($id);

        if (! $p) {
            $this->toast('error', 'Permintaan ini sudah ditinjau.');

            return;
        }

        $p->update(['status' => 'ditolak', 'ditinjau_oleh' => auth()->id(), 'ditinjau_pada' => now()]);

        \App\Support\Aktivitas::catat('Menolak perubahan jadwal', "{$p->dokter?->nama}: {$p->hari}");
        $this->toast('success', 'Permintaan ditolak. Jadwal dokter tidak berubah.');
    }

    public function render()
    {
        $dokters = Dokter::with('poli:id,nama_poli,kode_poli')->orderBy('poli_id')->orderBy('nama')->get(['id', 'poli_id', 'nama', 'aktif']);
        $jadwal = JadwalPraktik::all()->groupBy('dokter_id')->map(fn ($g) => $g->keyBy('hari'));
        $hariIni = now()->toDateString();

        $cuti = CutiDokter::with('dokter:id,nama')->whereDate('tanggal_selesai', '>=', $hariIni)->orderBy('tanggal_mulai')->get();
        $sedangCuti = $cuti->filter(fn ($c) => $c->tanggal_mulai->toDateString() <= $hariIni)->pluck('dokter_id')->all();

        $permintaan = PermintaanJadwal::with('dokter:id,nama,poli_id')->menunggu()->oldest()->get();

        return view('livewire.admin.jadwal-dokter-manager', [
            'permintaan' => $permintaan,
            'hariLabel' => JadwalRumahSakit::HARI,
            'dokters' => $dokters,
            'jadwal' => $jadwal,
            'cuti' => $cuti,
            'sedangCuti' => $sedangCuti,
            'hariIni' => strtolower(['Sunday' => 'minggu', 'Monday' => 'senin', 'Tuesday' => 'selasa', 'Wednesday' => 'rabu', 'Thursday' => 'kamis', 'Friday' => 'jumat', 'Saturday' => 'sabtu'][now()->format('l')]),
        ]);
    }

    private function toast(string $tipe, string $teks): void
    {
        $this->tipePesan = $tipe;
        $this->pesan = $teks;
        $this->pesanId++;
    }
}
