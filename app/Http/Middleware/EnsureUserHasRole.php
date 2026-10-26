<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Batasi akses route berdasarkan role user yang login.
     *
     * Pemakaian di route: ->middleware('role:admin,petugas')
     * artinya route itu cuma bisa diakses user dengan role admin ATAU petugas.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
