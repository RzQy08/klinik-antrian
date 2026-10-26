<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.poli.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar poli</a>
        <h1 class="h2 mt-3 mb-0">Ubah poli</h1>
    </x-slot>

    <div style="max-width: 36rem">
        @include('partials.errors')

        <form method="POST" action="{{ route('admin.poli.update', $poli) }}" class="card">
            @csrf
            @method('PUT')
            <div class="card-body p-4 d-grid gap-4">
                <div>
                    <label for="nama_poli" class="form-label">Nama poli</label>
                    <input id="nama_poli" name="nama_poli" type="text" required class="form-control" value="{{ old('nama_poli', $poli->nama_poli) }}">
                </div>
                <div>
                    <label for="kode_poli" class="form-label">Kode poli</label>
                    <input id="kode_poli" name="kode_poli" type="text" maxlength="5" required class="form-control" value="{{ old('kode_poli', $poli->kode_poli) }}">
                    <div class="form-text">Mengubah kode hanya berpengaruh ke nomor antrian berikutnya.</div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="aktif" id="aktif" value="1" @checked($poli->aktif)>
                    <label class="form-check-label" for="aktif">Aktif, bisa dipilih pasien saat ambil antrian</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan perubahan</button>
                    <a href="{{ route('admin.poli.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
