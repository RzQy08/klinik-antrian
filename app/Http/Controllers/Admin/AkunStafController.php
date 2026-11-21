<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Aktivitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Akun staf: admin membuat akun petugas atau admin, menonaktifkan akun yang tidak dipakai lagi,
 * dan mengatur ulang password. Akun dokter dibuat dari menu Dokter supaya langsung terhubung ke data dokter.
 */
class AkunStafController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'peran' => ['nullable', Rule::in(['admin', 'petugas', 'dokter'])],
            'cari' => ['nullable', 'string', 'max:80'],
        ]);

        $cari = trim((string) $request->input('cari'));

        $akun = User::query()
            ->when($request->filled('peran'), fn ($q) => $q->where('role', $request->input('peran')))
            ->when($cari !== '', function ($q) use ($cari) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $cari).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderByRaw('nonaktif_at IS NOT NULL')
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.akun', [
            'akun' => $akun,
            'peran' => $request->input('peran'),
            'cari' => $cari,
            'jumlah' => User::selectRaw('role, COUNT(*) as n')->whereNull('nonaktif_at')->groupBy('role')->pluck('n', 'role'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'petugas'])],
            'password' => ['required', 'string', 'max:255', Password::defaults(), 'confirmed'],
        ], [], ['name' => 'nama', 'role' => 'peran']);

        User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'role' => $data['role'],
            'password' => $data['password'],
        ])->forceFill(['email_verified_at' => now()])->save();

        return back()->with('success', 'Akun '.$data['name'].' sudah dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'aksi' => ['required', Rule::in(['nonaktifkan', 'aktifkan', 'password'])],
            'password' => ['required_if:aksi,password', 'nullable', 'string', 'max:255', Password::defaults(), 'confirmed'],
        ]);

        if ($data['aksi'] === 'nonaktifkan') {
            if ($user->is($request->user())) {
                return back()->with('error', 'Anda tidak bisa menonaktifkan akun Anda sendiri.');
            }

            $adminAktifLain = User::where('role', 'admin')->whereNull('nonaktif_at')->whereKeyNot($user->id)->exists();

            if ($user->role === 'admin' && ! $adminAktifLain) {
                return back()->with('error', 'Harus ada minimal satu admin yang aktif.');
            }

            $user->forceFill(['nonaktif_at' => now()])->save();
            Aktivitas::catat('akun_nonaktif', $user->email);

            return back()->with('success', $user->name.' dinonaktifkan dan langsung dikeluarkan.');
        }

        if ($data['aksi'] === 'aktifkan') {
            $user->forceFill(['nonaktif_at' => null])->save();
            Aktivitas::catat('akun_aktif', $user->email);

            return back()->with('success', $user->name.' diaktifkan kembali.');
        }

        $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => null])->save();

        return back()->with('success', 'Password '.$user->name.' sudah diganti. Sesi lamanya otomatis berakhir.');
    }
}
