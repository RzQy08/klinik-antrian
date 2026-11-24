<?php

namespace App\Http\Controllers;

use App\Models\JadwalPraktik;
use App\Models\JadwalRumahSakit;
use App\Models\PermintaanJadwal;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Dokter mengajukan perubahan jadwal praktiknya sendiri (hari, jam, kuota).
 * Perubahan baru berlaku setelah admin menyetujui; sebelum itu jadwal lama tetap aktif.
 */
class PermintaanJadwalController extends Controller
{
    /** Dokter: ajukan perubahan untuk satu hari. */
    public function store(Request $request): RedirectResponse
    {
        $dokter = $request->user()->dokter;
        abort_if(! $dokter, 403, 'Akun ini tidak terhubung dengan data dokter.');

        $data = $request->validate([
            'hari' => ['required', 'string', 'in:'.implode(',', array_keys(JadwalRumahSakit::HARI))],
            'jam_mulai_baru' => ['required', 'date_format:H:i'],
            'jam_selesai_baru' => ['required', 'date_format:H:i', 'after:jam_mulai_baru'],
            'kuota_baru' => ['required', 'integer', 'min:1', 'max:200'],
            'alasan' => ['nullable', 'string', 'max:180'],
        ], [
            'jam_selesai_baru.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $adaMenunggu = PermintaanJadwal::where('dokter_id', $dokter->id)
            ->where('hari', $data['hari'])->menunggu()->exists();

        if ($adaMenunggu) {
            return back()->with('error', 'Sudah ada permintaan untuk hari itu yang masih menunggu persetujuan admin.');
        }

        PermintaanJadwal::create([
            'dokter_id' => $dokter->id,
            'hari' => $data['hari'],
            'jam_mulai_baru' => $data['jam_mulai_baru'],
            'jam_selesai_baru' => $data['jam_selesai_baru'],
            'kuota_baru' => $data['kuota_baru'],
            'alasan' => $data['alasan'] ?? null,
        ]);

        Aktivitas::catat('Mengajukan perubahan jadwal', "{$dokter->nama}: {$data['hari']} {$data['jam_mulai_baru']}-{$data['jam_selesai_baru']}");

        return back()->with('success', 'Permintaan perubahan jadwal dikirim. Jadwal lama tetap berlaku sampai disetujui admin.');
    }

    /** Dokter: batalkan permintaannya sendiri selama masih menunggu. */
    public function batalkan(Request $request, PermintaanJadwal $permintaan): RedirectResponse
    {
        $dokter = $request->user()->dokter;
        abort_if(! $dokter || $permintaan->dokter_id !== $dokter->id, 403);
        abort_unless($permintaan->status === 'menunggu', 422, 'Permintaan ini sudah ditinjau.');

        $permintaan->delete();

        return back()->with('success', 'Permintaan dibatalkan.');
    }

    /** Admin: setujui, lalu terapkan ke jadwal praktik sesungguhnya. */
    public function setujui(Request $request, PermintaanJadwal $permintaan): RedirectResponse
    {
        abort_unless($permintaan->status === 'menunggu', 422, 'Permintaan ini sudah ditinjau.');

        JadwalPraktik::updateOrCreate(
            ['dokter_id' => $permintaan->dokter_id, 'hari' => $permintaan->hari],
            [
                'jam_mulai' => $permintaan->jam_mulai_baru,
                'jam_selesai' => $permintaan->jam_selesai_baru,
                'kuota' => $permintaan->kuota_baru,
            ]
        );

        $permintaan->update([
            'status' => 'disetujui',
            'ditinjau_oleh' => $request->user()->id,
            'ditinjau_pada' => now(),
        ]);

        Aktivitas::catat('Menyetujui perubahan jadwal', "{$permintaan->dokter?->nama}: {$permintaan->hari}");

        return back()->with('success', 'Permintaan disetujui dan jadwal praktik diperbarui.');
    }

    /** Admin: tolak, jadwal lama tidak berubah. */
    public function tolak(Request $request, PermintaanJadwal $permintaan): RedirectResponse
    {
        abort_unless($permintaan->status === 'menunggu', 422, 'Permintaan ini sudah ditinjau.');

        $catatan = $request->validate(['catatan_admin' => ['nullable', 'string', 'max:180']])['catatan_admin'] ?? null;

        $permintaan->update([
            'status' => 'ditolak',
            'ditinjau_oleh' => $request->user()->id,
            'ditinjau_pada' => now(),
            'catatan_admin' => $catatan,
        ]);

        Aktivitas::catat('Menolak perubahan jadwal', "{$permintaan->dokter?->nama}: {$permintaan->hari}");

        return back()->with('success', 'Permintaan ditolak.');
    }
}
