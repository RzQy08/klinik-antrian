<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-2 mb-1">Tambah dokter</h1>
        <p class="text-body-secondary mb-0">Isi data dokter. Akun login bisa dibuat sekarang atau nanti.</p>
    </x-slot>

    @include('partials.errors')

    <form method="POST" action="{{ route('admin.dokter.store') }}" class="df-layout">
        @csrf

        <section class="ad-card df-main">
            <header class="ad-card-head"><h2><i class="bi bi-person-badge me-2 text-primary"></i>Data dokter</h2></header>
            @include('admin.dokter.partials.fields', ['dokter' => null])

            <hr class="df-sep">

            <div x-data="{ buat: {{ old('buat_akun') ? 'true' : 'false' }} }">
                <label class="df-toggle">
                    <input type="checkbox" name="buat_akun" value="1" x-model="buat">
                    <span class="df-toggle-track"><span></span></span>
                    <span class="fw-semibold">Buatkan akun login untuk dokter ini</span>
                </label>
                <p class="small text-body-secondary mt-1 mb-0">Dengan akun, dokter bisa masuk sendiri untuk memulai dan menyelesaikan pelayanan.</p>

                <div class="df-grid mt-3" x-show="buat" x-collapse>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" autocomplete="off" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div x-data="{ lihat: false }">
                        <label for="password" class="form-label">Password</label>
                        <div class="df-pass">
                            <input id="password" name="password" :type="lihat ? 'text' : 'password'" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                            <button type="button" @click="lihat = ! lihat" :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'"><i class="bi" :class="lihat ? 'bi-eye-slash' : 'bi-eye'"></i></button>
                        </div>
                        <div class="form-text">Minimal 8 karakter.</div>
                        @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </section>

        <aside class="ad-card df-side">
            <header class="ad-card-head"><h2>Langkah berikutnya</h2></header>
            <ol class="df-steps">
                <li><span>1</span>Simpan data dokter</li>
                <li><span>2</span>Atur jadwal praktik mingguan</li>
                <li><span>3</span>Dokter siap dipanggil di antrian</li>
            </ol>
            <div class="df-actions">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-circle me-1"></i>Simpan dokter</button>
                <a href="{{ route('admin.dokter.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </aside>
    </form>
</x-app-layout>
