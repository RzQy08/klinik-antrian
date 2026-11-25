<x-public-layout title="Cek tiket">
    <div class="mx-auto" style="max-width: 26rem">
        <h1 class="h3 mb-1">Buka tiket antrian</h1>
        <p class="text-body-secondary">Tempel kode tiket atau tautan yang tertulis di kartu antrian yang Anda unduh, misalnya saat berganti ponsel.</p>

        <form method="POST" action="{{ route('antrian.cari') }}" class="card card-body">
            @csrf
            <label for="kode" class="form-label fw-semibold">Kode atau tautan tiket</label>
            <input id="kode" name="kode" value="{{ old('kode') }}" required autofocus autocomplete="off" spellcheck="false" class="form-control @error('kode') is-invalid @enderror" placeholder="Contoh: https://.../antrian/tiket/AbC1...">
            @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button class="btn btn-primary mt-3"><i class="bi bi-search me-2" aria-hidden="true"></i>Buka tiket</button>
        </form>
    </div>
</x-public-layout>
