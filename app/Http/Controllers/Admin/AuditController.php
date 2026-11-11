<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Jejak audit untuk admin: siapa masuk, siapa membuka atau mengunduh data pasien, siapa mengubah akun.
 */
class AuditController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate(['aksi' => ['nullable', 'in:'.implode(',', array_keys(AuditLog::AKSI))]]);

        $data = AuditLog::query()
            ->when($request->filled('aksi'), fn ($q) => $q->where('aksi', $request->input('aksi')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.audit', ['data' => $data, 'aksi' => $request->input('aksi')]);
    }
}
