<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-3 mb-0">Tambah dokter</h1>
    </x-slot>

    <div style="max-width: 36rem">
        @include('partials.errors')

        <form method="POST" action="{{ route('admin.dokter.store') }}" class="card">
            @csrf
            <div class="card-body p-4 d-grid gap-4">
                <div>
                    <label for="poli_id" class="form-label">Poli</label>
                    <select id="poli_id" name="poli_id" required class="form-select">
                        <option value="">Pilih poli</option>
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected(old('poli_id') == $poli->id)>{{ $poli->nama_poli }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="nama" class="form-label">Nama dokter</label>
                    <input id="nama" name="nama" type="text" required class="form-control" value="{{ old('nama') }}" placeholder="Contoh: dr. Andi Wijaya">
                </div>
                <div>
                    <label for="no_sip" class="form-label">No. SIP <span class="fw-normal text-body-secondary">(opsional)</span></label>
                    <input id="no_sip" name="no_sip" type="text" class="form-control" value="{{ old('no_sip') }}">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" checked>
                    <label class="form-check-label" for="aktif">Aktif</label>
                </div>

                <hr class="my-0">

                <div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="buat_akun" id="buat_akun" value="1" data-bs-toggle="collapse" data-bs-target="#akunFields" aria-controls="akunFields" @checked(old('buat_akun'))>
                        <label class="form-check-label fw-medium" for="buat_akun">Sekalian buatkan akun login untuk dokter ini</label>
                    </div>
                    <div id="akunFields" class="collapse {{ old('buat_akun') ? 'show' : '' }}">
                        <div class="d-grid gap-3 pt-3">
                            <div>
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control" value="{{ old('email') }}">
                            </div>
                            <div>
                                <label for="password" class="form-label">Password</label>
                                <input id="password" name="password" type="password" autocomplete="new-password" class="form-control">
                                <div class="form-text">Minimal 8 karakter.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan dokter</button>
                    <a href="{{ route('admin.dokter.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
