<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Log aktivitas untuk admin: siapa masuk, siapa membuka atau mengunduh data pasien, siapa mengubah akun.
 */
class LogAktivitasController extends Controller
{
    public const PER_HALAMAN = [10, 25, 50, 100];

    public function __invoke(Request $request): View
    {
        $v = $request->validate([
            'aksi' => ['nullable', 'in:'.implode(',', array_keys(LogAktivitas::AKSI))],
            'peran' => ['nullable', 'in:admin,petugas,dokter'],
            'cari' => ['nullable', 'string', 'max:80'],
            'per' => ['nullable', 'integer', 'in:'.implode(',', self::PER_HALAMAN)],
        ]);

        $per = (int) ($v['per'] ?? 25);
        $cari = trim($v['cari'] ?? '');
        $like = '%'.addcslashes($cari, '%_\\').'%';

        $data = LogAktivitas::query()
            ->when(! empty($v['aksi']), fn ($q) => $q->where('aksi', $v['aksi']))
            ->when(! empty($v['peran']), fn ($q) => $q->where('peran', $v['peran']))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama_pengguna', 'like', $like)
                ->orWhere('keterangan', 'like', $like)
                ->orWhere('ip', 'like', $like)))
            ->orderByDesc('id')
            ->paginate($per)
            ->withQueryString();

        return view('admin.log-aktivitas', [
            'data' => $data,
            'aksi' => $v['aksi'] ?? null,
            'peran' => $v['peran'] ?? null,
            'cari' => $cari,
            'per' => $per,
            'perHalaman' => self::PER_HALAMAN,
        ]);
    }
}
