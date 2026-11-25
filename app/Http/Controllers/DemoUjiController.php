<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Pasien;
use App\Models\Poli;
use App\Services\JamOperasional;
use App\Support\AksesLokal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alat uji coba untuk panel DEV. Sama seperti masuk cepat: hanya menjawab untuk permintaan dari
 * komputer developer sendiri (lihat AksesLokal), selain itu 404.
 */
class DemoUjiController extends Controller
{
    /** Saklar "abaikan jam operasional": daftar dan panggil bisa kapan saja, termasuk hari libur. */
    public function modeUji(Request $request): RedirectResponse
    {
        abort_unless(AksesLokal::diizinkan($request), 404);

        $nyala = ! JamOperasional::modeUji();
        $nyala ? Cache::forever(JamOperasional::KUNCI_MODE_UJI, true) : Cache::forget(JamOperasional::KUNCI_MODE_UJI);

        return back()->with('success', $nyala
            ? 'Mode uji menyala: pendaftaran dan pemanggilan bisa kapan saja.'
            : 'Mode uji mati: jam operasional berlaku lagi.');
    }

    /** Menambah satu pasien fiktif ke antrian hari ini (NIK diawali 9999 supaya mudah dikenali). */
    public function pasienUji(Request $request): RedirectResponse
    {
        abort_unless(AksesLokal::diizinkan($request), 404);

        $poli = ($request->filled('poli_id') ? Poli::where('aktif', true)->find($request->integer('poli_id')) : null)
            ?? Poli::where('aktif', true)->inRandomOrder()->first();

        if (! $poli) {
            return back()->with('error', 'Belum ada poli aktif.');
        }

        $antrian = DB::transaction(function () use ($poli) {
            do {
                $nik = '9999'.str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
            } while (Pasien::where('nik', $nik)->exists());

            $pasien = Pasien::create([
                'no_rm' => 'TMP-'.Str::uuid(),
                'nama' => 'Pasien Uji '.strtoupper(Str::random(3)),
                'nik' => $nik,
                'no_hp' => null,
            ]);
            $pasien->update(['no_rm' => 'RM-'.now()->format('ymd').'-'.str_pad((string) $pasien->id, 4, '0', STR_PAD_LEFT)]);

            $poli = Poli::lockForUpdate()->findOrFail($poli->id);
            $urut = Antrian::where('poli_id', $poli->id)->padaTanggal(now())->lockForUpdate()->count() + 1;

            return Antrian::create([
                'pasien_id' => $pasien->id,
                'poli_id' => $poli->id,
                'nomor_antrian' => $poli->kode_poli.str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
                'status' => 'menunggu',
                'waktu_daftar' => now(),
                'tanggal' => now()->toDateString(),
            ]);
        });

        return back()->with('success', "Pasien uji terdaftar: {$antrian->nomor_antrian} ({$poli->nama_poli}).");
    }
}
