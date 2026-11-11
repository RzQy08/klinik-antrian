<?php

namespace App\Http\Controllers;

use App\Models\Dokter;
use App\Models\User;
use App\Support\AksesLokal;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DemoLoginController extends Controller
{
    /**
     * Masuk cepat untuk pengujian. Hanya aktif untuk permintaan dari komputer developer sendiri
     * (APP_ENV=local, tanpa proxy/tunnel, host lokal; lihat AksesLokal). Lewat ngrok, port forward,
     * IP jaringan, atau hosting, route ini menjawab 404 walaupun APP_ENV salah diisi local.
     *
     * Memakai akun yang sudah ada untuk peran tersebut (misalnya hasil DemoSeeder).
     * Akun demo.* baru dibuat hanya kalau belum ada akun sama sekali untuk peran itu.
     */
    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(AksesLokal::diizinkan($request), 404);
        abort_unless(in_array($role, ['admin', 'petugas', 'dokter'], true), 404);

        // Untuk dokter, pilih akun yang sudah terhubung ke data dokter
        $user = User::where('role', $role)
            ->when($role === 'dokter', fn ($q) => $q->whereHas('dokter'))
            ->orderBy('id')
            ->first();

        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => "demo.{$role}@klinik.test"],
                [
                    'name' => 'Demo ' . ucfirst($role),
                    'password' => Str::random(40),
                    'role' => $role,
                ]
            );

            if ($role === 'dokter' && ! $user->dokter()->exists()) {
                $dokter = Dokter::whereNull('user_id')->where('aktif', true)->first();

                if (! $dokter) {
                    return redirect()->route('beranda')
                        ->with('error', 'Belum ada data dokter yang bisa dihubungkan ke akun demo. Jalankan php artisan db:seed atau tambahkan dokter lewat dashboard admin.');
                }

                $dokter->update(['user_id' => $user->id]);
            }
        }

        Auth::login($user);
        Audit::catat('login_demo', $user->email, $user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
