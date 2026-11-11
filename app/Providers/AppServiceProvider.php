<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AksesLokal;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Di produksi semua tautan dan aset dibuat dengan https
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->kuncikanDebug();
        $this->pakaiAsetBuildLewatTunnel();
        $this->aturPassword();
        $this->catatJejakAudit();
    }

    /**
     * Halaman error mode debug menampilkan isi .env (kunci aplikasi, password database).
     * Kesalahan manusia yang paling sering: APP_DEBUG=true terbawa ke server atau ikut terbagi lewat tunnel.
     * Jadi debug hanya boleh menyala untuk permintaan dari komputer sendiri, tidak di produksi.
     */
    private function kuncikanDebug(): void
    {
        if ($this->app->runningInConsole() || ! config('app.debug')) {
            return;
        }

        if ($this->app->isProduction() || ! AksesLokal::diizinkan()) {
            config(['app.debug' => false]);
        }
    }

    /**
     * File public/hot dibuat oleh "npm run dev" dan menunjuk ke localhost:5173 milik developer.
     * Dibuka dari alamat lain (ngrok, port forward, IP jaringan, hosting), browser pengunjung tidak bisa
     * menjangkau alamat itu sehingga CSS dan JS tidak termuat. Untuk host non-lokal, pakai hasil
     * "npm run build" (public/build) selama manifest-nya ada.
     */
    private function pakaiAsetBuildLewatTunnel(): void
    {
        if ($this->app->runningInConsole() || ! is_file(public_path('hot')) || ! is_file(public_path('build/manifest.json'))) {
            return;
        }

        if (! AksesLokal::hostLokal(request()->getHost())) {
            Vite::useHotFile(storage_path('framework/vite.hot'));
        }
    }

    /**
     * Password staf: minimal 10 karakter, huruf besar dan kecil, angka, simbol.
     * Di produksi juga ditolak kalau sudah pernah bocor di internet (haveibeenpwned, tanpa mengirim password utuh).
     */
    private function aturPassword(): void
    {
        Password::defaults(function () {
            $aturan = Password::min(10)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction() ? $aturan->uncompromised() : $aturan;
        });
    }

    /** Jejak audit: siapa masuk, siapa membuat atau mengubah akun. */
    private function catatJejakAudit(): void
    {
        Event::listen(Login::class, fn (Login $e) => Audit::catat('login', null, $e->user));
        Event::listen(Logout::class, fn (Logout $e) => $e->user ? Audit::catat('logout', null, $e->user) : null);
        Event::listen(Failed::class, fn (Failed $e) => Audit::catat('login_gagal', 'email: '.mb_strtolower((string) ($e->credentials['email'] ?? '-'))));
        Event::listen(Lockout::class, fn (Lockout $e) => Audit::catat('login_terkunci', 'email: '.mb_strtolower((string) $e->request->input('email', '-'))));
        Event::listen(PasswordReset::class, fn (PasswordReset $e) => Audit::catat('password_reset', null, $e->user));

        User::created(fn (User $u) => Audit::catat('akun_dibuat', "{$u->email} ({$u->role})"));
        User::deleted(fn (User $u) => Audit::catat('akun_dihapus', "{$u->email} ({$u->role})"));
        User::updated(function (User $u) {
            if ($u->wasChanged('password')) {
                Audit::catat('password_diubah', $u->email);
            }

            if ($u->wasChanged('role')) {
                Audit::catat('peran_diubah', "{$u->email}: {$u->getOriginal('role')} menjadi {$u->role}");
            }
        });
    }
}
