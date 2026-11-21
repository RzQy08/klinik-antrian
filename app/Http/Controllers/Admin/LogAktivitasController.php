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
    public function __invoke(Request $request): View
    {
        $request->validate(['aksi' => ['nullable', 'in:'.implode(',', array_keys(LogAktivitas::AKSI))]]);

        $data = LogAktivitas::query()
            ->when($request->filled('aksi'), fn ($q) => $q->where('aksi', $request->input('aksi')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.log-aktivitas', ['data' => $data, 'aksi' => $request->input('aksi')]);
    }
}
