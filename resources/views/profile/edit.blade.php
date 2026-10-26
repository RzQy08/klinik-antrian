<x-app-layout>
    <x-slot name="header">
        <h1 class="h2 mb-1">Profil</h1>
        <p class="text-body-secondary mb-0">Perbarui data akun dan password.</p>
    </x-slot>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success" role="status"><i class="bi bi-check-circle-fill me-1"></i> Profil berhasil diperbarui.</div>
    @endif
    @if (session('status') === 'password-updated')
        <div class="alert alert-success" role="status"><i class="bi bi-check-circle-fill me-1"></i> Password berhasil diperbarui.</div>
    @endif

    @php $akun = auth()->user(); @endphp

    <div class="row g-4">
        <div class="col-lg-6">
            <form method="POST" action="{{ route('profile.update') }}" class="card h-100">
                @csrf
                @method('PATCH')
                <div class="card-header"><h2 class="card-title-sm">Data akun</h2></div>
                <div class="card-body d-grid gap-3">
                    <div>
                        <label for="name" class="form-label">Nama</label>
                        <input id="name" name="name" type="text" required autocomplete="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $akun->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="email" class="form-label">Email</label>
                        <input id="email" name="email" type="email" required autocomplete="username" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $akun->email) }}">
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div><button type="submit" class="btn btn-primary">Simpan</button></div>
                </div>
            </form>
        </div>

        <div class="col-lg-6">
            <form method="POST" action="{{ route('password.update') }}" class="card h-100">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="card-title-sm">Ganti password</h2></div>
                <div class="card-body d-grid gap-3">
                    <div>
                        <label for="current_password" class="form-label">Password saat ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="form-control {{ $errors->updatePassword->has('current_password') ? 'is-invalid' : '' }}">
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
                    </div>
                    <div>
                        <label for="update_password" class="form-label">Password baru</label>
                        <input id="update_password" name="password" type="password" autocomplete="new-password" class="form-control {{ $errors->updatePassword->has('password') ? 'is-invalid' : '' }}">
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
                    </div>
                    <div>
                        <label for="update_password_confirmation" class="form-label">Ulangi password baru</label>
                        <input id="update_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="form-control {{ $errors->updatePassword->has('password_confirmation') ? 'is-invalid' : '' }}">
                        <div class="invalid-feedback">{{ $errors->updatePassword->first('password_confirmation') }}</div>
                    </div>
                    <div><button type="submit" class="btn btn-primary">Simpan</button></div>
                </div>
            </form>
        </div>

        <div class="col-12">
            <div class="card border-danger-subtle">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div style="max-width: 36rem">
                        <h2 class="card-title-sm text-danger">Hapus akun</h2>
                        <p class="small text-body-secondary mb-0 mt-1">Setelah dihapus, semua data akun ini hilang permanen. Tindakan ini tidak bisa dibatalkan.</p>
                    </div>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#hapusAkun">Hapus akun</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade {{ $errors->userDeletion->any() ? 'show d-block' : '' }}" id="hapusAkun" tabindex="-1" aria-labelledby="hapusAkunJudul" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" action="{{ route('profile.destroy') }}" class="modal-content">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h2 class="modal-title h5" id="hapusAkunJudul">Yakin hapus akun?</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <p class="mb-0">Masukkan password untuk memastikan Anda ingin menghapus akun ini secara permanen.</p>
                    <div>
                        <label for="password_hapus" class="form-label">Password</label>
                        <input id="password_hapus" name="password" type="password" required class="form-control {{ $errors->userDeletion->has('password') ? 'is-invalid' : '' }}">
                        <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus akun</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
