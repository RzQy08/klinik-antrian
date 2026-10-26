@props(['title' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body>

<nav class="navbar navbar-expand-xl topbar sticky-top">
    <div class="container-xxl">
        <a class="navbar-brand me-xl-4" href="{{ route('beranda') }}" aria-label="{{ config('rumahsakit.nama') }}, ke beranda">
            @include('partials.brand')
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuUtama" aria-controls="menuUtama" aria-expanded="false" aria-label="Buka menu">
            <i class="bi bi-list fs-2 lh-1"></i>
        </button>

        <div class="collapse navbar-collapse" id="menuUtama">
            <ul class="navbar-nav me-auto gap-xl-1 mt-3 mt-xl-0">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('beranda') ? 'active' : '' }}" href="{{ route('beranda') }}"><i class="bi bi-house-door me-2"></i>Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('antrian.create') ? 'active' : '' }}" href="{{ route('antrian.create') }}"><i class="bi bi-ticket-perforated me-2"></i>Ambil antrian</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('beranda') }}#antrian"><i class="bi bi-list-ol me-2"></i>Antrian hari ini</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('antrian.layar') ? 'active' : '' }}" href="{{ route('antrian.layar') }}"><i class="bi bi-tv me-2"></i>Papan antrian</a>
                </li>
            </ul>

            <div class="nav-actions d-flex flex-column flex-xl-row gap-2 mt-3 mt-xl-0">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm"><i class="bi bi-speedometer2 me-1"></i>Buka dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk staf</a>
                    <a href="{{ route('antrian.create') }}" class="btn btn-primary btn-sm">Ambil nomor</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main class="container-xxl py-4 py-md-5" @if (! $wide) style="max-width: 46rem" @endif>
    {{ $slot }}
</main>

<footer class="container-xxl pb-5 small text-body-secondary">
    {{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }}. Sistem antrian poliklinik dengan perkiraan waktu tunggu dari teori antrian.
</footer>

</body>
</html>
