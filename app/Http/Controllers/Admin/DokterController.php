<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DokterController extends Controller
{
    public function index(): View
    {
        // Tabel, pencarian, saringan, dan paginasi ditangani komponen Livewire Admin\DokterManager
        return view('admin.dokter.index');
    }

    public function create(): View
    {
        $polis = Poli::orderBy('nama_poli')->get();

        return view('admin.dokter.create', compact('polis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'poli_id' => ['required', 'exists:polis,id'],
            'nama' => ['required', 'string', 'max:255'],
            'no_sip' => ['nullable', 'string', 'max:255'],
            'aktif' => ['nullable', 'boolean'],
            // akun login opsional - kalau diisi, otomatis dibuatkan akun User role dokter
            'buat_akun' => ['nullable', 'boolean'],
            'email' => ['required_if:buat_akun,1', 'nullable', 'email', 'unique:users,email'],
            'password' => ['required_if:buat_akun,1', 'nullable', 'string', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $userId = $request->boolean('buat_akun') ? $this->buatAkun($validated)->id : null;

            Dokter::create([
                'poli_id' => $validated['poli_id'],
                'user_id' => $userId,
                'nama' => $validated['nama'],
                'no_sip' => $validated['no_sip'] ?? null,
                'aktif' => $request->boolean('aktif'),
            ]);
        });

        return redirect()->route('admin.dokter.index')->with('success', 'Dokter berhasil ditambahkan.');
    }

    public function edit(Dokter $dokter): View
    {
        $dokter->load('poli:id,nama_poli', 'user:id,email')->loadCount(['antrians', 'jadwalPraktiks']);
        $polis = Poli::orderBy('nama_poli')->get();

        return view('admin.dokter.edit', compact('dokter', 'polis'));
    }

    public function update(Request $request, Dokter $dokter): RedirectResponse
    {
        $validated = $request->validate([
            'poli_id' => ['required', 'exists:polis,id'],
            'nama' => ['required', 'string', 'max:255'],
            'no_sip' => ['nullable', 'string', 'max:255'],
            'aktif' => ['nullable', 'boolean'],
            // dokter yang belum punya akun boleh dibuatkan sekarang
            'buat_akun' => ['nullable', 'boolean'],
            'email' => ['required_if:buat_akun,1', 'nullable', 'email', 'unique:users,email'],
            'password' => ['required_if:buat_akun,1', 'nullable', 'string', Password::defaults()],
            // dokter yang sudah punya akun boleh diganti passwordnya
            'password_baru' => ['nullable', 'string', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated, $request, $dokter) {
            $userId = $dokter->user_id;

            if (! $userId && $request->boolean('buat_akun')) {
                $userId = $this->buatAkun($validated)->id;
            } elseif ($dokter->user) {
                $perubahan = ['name' => $validated['nama']];

                if (! empty($validated['password_baru'])) {
                    $perubahan['password'] = $validated['password_baru']; // di-hash otomatis oleh cast model User
                }

                $dokter->user->update($perubahan);
            }

            $dokter->update([
                'poli_id' => $validated['poli_id'],
                'user_id' => $userId,
                'nama' => $validated['nama'],
                'no_sip' => $validated['no_sip'] ?? null,
                'aktif' => $request->boolean('aktif'),
            ]);
        });

        return redirect()->route('admin.dokter.index')->with('success', 'Dokter berhasil diperbarui.');
    }

    public function destroy(Dokter $dokter): RedirectResponse
    {
        if ($dokter->antrians()->exists()) {
            return back()->with('error', 'Dokter ini tidak bisa dihapus karena masih punya riwayat antrian. Nonaktifkan saja.');
        }

        $dokter->delete();

        return redirect()->route('admin.dokter.index')->with('success', 'Dokter berhasil dihapus.');
    }

    private function buatAkun(array $data): User
    {
        return User::create([
            'name' => $data['nama'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role' => 'dokter',
        ]);
    }
}
