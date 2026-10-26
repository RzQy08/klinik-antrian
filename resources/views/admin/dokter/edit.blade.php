<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-3 mb-0">Ubah dokter</h1>
    </x-slot>

    <div style="max-width: 36rem">
        @include('partials.errors')

        @if ($dokter->user)
            <div class="alert alert-info"><i class="bi bi-person-lock me-1"></i> Akun login: <strong>{{ $dokter->user->email }}</strong>. Email dan password tidak diubah lewat form ini.</div>
        @endif

        <form method="POST" action="{{ route('admin.dokter.update', $dokter) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-body p-4 d-grid gap-4">
                <div>
                    <label for="poli_id" class="form-label">Poli</label>
                    <select id="poli_id" name="poli_id" required class="form-select">
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected(old('poli_id', $dokter->poli_id) == $poli->id)>{{ $poli->nama_poli }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="nama" class="form-label">Nama dokter</label>
                    <input id="nama" name="nama" type="text" required class="form-control" value="{{ old('nama', $dokter->nama) }}">
                </div>
                <div>
                    <label for="no_sip" class="form-label">No. SIP <span class="fw-normal text-body-secondary">(opsional)</span></label>
                    <input id="no_sip" name="no_sip" type="text" class="form-control" value="{{ old('no_sip', $dokter->no_sip) }}">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" @checked($dokter->aktif)>
                    <label class="form-check-label" for="aktif">Aktif</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                    <a href="{{ route('admin.dokter.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
