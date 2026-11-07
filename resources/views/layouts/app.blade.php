@php
    $user = auth()->user();
    $role = $user?->role;
    $roleLabel = ['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'][$role] ?? 'Pengguna';

    // [label, url, aktif, ikon]
    $nav = [];

    if ($role === 'admin') {
        $nav[] = ['Ringkasan', route('admin.dashboard'), request()->routeIs('admin.dashboard'), 'house', 'Kelola'];
        $nav[] = ['Poli', route('admin.poli.index'), request()->routeIs('admin.poli.*'), 'building', 'Kelola'];
        $nav[] = ['Dokter', route('admin.dokter.index'), request()->routeIs('admin.dokter.*', 'admin.jadwal.*'), 'person-badge', 'Kelola'];
    }

    if (in_array($role, ['admin', 'petugas'], true)) {
        $nav[] = ['Antrian hari ini', route('antrian.index'), request()->routeIs('antrian.index', 'antrian.kelola'), 'people', 'Layanan'];
        $nav[] = ['Analitik', route('antrian.analitik'), request()->routeIs('antrian.analitik'), 'bar-chart-line', 'Layanan'];

        if (\Illuminate\Support\Facades\Route::has('antrian.riwayat')) {
            $nav[] = ['Riwayat', route('antrian.riwayat'), request()->routeIs('antrian.riwayat*'), 'clock-history', 'Layanan'];
        }
    }

    if ($role === 'dokter') {
        $nav[] = ['Pasien saya', route('antrian.dokter'), request()->routeIs('antrian.dokter'), 'clipboard2-pulse', 'Layanan'];
    }

    $inisial = mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', (string) $user?->name)), 0, 1));
    $pendek = ['Antrian hari ini' => 'Antrian', 'Ringkasan' => 'Ringkas'];

    // Dock bawah memuat paling banyak 5 tombol; kalau menu lebih banyak, sisanya lewat tombol "Menu"
    $adaMenuLain = count($nav) > 5;
    $dock = $adaMenuLain ? array_slice($nav, 0, 4) : $nav;
    $nama = config('rumahsakit.nama', 'RS Bersama');
    $judul = collect($nav)->first(fn ($n) => $n[2])[0] ?? 'Dashboard';
    $tanggal = now()->locale('id')->translatedFormat('l, j F Y');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <script>try{if(localStorage.getItem('sh-rail')==='1')document.documentElement.classList.add('sh-rail')}catch(e){}</script>
</head>
<body class="sh-body">

<a class="nv-skip" href="#konten">Lewati ke konten</a>

<aside class="sh-side" id="shSide" aria-label="Menu utama">
    <a class="sh-brand" href="{{ route('dashboard') }}" aria-label="{{ $nama }}, ke dashboard">@include('partials.brand')</a>

    <nav class="sh-nav">
        @php $grup = null; @endphp
        @foreach ($nav as [$label, $url, $aktif, $ikon, $g])
            @if ($g !== $grup)
                @php $grup = $g; @endphp
                <div class="sh-label">{{ $g }}</div>
            @endif
            <a class="sh-link {{ $aktif ? 'is-on' : '' }}" href="{{ $url }}" title="{{ $label }}" @if ($aktif) aria-current="page" @endif><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><span class="t">{{ $label }}</span></a>
        @endforeach
        <div class="sh-label">Lainnya</div>
        <a class="sh-link" href="{{ route('antrian.create') }}" target="_blank" rel="noopener" title="Halaman pasien"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="t">Halaman pasien</span></a>
        <a class="sh-link" href="{{ route('antrian.layar') }}" target="_blank" rel="noopener" title="Papan antrian"><i class="bi bi-tv" aria-hidden="true"></i><span class="t">Papan antrian</span></a>
        <a class="sh-link {{ request()->routeIs('profile.*') ? 'is-on' : '' }}" href="{{ route('profile.edit') }}" title="Profil dan keamanan"><i class="bi bi-person-gear" aria-hidden="true"></i><span class="t">Profil</span></a>
    </nav>

    <div class="sh-foot">
        <button type="button" class="sh-link sh-collapse" id="shCollapse" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><i class="bi bi-chevron-bar-left" aria-hidden="true"></i><span class="t">Ciutkan</span></button>
        <div class="sh-user">
            <span class="sh-av">{{ $inisial }}</span>
            <span class="sh-user-t"><b>{{ $user?->name }}</b><small>{{ $roleLabel }}</small></span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="sh-logout" title="Keluar" aria-label="Keluar"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></button>
            </form>
        </div>
    </div>
</aside>
<button type="button" class="sh-scrim" id="shScrim" tabindex="-1" aria-label="Tutup menu"></button>

<div class="sh-main">
    <header class="sh-top" id="shTop">
        <button type="button" class="sh-burger" id="shBurger" aria-controls="shSide" aria-expanded="false" aria-label="Buka menu"><i class="bi bi-list" aria-hidden="true"></i></button>
        <div class="sh-crumb"><small>{{ $roleLabel }}</small><strong>{{ $judul }}</strong></div>
        <div class="sh-top-act">
            <span class="sh-date"><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $tanggal }}</span>
            <a href="{{ route('antrian.layar') }}" target="_blank" rel="noopener" class="sh-ibtn sh-ibtn-hide" title="Papan antrian" aria-label="Papan antrian (tab baru)"><i class="bi bi-tv" aria-hidden="true"></i></a>
            <a href="{{ route('antrian.create') }}" target="_blank" rel="noopener" class="sh-ibtn sh-ibtn-hide" title="Halaman pasien" aria-label="Halaman pasien (tab baru)"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>
            <div class="dropdown">
                <button class="sh-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun {{ $user?->name }}">
                    <span class="sh-av">{{ $inisial }}</span>
                    <span class="nama">{{ $user?->name }}</span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end sh-menu">
                    <li class="sh-menu-head">
                        <span class="sh-av">{{ $inisial }}</span>
                        <span><b>{{ $user?->name }}</b><small>{{ $user?->email }}</small></span>
                    </li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person" aria-hidden="true"></i>Profil dan keamanan</a></li>
                    <li><a class="dropdown-item" href="{{ route('beranda') }}"><i class="bi bi-house" aria-hidden="true"></i>Beranda</a></li>
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
    </header>

    <main id="konten" tabindex="-1" class="sh-content">
        @isset($header)
            <div class="sh-head">{{ $header }}</div>
        @endisset

        {{ $slot }}

        <footer class="sh-foot-note">
            <span>{{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }} &middot; {{ config('rumahsakit.sub', 'Sistem Antrian Poliklinik') }}</span>
            <nav aria-label="Pintasan">
                <a href="{{ route('antrian.create') }}" target="_blank" rel="noopener">Halaman pasien</a>
                <a href="{{ route('antrian.layar') }}" target="_blank" rel="noopener">Papan antrian</a>
            </nav>
        </footer>
    </main>
</div>

{{-- Dock bawah di HP dan tablet --}}
@if (count($nav) > 0)
    <nav class="sh-dock" aria-label="Menu cepat">
        @foreach ($dock as [$label, $url, $aktif, $ikon])
            <a href="{{ $url }}" class="{{ $aktif ? 'is-on' : '' }}" @if ($aktif) aria-current="page" @endif>
                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                <span>{{ $pendek[$label] ?? $label }}</span>
            </a>
        @endforeach
        @if ($adaMenuLain)
            <button type="button" data-sh-open aria-controls="shSide">
                <i class="bi bi-grid" aria-hidden="true"></i>
                <span>Menu</span>
            </button>
        @endif
    </nav>
@endif

<script>
(function () {
    var root = document.documentElement, top = document.getElementById('shTop'), burger = document.getElementById('shBurger'), side = document.getElementById('shSide');
    var mobil = window.matchMedia('(max-width: 991.98px)');
    function geser() { top.classList.toggle('is-scrolled', window.scrollY > 8); }
    window.addEventListener('scroll', geser, { passive: true }); geser();

    function buka(b) {
        root.classList.toggle('sh-open', b);
        burger.setAttribute('aria-expanded', b ? 'true' : 'false');
        if (mobil.matches) { b ? side.removeAttribute('inert') : side.setAttribute('inert', ''); }
        if (b) { var l = side.querySelector('.sh-link.is-on') || side.querySelector('.sh-link'); l && l.focus({ preventScroll: true }); } else if (mobil.matches) { burger.focus({ preventScroll: true }); }
    }
    function sinkron() { if (mobil.matches) { root.classList.contains('sh-open') || side.setAttribute('inert', ''); } else { side.removeAttribute('inert'); root.classList.remove('sh-open'); } }
    sinkron(); (mobil.addEventListener ? mobil.addEventListener('change', sinkron) : mobil.addListener(sinkron));

    burger.addEventListener('click', function () { buka(!root.classList.contains('sh-open')); });
    document.getElementById('shScrim').addEventListener('click', function () { buka(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && root.classList.contains('sh-open')) buka(false); });
    document.querySelectorAll('[data-sh-open]').forEach(function (b) { b.addEventListener('click', function () { buka(true); }); });
    side.addEventListener('click', function (e) { if (mobil.matches && e.target.closest('a.sh-link')) buka(false); });

    document.getElementById('shCollapse').addEventListener('click', function () {
        var r = root.classList.toggle('sh-rail');
        try { localStorage.setItem('sh-rail', r ? '1' : '0'); } catch (e) {}
        this.setAttribute('aria-label', r ? 'Lebarkan sidebar' : 'Ciutkan sidebar');
    });
})();
</script>

</body>
</html>
