<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dokter;
use App\Models\Poli;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DokterController extends Controller
{
    public function index(): View
    {
        $dokters = Dokter::with('poli', 'user')->orderBy('nama')->get();

        return view('admin.dokter.index', compact('dokters'));
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
            'password' => ['required_if:buat_akun,1', 'nullable', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            $userId = null;

            if ($request->boolean('buat_akun')) {
                $user = User::create([
                    'name' => $validated['nama'],
                    'email' => $validated['email'],
                    'password' => bcrypt($validated['password']),
                    'role' => 'dokter',
                ]);

                $userId = $user->id;
            }

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
        ]);

        $dokter->update([
            'poli_id' => $validated['poli_id'],
            'nama' => $validated['nama'],
            'no_sip' => $validated['no_sip'] ?? null,
            'aktif' => $request->boolean('aktif'),
        ]);

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
}
