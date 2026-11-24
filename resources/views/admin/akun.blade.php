@php
    $labelPeran = ['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'];
    $ikonPeran = ['admin' => 'shield-lock', 'petugas' => 'headset', 'dokter' => 'person-badge'];
    $urlPeran = fn (?string $r) => route('admin.akun.index', array_filter(['peran' => $r, 'cari' => $cari]));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="ak-head">
            <form method="GET" class="ak-search" role="search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="fCari">Cari nama atau email</label>
                <input id="fCari" type="search" name="cari" value="{{ $cari }}" maxlength="80" placeholder="Cari nama atau email" autocomplete="off">
                @if ($peran)<input type="hidden" name="peran" value="{{ $peran }}">@endif
            </form>
        </div>

        <nav class="ak-seg" aria-label="Saring peran">
            <a href="{{ $urlPeran(null) }}" class="{{ ! $peran ? 'is-on' : '' }}" @if (! $peran) aria-current="true" @endif>Semua<b>{{ $jumlah->sum() }}</b></a>
            @foreach ($labelPeran as $k => $l)
                <a href="{{ $urlPeran($k) }}" class="{{ $peran === $k ? 'is-on' : '' }}" @if ($peran === $k) aria-current="true" @endif><i class="bi bi-{{ $ikonPeran[$k] }}" aria-hidden="true"></i>{{ $l }}<b>{{ $jumlah->get($k, 0) }}</b></a>
            @endforeach
        </nav>
    </x-slot>

    <div class="lp-page">
        @include('partials.flash')

        <div class="ak-layout">
            <section class="ak-list" aria-label="Daftar akun">
                @forelse ($akun as $u)
                    <article class="ak-row {{ $u->nonaktif_at ? 'is-off' : '' }}">
                        <span class="ak-av ak-av-{{ $u->role }}" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', $u->name)), 0, 1)) }}</span>
                        <div class="ak-who">
                            <strong>{{ $u->name }}@if ($u->is(auth()->user()))<em>Anda</em>@endif</strong>
                            <small>{{ $u->email }}</small>
                        </div>
                        <span class="ak-role ak-role-{{ $u->role }}"><i class="bi bi-{{ $ikonPeran[$u->role] ?? 'person' }}" aria-hidden="true"></i>{{ $labelPeran[$u->role] ?? $u->role }}</span>
                        <span class="ak-state {{ $u->nonaktif_at ? 'off' : 'on' }}">{{ $u->nonaktif_at ? 'Nonaktif' : 'Aktif' }}</span>
                        <div class="ak-act">
                            <button type="button" class="ak-btn" data-bs-toggle="collapse" data-bs-target="#pw-{{ $u->id }}" aria-expanded="false" aria-controls="pw-{{ $u->id }}"><i class="bi bi-key" aria-hidden="true"></i><span>Password</span></button>
                            @if (! $u->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.akun.update', $u) }}" onsubmit="return confirm('{{ $u->nonaktif_at ? 'Aktifkan' : 'Nonaktifkan' }} akun {{ e($u->name) }}?')">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="aksi" value="{{ $u->nonaktif_at ? 'aktifkan' : 'nonaktifkan' }}">
                                    <button class="ak-btn {{ $u->nonaktif_at ? 'ak-btn-ok' : 'ak-btn-bad' }}"><i class="bi bi-{{ $u->nonaktif_at ? 'person-check' : 'person-slash' }}" aria-hidden="true"></i><span>{{ $u->nonaktif_at ? 'Aktifkan' : 'Nonaktifkan' }}</span></button>
                                </form>
                            @endif
                        </div>
                        <div class="collapse ak-pw-wrap" id="pw-{{ $u->id }}"><form method="POST" action="{{ route('admin.akun.update', $u) }}" class="ak-pw">
                            @csrf @method('PATCH')
                            <input type="hidden" name="aksi" value="password">
                            <input type="password" name="password" placeholder="Password baru" autocomplete="new-password" required aria-label="Password baru {{ $u->name }}">
                            <input type="password" name="password_confirmation" placeholder="Ulangi password" autocomplete="new-password" required aria-label="Ulangi password {{ $u->name }}">
                            <button class="btn btn-primary btn-sm">Simpan</button>
                        </form></div>
                    </article>
                @empty
                    <div class="lp-kosong"><i class="bi bi-people" aria-hidden="true"></i><strong>Akun tidak ditemukan</strong></div>
                @endforelse

                <div class="lp-pager">{{ $akun->links('pagination::simple-bootstrap-5') }}</div>
            </section>

            <section class="ak-form" aria-labelledby="judulTambah">
                <h2 id="judulTambah"><i class="bi bi-person-plus" aria-hidden="true"></i>Tambah akun</h2>
                <form method="POST" action="{{ route('admin.akun.store') }}" class="d-grid gap-3">
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
</x-app-layout>
