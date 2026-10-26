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
    $pendek = ['Antrian hari ini' => 'Antrian'];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="has-bottomnav">

<nav class="navbar navbar-expand-lg topbar sticky-top">
    <div class="container-xxl">
        <a class="navbar-brand" href="{{ route('dashboard') }}" aria-label="{{ config('rumahsakit.nama') }}, ke dashboard">
            @include('partials.brand')
        </a>

        {{-- Menu layar lebar --}}
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto gap-1">
                @foreach ($nav as [$label, $url, $aktif, $ikon])
                    <li class="nav-item">
                        <a class="nav-link {{ $aktif ? 'active' : '' }}" href="{{ $url }}" @if ($aktif) aria-current="page" @endif>{{ $label }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="dropdown">
                <button class="user-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="avatar">{{ $inisial }}</span>
                    <span class="text-start lh-sm d-none d-lg-block">
                        <span class="d-block fw-semibold small">{{ $user?->name }}</span>
                        <span class="d-block text-body-secondary" style="font-size: .75rem">{{ $roleLabel }}</span>
                    </span>
                    <i class="bi bi-chevron-down small text-body-secondary"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profil</a></li>
                    <li><a class="dropdown-item" href="{{ route('beranda') }}"><i class="bi bi-house me-2"></i>Beranda</a></li>
                    <li><a class="dropdown-item" href="{{ route('antrian.create') }}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>Halaman pasien</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Tombol menu layar kecil --}}
        <button class="navbar-toggler border-0 px-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuSeluler" aria-controls="menuSeluler" aria-label="Buka menu">
            <i class="bi bi-list fs-2"></i>
        </button>
    </div>
</nav>

{{-- Menu layar kecil --}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="menuSeluler" aria-labelledby="menuSelulerJudul">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2" id="menuSelulerJudul">
            <span class="avatar">{{ $inisial }}</span>
            <div class="lh-sm">
                <div class="fw-semibold">{{ $user?->name }}</div>
                <div class="small text-body-secondary">{{ $roleLabel }}</div>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="list-group list-group-flush mb-3">
            @foreach ($nav as [$label, $url, $aktif, $ikon])
                <a href="{{ $url }}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 {{ $aktif ? 'active' : '' }}">
                    <i class="bi bi-{{ $ikon }}"></i> {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="list-group list-group-flush mt-auto">
            <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action"><i class="bi bi-person me-3"></i>Profil</a>
            <a href="{{ route('beranda') }}" class="list-group-item list-group-item-action"><i class="bi bi-house me-3"></i>Beranda</a>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-outline-danger w-100"><i class="bi bi-box-arrow-right me-2"></i>Keluar</button>
        </form>
    </div>
</div>

@isset($header)
    <div class="page-band">
        <div class="container-xxl pt-4 pb-4 pt-md-5">
            {{ $header }}
        </div>
    </div>
@endisset

<main class="container-xxl py-4">
    {{ $slot }}
</main>

@if (count($nav) > 0)
    <nav class="bottomnav" aria-label="Menu cepat">
        @foreach (array_slice($nav, 0, 5) as [$label, $url, $aktif, $ikon])
            <a href="{{ $url }}" class="{{ $aktif ? 'active' : '' }}" @if ($aktif) aria-current="page" @endif>
                <i class="bi bi-{{ $ikon }}"></i>
                <span>{{ $pendek[$label] ?? $label }}</span>
            </a>
        @endforeach
    </nav>
@endif

</body>
</html>
