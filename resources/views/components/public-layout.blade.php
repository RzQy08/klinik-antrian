@props(['title' => null, 'wide' => false, 'full' => false])
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="d-flex flex-column min-vh-dvh bn-pub">

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

@if ($full)
    <main class="flex-grow-1">{{ $slot }}</main>
@else
    <main class="container-xxl py-4 py-md-5 flex-grow-1" @if (! $wide) style="max-width: 46rem" @endif>
        {{ $slot }}
    </main>
@endif

@php
    $poliFooter = \App\Models\Poli::where('aktif', true)->orderBy('kode_poli')->get(['id', 'nama_poli']);
    $waTujuan = config('rumahsakit.whatsapp') ? \App\Services\WhatsAppService::normalisasi((string) config('rumahsakit.whatsapp')) : null;

    $jamLayanan = config('rumahsakit.jam_layanan');
    if (! $jamLayanan && \App\Services\JamOperasional::aktif()) {
        $jamLayanan = 'Pendaftaran '.\App\Services\JamOperasional::tampil(config('operasional.pendaftaran_buka')).' sampai '.\App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup')).' WIB';
    }
@endphp
<footer class="bn-footer">
    <div class="container-xxl">
        <div class="bn-footer-top">
            <div class="bn-footer-brand">
                <a href="{{ route('beranda') }}" class="text-decoration-none">@include('partials.brand', ['terang' => true])</a>
                <p>{{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }} melayani pendaftaran antrian poliklinik secara online, lengkap dengan perkiraan waktu tunggu dan pemberitahuan saat giliran Anda tiba.</p>

                @if (config('rumahsakit.instagram') || config('rumahsakit.facebook') || $waTujuan)
                    <div class="bn-social">
                        @if (config('rumahsakit.instagram'))<a href="{{ config('rumahsakit.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>@endif
                        @if (config('rumahsakit.facebook'))<a href="{{ config('rumahsakit.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>@endif
                        @if ($waTujuan)<a href="https://wa.me/{{ $waTujuan }}" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>@endif
                    </div>
                @endif
            </div>

            <nav aria-label="Layanan poliklinik">
                <h4>Layanan poliklinik</h4>
                <ul>
                    @forelse ($poliFooter as $p)
                        <li><a href="{{ route('antrian.create') }}">{{ $p->nama_poli }}</a></li>
                    @empty
                        <li>Daftar poli akan segera tersedia</li>
                    @endforelse
                </ul>
            </nav>

            <nav aria-label="Tautan cepat">
                <h4>Tautan cepat</h4>
                <ul>
                    <li><a href="{{ route('antrian.create') }}">Ambil nomor antrian</a></li>
                    <li><a href="{{ route('beranda') }}#antrian">Antrian hari ini</a></li>
                    <li><a href="{{ route('antrian.layar') }}">Papan antrian</a></li>
                    <li><a href="{{ route('beranda') }}#cara-kerja">Cara kerja</a></li>
                </ul>
            </nav>

            <div>
                <h4>Hubungi kami</h4>
                <ul class="bn-contact">
                    @if (config('rumahsakit.alamat'))
                        <li><i class="bi bi-geo-alt"></i><span>{{ config('rumahsakit.alamat') }}@if (config('rumahsakit.maps')) <a href="{{ config('rumahsakit.maps') }}" target="_blank" rel="noopener" class="bn-contact-link">Lihat peta</a>@endif</span></li>
                    @endif
                    @if (config('rumahsakit.telepon'))
                        <li><i class="bi bi-telephone"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', config('rumahsakit.telepon')) }}">{{ config('rumahsakit.telepon') }}</a></li>
                    @endif
                    @if ($waTujuan)
                        <li><i class="bi bi-whatsapp"></i><a href="https://wa.me/{{ $waTujuan }}" target="_blank" rel="noopener">{{ config('rumahsakit.whatsapp') }}</a></li>
                    @endif
                    @if (config('rumahsakit.email'))
                        <li><i class="bi bi-envelope"></i><a href="mailto:{{ config('rumahsakit.email') }}">{{ config('rumahsakit.email') }}</a></li>
                    @endif
                    <li><i class="bi bi-clock"></i><span>{{ $jamLayanan ?: 'Pendaftaran antrian online tersedia' }}</span></li>
                </ul>
            </div>
        </div>

        <div class="bn-footer-bottom">
            <span>&copy; {{ now()->year }} {{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }}. Seluruh hak dilindungi.</span>
            <span class="bn-footer-note"><i class="bi bi-shield-check"></i>Data pribadi dipakai hanya untuk keperluan pendaftaran dan pelayanan.</span>
            <span class="bn-footer-links">
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Login staf</a>
                @endauth
                <a href="#top" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;"><i class="bi bi-arrow-up"></i> Ke atas</a>
            </span>
        </div>
    </div>
</footer>

</body>
</html>
