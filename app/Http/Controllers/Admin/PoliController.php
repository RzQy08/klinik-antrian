<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poli;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PoliController extends Controller
{
    public function index(): View
    {
        // withCount: jumlah dokter dihitung dalam 1 query, bukan 1 query per poli
        $polis = Poli::withCount('dokters')->orderBy('nama_poli')->get();

        return view('admin.poli.index', compact('polis'));
    }

    public function create(): View
    {
        return view('admin.poli.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_poli' => ['required', 'string', 'max:255'],
            'kode_poli' => ['required', 'string', 'max:5', 'unique:polis,kode_poli'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        Poli::create([
            'nama_poli' => $validated['nama_poli'],
            'kode_poli' => strtoupper($validated['kode_poli']),
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('admin.poli.index')->with('success', 'Poli berhasil ditambahkan.');
    }

    public function edit(Poli $poli): View
    {
        return view('admin.poli.edit', compact('poli'));
    }

    public function update(Request $request, Poli $poli): RedirectResponse
    {
        $validated = $request->validate([
            'nama_poli' => ['required', 'string', 'max:255'],
            'kode_poli' => ['required', 'string', 'max:5', 'unique:polis,kode_poli,' . $poli->id],
            'aktif' => ['nullable', 'boolean'],
        ]);

        $poli->update([
            'nama_poli' => $validated['nama_poli'],
            'kode_poli' => strtoupper($validated['kode_poli']),
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('admin.poli.index')->with('success', 'Poli berhasil diperbarui.');
    }

    public function destroy(Poli $poli): RedirectResponse
    {
        // Cegah hapus poli yang masih punya data antrian (jaga integritas data historis)
        if ($poli->antrians()->exists()) {
            return back()->with('error', 'Poli ini tidak bisa dihapus karena masih punya riwayat antrian. Nonaktifkan saja.');
        }

        $poli->delete();

        return redirect()->route('admin.poli.index')->with('success', 'Poli berhasil dihapus.');
    }
}
