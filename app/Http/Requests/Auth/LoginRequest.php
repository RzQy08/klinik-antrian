<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Batas panjang mencegah input raksasa membebani hashing password
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Batas percobaan masuk. Tiga lapis supaya tebakan password tidak bisa disebar:
     * per email dan IP, per IP saja (satu penyerang mencoba banyak akun), dan per email saja
     * (banyak IP menyerang satu akun).
     *
     * @return array<string, array{0: int, 1: int}> kunci => [maksimum percobaan, detik]
     */
    private function batasan(): array
    {
        $email = Str::transliterate(Str::lower((string) $this->input('email')));

        return [
            'login|'.$email.'|'.$this->ip() => [5, 300],
            'login-ip|'.$this->ip() => [25, 600],
            'login-akun|'.$email => [12, 900],
        ];
    }

    /**
     * Masuk dengan email dan password. Pesan gagal selalu sama, baik email tidak terdaftar
     * maupun password salah, dan waktu prosesnya disamakan agar akun tidak bisa ditebak.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $email = (string) $this->input('email');
        $password = (string) $this->input('password');

        // Samakan waktu proses bila akun tidak ada (hash palsu bercost sama dengan hash asli)
        if (! User::where('email', $email)->exists()) {
            Hash::check($password, '$2y$12$95cxnhPWJxquG3XQqxdRuu1k6nss3OVxQYiE/Z5iOiObbd/.mplJq');
        }

        // Tanpa "ingat saya": komputer rumah sakit dipakai bergantian
        if (! Auth::attempt(['email' => $email, 'password' => $password, fn ($q) => $q->whereNull('nonaktif_at')], false)) {
            foreach ($this->batasan() as $kunci => [, $detik]) {
                RateLimiter::hit($kunci, $detik);
            }

            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        foreach ($this->batasan() as $kunci => [$maks]) {
            if (! RateLimiter::tooManyAttempts($kunci, $maks)) {
                continue;
            }

            event(new Lockout($this));

            $detik = RateLimiter::availableIn($kunci);

            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.max(1, (int) ceil($detik / 60)).' menit.',
            ]);
        }
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
