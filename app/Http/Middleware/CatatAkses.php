<?php

namespace App\Http\Middleware;

use App\Support\Aktivitas;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat siapa yang membuka atau mengunduh data pasien: ->middleware('aktivitas:ekspor_csv')
 */
class CatatAkses
{
    public function handle(Request $request, Closure $next, string $aksi): Response
    {
        $response = $next($request);

        // Pencarian langsung mengirim satu permintaan per ketukan; cukup dicatat sekali per 30 detik per pengguna
        if ($request->ajax() && ! Cache::add('aktivitas.ajax.'.$request->user()?->id.'.'.$aksi, 1, 30)) {
            return $response;
        }

        if ($response->getStatusCode() < 400) {
            $filter = collect($request->only(['dari', 'sampai', 'poli_id', 'status', 'q']))->filter()
                ->map(fn ($v, $k) => $k.'='.Aktivitas::samarkan((string) $v))->implode(', ');

            Aktivitas::catat($aksi, $filter !== '' ? $filter : null);
        }

        return $response;
    }
}
