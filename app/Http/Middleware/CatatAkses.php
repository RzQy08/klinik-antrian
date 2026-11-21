<?php

namespace App\Http\Middleware;

use App\Support\Aktivitas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat siapa yang membuka atau mengunduh data pasien: ->middleware('aktivitas:ekspor_csv')
 */
class CatatAkses
{
    public function handle(Request $request, Closure $next, string $aksi): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            $filter = collect($request->only(['dari', 'sampai', 'poli_id', 'status', 'q']))->filter()
                ->map(fn ($v, $k) => $k.'='.Aktivitas::samarkan((string) $v))->implode(', ');

            Aktivitas::catat($aksi, $filter !== '' ? $filter : null);
        }

        return $response;
    }
}
