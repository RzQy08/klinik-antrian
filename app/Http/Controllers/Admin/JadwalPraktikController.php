<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\JadwalPraktik;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JadwalPraktikController extends Controller
{
    private const HARI_VALID = ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'];

    public function index(Dokter $dokter): View
    {
        // Editor mingguan ditangani komponen Livewire Admin\JadwalEditor.
        // Urutan hari tidak lagi memakai FIELD() (hanya ada di MySQL), jadi jalan di database apa pun.
        return view('admin.dokter.jadwal', compact('dokter'));
    }

    public function store(Request $request, Dokter $dokter): RedirectResponse
    {
        $validated = $request->validate([
            'hari' => ['required', 'in:' . implode(',', self::HARI_VALID)],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'kuota' => ['required', 'integer', 'min:1', 'max:200'],
        ]);

        // Cegah dokter punya 2 jadwal di hari yang sama (data ambigu)
        if ($dokter->jadwalPraktiks()->where('hari', $validated['hari'])->exists()) {
            return back()->with('error', 'Dokter ini sudah punya jadwal di hari tersebut. Hapus dulu jadwal lama kalau mau ganti.');
        }

        $dokter->jadwalPraktiks()->create($validated);

        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function destroy(JadwalPraktik $jadwal): RedirectResponse
    {
        $dokterId = $jadwal->dokter_id;
        $jadwal->delete();

        return redirect()
            ->route('admin.dokter.jadwal.index', $dokterId)
            ->with('success', 'Jadwal berhasil dihapus.');
    }
}
