@php
    $labelPeran = ['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="lp-head">
            <div>
                <h1 class="lp-title">Akun staf</h1>
                <p class="lp-sub">{{ $jumlah->get('admin', 0) }} admin, {{ $jumlah->get('petugas', 0) }} petugas, {{ $jumlah->get('dokter', 0) }} dokter aktif.</p>
            </div>
            <form method="GET" class="lp-filter-inline">
                <label class="visually-hidden" for="fCari">Cari nama atau email</label>
                <input id="fCari" type="search" name="cari" value="{{ $cari }}" maxlength="80" class="form-control" placeholder="Cari nama atau email">
                <label class="visually-hidden" for="fPeran">Peran</label>
                <select id="fPeran" name="peran" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua peran</option>
                    @foreach ($labelPeran as $k => $l)
                        <option value="{{ $k }}" @selected($peran === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <div class="lp-page">
        @include('partials.flash')

        <div class="row g-4">
            <div class="col-xl-8">
                <div class="lp-tabel">
                    <div class="lp-scroll">
                        <table class="table lp-table align-middle">
                            <thead><tr><th>Nama</th><th>Peran</th><th>Status</th><th class="text-end">Tindakan</th></tr></thead>
                            <tbody>
                                @forelse ($akun as $u)
                                    <tr>
                                        <td data-label="Nama" class="lp-td-pasien">{{ $u->name }}<div class="small text-body-secondary">{{ $u->email }}</div></td>
                                        <td data-label="Peran">{{ $labelPeran[$u->role] ?? $u->role }}</td>
                                        <td data-label="Status" class="lp-td-status">
                                            @if ($u->nonaktif_at)<span class="lp-dur bad">Nonaktif</span>@else<span class="lp-dur">Aktif</span>@endif
                                        </td>
                                        <td data-label="Tindakan" class="text-end">
                                            <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#pw-{{ $u->id }}" aria-expanded="false">Ganti password</button>
                                                @if (! $u->is(auth()->user()))
                                                    <form method="POST" action="{{ route('admin.akun.update', $u) }}" onsubmit="return confirm('{{ $u->nonaktif_at ? 'Aktifkan' : 'Nonaktifkan' }} akun {{ e($u->name) }}?')">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="aksi" value="{{ $u->nonaktif_at ? 'aktifkan' : 'nonaktifkan' }}">
                                                        <button class="btn btn-sm {{ $u->nonaktif_at ? 'btn-outline-primary' : 'btn-outline-danger' }}">{{ $u->nonaktif_at ? 'Aktifkan' : 'Nonaktifkan' }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                            <div class="collapse mt-2 text-start" id="pw-{{ $u->id }}">
                                                <form method="POST" action="{{ route('admin.akun.update', $u) }}" class="d-grid gap-2">
                                                    @csrf @method('PATCH')
                                                    <input type="hidden" name="aksi" value="password">
                                                    <input type="password" name="password" class="form-control form-control-sm" placeholder="Password baru" autocomplete="new-password" required>
                                                    <input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="Ulangi password baru" autocomplete="new-password" required>
                                                    <button class="btn btn-primary btn-sm">Simpan password</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><div class="lp-kosong"><i class="bi bi-people" aria-hidden="true"></i><strong>Akun tidak ditemukan</strong></div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="lp-pager">{{ $akun->links('pagination::simple-bootstrap-5') }}</div>
            </div>

            <div class="col-xl-4">
                <section class="pt-sec">
                    <div class="pt-sec-head"><h2>Tambah akun</h2></div>
                    <form method="POST" action="{{ route('admin.akun.store') }}" class="d-grid gap-3 p-3">
                        @csrf
                        <div>
                            <label class="form-label" for="name">Nama</label>
                            <input id="name" name="name" value="{{ old('name') }}" maxlength="100" required class="form-control @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="254" required autocomplete="off" class="form-control @error('email') is-invalid @enderror">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="role">Peran</label>
                            <select id="role" name="role" class="form-select">
                                <option value="petugas" @selected(old('role') !== 'admin')>Petugas</option>
                                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                            </select>
                            <div class="form-text">Akun dokter dibuat dari menu Dokter.</div>
                        </div>
                        <div>
                            <label class="form-label" for="password">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@else<div class="form-text">Minimal 10 karakter, huruf besar dan kecil, angka, dan simbol.</div>@enderror
                        </div>
                        <div>
                            <label class="form-label" for="password_confirmation">Ulangi password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control">
                        </div>
                        <button class="btn btn-primary">Buat akun</button>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
