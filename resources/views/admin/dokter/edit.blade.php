<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-2 mb-1">Ubah dokter</h1>
        <p class="text-body-secondary mb-0">{{ $dokter->nama }}</p>
    </x-slot>

    @include('partials.errors')

    <form method="POST" action="{{ route('admin.dokter.update', $dokter) }}" class="df-layout">
        @csrf
        @method('PUT')

        <section class="ad-card df-main">
            <header class="ad-card-head"><h2><i class="bi bi-person-badge me-2 text-primary"></i>Data dokter</h2></header>
            @include('admin.dokter.partials.fields', ['dokter' => $dokter])

            <hr class="df-sep">

            <header class="ad-card-head"><h2><i class="bi bi-person-lock me-2 text-primary"></i>Akun login</h2></header>

            @if ($dokter->user)
                <div class="df-grid">
                    <div>
                        <label class="form-label">Email akun</label>
                        <input type="email" class="form-control" value="{{ $dokter->user->email }}" readonly aria-readonly="true">
                        <div class="form-text">Email tidak diubah lewat form ini.</div>
                    </div>
                    <div x-data="{ lihat: false }">
                        <label for="password_baru" class="form-label">Password baru <span class="fw-normal text-body-secondary">(opsional)</span></label>
                        <div class="df-pass">
                            <input id="password_baru" name="password_baru" :type="lihat ? 'text' : 'password'" autocomplete="new-password" class="form-control @error('password_baru') is-invalid @enderror" placeholder="Kosongkan bila tidak diganti">
                            <button type="button" @click="lihat = ! lihat" :aria-label="lihat ? 'Sembunyikan password' : 'Tampilkan password'"><i class="bi" :class="lihat ? 'bi-eye-slash' : 'bi-eye'"></i></button>
                        </div>
                        @error('password_baru')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                </div>
            @else
                <div x-data="{ buat: {{ old('buat_akun') ? 'true' : 'false' }} }">
                    <div class="ad-note ad-note-warning mb-3"><i class="bi bi-person-exclamation"></i><div><div class="fw-semibold">Dokter ini belum punya akun login</div><div class="small text-body-secondary">Tanpa akun, dokter tidak bisa memulai dan menyelesaikan pelayanan sendiri.</div></div></div>
                    <label class="df-toggle">
                        <input type="checkbox" name="buat_akun" value="1" x-model="buat">
                        <span class="df-toggle-track"><span></span></span>
                        <span class="fw-semibold">Buatkan akun login sekarang</span>
                    </label>
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
            @endif
        </section>

        <aside class="d-grid gap-3 align-content-start df-side">
            <section class="ad-card">
                <header class="ad-card-head"><h2>Ringkasan</h2></header>
                <dl class="df-facts">
                    <div><dt>Poli</dt><dd>{{ $dokter->poli?->nama_poli }}</dd></div>
                    <div><dt>Status</dt><dd><span class="ap-state {{ $dokter->aktif ? 'is-on' : '' }}">{{ $dokter->aktif ? 'Aktif' : 'Nonaktif' }}</span></dd></div>
                    <div><dt>Hari praktik</dt><dd>{{ $dokter->jadwal_praktiks_count }} hari</dd></div>
                    <div><dt>Pasien dilayani</dt><dd>{{ $dokter->antrians_count }}</dd></div>
                </dl>
                <a href="{{ route('admin.dokter.jadwal.index', $dokter) }}" class="btn btn-outline-primary w-100 mt-3"><i class="bi bi-calendar3 me-1"></i>Atur jadwal praktik</a>
            </section>

            <section class="ad-card">
                <div class="df-actions">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-circle me-1"></i>Simpan perubahan</button>
                    <a href="{{ route('admin.dokter.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </section>
        </aside>
    </form>
</x-app-layout>
