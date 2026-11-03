@php
    $user = auth()->user();
    $role = $user?->role;
    $roleLabel = ['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'][$role] ?? 'Pengguna';

    // [label, url, aktif, ikon]
    $nav = [];

    if ($role === 'admin') {
        $nav[] = ['Ringkasan', route('admin.dashboard'), request()->routeIs('admin.dashboard'), 'house'];
        $nav[] = ['Poli', route('admin.poli.index'), request()->routeIs('admin.poli.*'), 'building'];
        $nav[] = ['Dokter', route('admin.dokter.index'), request()->routeIs('admin.dokter.*', 'admin.jadwal.*'), 'person-badge'];
    }

    if (in_array($role, ['admin', 'petugas'], true)) {
        $nav[] = ['Antrian hari ini', route('antrian.index'), request()->routeIs('antrian.index', 'antrian.kelola'), 'people'];
        $nav[] = ['Analitik', route('antrian.analitik'), request()->routeIs('antrian.analitik'), 'bar-chart-line'];

        if (\Illuminate\Support\Facades\Route::has('antrian.riwayat')) {
            $nav[] = ['Riwayat', route('antrian.riwayat'), request()->routeIs('antrian.riwayat*'), 'clock-history'];
        }
    }

    if ($role === 'dokter') {
        $nav[] = ['Pasien saya', route('antrian.dokter'), request()->routeIs('antrian.dokter'), 'clipboard2-pulse'];
    }

    $inisial = mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', (string) $user?->name)), 0, 1));
    $pendek = ['Antrian hari ini' => 'Antrian', 'Ringkasan' => 'Ringkas'];

    // Dock bawah memuat paling banyak 5 tombol; kalau menu lebih banyak, sisanya lewat tombol "Menu"
    $adaMenuLain = count($nav) > 5;
    $dock = $adaMenuLain ? array_slice($nav, 0, 4) : $nav;
    $nama = config('rumahsakit.nama', 'RS Bersama');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="has-bottomnav">

<a class="nv-skip" href="#konten">Lewati ke konten</a>

<header class="nv-wrap" id="nvWrap">
    <nav class="nv nv-staff" aria-label="Navigasi utama">
        <a class="nv-brand" href="{{ route('dashboard') }}" aria-label="{{ $nama }}, ke dashboard">@include('partials.brand')</a>

        <ul class="nv-links">
            @foreach ($nav as [$label, $url, $aktif, $ikon])
                <li><a class="nv-link {{ $aktif ? 'is-on' : '' }}" href="{{ $url }}" @if ($aktif) aria-current="page" @endif><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="nv-actions">
            <span class="nv-role">{{ $roleLabel }}</span>
            <a href="{{ route('antrian.create') }}" target="_blank" rel="noopener" class="nv-btn nv-btn-ghost" title="Halaman pasien (tab baru)"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Halaman pasien<span class="visually-hidden"> (tab baru)</span></a>

            <div class="dropdown">
                <button class="nv-user" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun {{ $user?->name }}">
                    <span class="avatar">{{ $inisial }}</span>
                    <span class="nama">{{ $user?->name }}</span>
                    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end nv-menu">
                    <li class="nv-menu-head">
                        <span class="avatar">{{ $inisial }}</span>
                        <span><b>{{ $user?->name }}</b><small>{{ $user?->email }}</small></span>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person" aria-hidden="true"></i>Profil dan keamanan</a></li>
                    <li><a class="dropdown-item" href="{{ route('beranda') }}"><i class="bi bi-house" aria-hidden="true"></i>Beranda</a></li>
                    <li><a class="dropdown-item" href="{{ route('antrian.layar') }}" target="_blank" rel="noopener"><i class="bi bi-tv" aria-hidden="true"></i>Papan antrian</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        <button class="nv-burger" type="button" id="nvBurger" data-bs-toggle="offcanvas" data-bs-target="#nvSheet" aria-controls="nvSheet" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </button>
    </nav>
</header>

{{-- Lembar menu di HP --}}
<div class="offcanvas offcanvas-end nv-sheet" tabindex="-1" id="nvSheet" aria-labelledby="nvSheetJudul">
    <div class="offcanvas-header">
        <div id="nvSheetJudul">@include('partials.brand')</div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body">
        <div class="nv-sheet-user">
            <span class="avatar">{{ $inisial }}</span>
            <span><b>{{ $user?->name }}</b><small>{{ $roleLabel }}</small></span>
        </div>

        <ul class="nv-sheet-links">
            @foreach ($nav as [$label, $url, $aktif, $ikon])
                <li><a class="nv-sheet-link {{ $aktif ? 'is-on' : '' }}" href="{{ $url }}" @if ($aktif) aria-current="page" @endif><span class="ic"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></span>{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="nv-sheet-foot">
            <a href="{{ route('profile.edit') }}" class="nv-btn nv-btn-ghost"><i class="bi bi-person" aria-hidden="true"></i>Profil dan keamanan</a>
            <a href="{{ route('antrian.create') }}" target="_blank" rel="noopener" class="nv-btn nv-btn-ghost"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>Halaman pasien<span class="visually-hidden"> (tab baru)</span></a>
            <form method="POST" action="{{ route('logout') }}" class="d-grid">
                @csrf
                <button type="submit" class="nv-btn nv-btn-ghost text-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i>Keluar</button>
            </form>
        </div>
    </div>
</div>

@isset($header)
    <div class="page-band">
        <div class="container-xxl pt-4 pb-4 pt-md-5">
            {{ $header }}
        </div>
    </div>
@endisset

<main id="konten" tabindex="-1" class="container-xxl py-4">
    {{ $slot }}

    <footer class="nv-foot">
        <span>{{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }} &middot; {{ config('rumahsakit.sub', 'Sistem Antrian Poliklinik') }}</span>
        <nav aria-label="Pintasan">
            <a href="{{ route('antrian.create') }}" target="_blank" rel="noopener">Halaman pasien</a>
            <a href="{{ route('antrian.layar') }}" target="_blank" rel="noopener">Papan antrian</a>
        </nav>
    </footer>
</main>

{{-- Dock bawah di HP dan tablet --}}
@if (count($nav) > 0)
    <nav class="nv-dock" aria-label="Menu cepat">
        @foreach ($dock as [$label, $url, $aktif, $ikon])
            <a href="{{ $url }}" class="{{ $aktif ? 'is-on' : '' }}" @if ($aktif) aria-current="page" @endif>
                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                <span>{{ $pendek[$label] ?? $label }}</span>
            </a>
        @endforeach
        @if ($adaMenuLain)
            <button type="button" data-bs-toggle="offcanvas" data-bs-target="#nvSheet" aria-controls="nvSheet">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <span>Menu</span>
            </button>
        @endif
    </nav>
@endif

<script>
(function () {
    var wrap = document.getElementById('nvWrap'), burger = document.getElementById('nvBurger'), sheet = document.getElementById('nvSheet');
    var tunggu = false;
    function geser() { wrap.classList.toggle('is-scrolled', window.scrollY > 8); tunggu = false; }
    window.addEventListener('scroll', function () { if (!tunggu) { tunggu = true; requestAnimationFrame(geser); } }, { passive: true });
    geser();
    if (sheet && burger) {
        sheet.addEventListener('show.bs.offcanvas', function () { burger.classList.add('is-open'); });
        sheet.addEventListener('hidden.bs.offcanvas', function () { burger.classList.remove('is-open'); });
    }
})();
</script>

</body>
</html>
