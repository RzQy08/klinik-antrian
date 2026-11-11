@props(['title' => null, 'wide' => false, 'full' => false, 'fit' => false])
@php
    $jamAktif = \App\Services\JamOperasional::aktif();
    $buka = \App\Services\JamOperasional::pendaftaranDibuka();
    $jam = fn (string $kunci) => \App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam($kunci));

    $statusTeks = ! $jamAktif
        ? 'Antrian online aktif'
        : ($buka ? 'Pendaftaran dibuka sampai pukul '.$jam('pendaftaran_tutup') : 'Pendaftaran ditutup, dibuka lagi pukul '.$jam('pendaftaran_buka'));
    $statusPendek = ! $jamAktif ? 'Antrian online aktif' : ($buka ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup');

    // Menu utama: hanya tiga tujuan inti supaya navbar ringkas di semua layar
    $menu = [
        ['Beranda', route('beranda'), request()->routeIs('beranda'), 'house-door'],
        ['Ambil antrian', route('antrian.create'), request()->routeIs('antrian.create'), 'ticket-perforated'],
        ['Papan antrian', route('antrian.layar'), request()->routeIs('antrian.layar'), 'tv'],
    ];

    $telepon = config('rumahsakit.telepon');

    $ctaBar = ! request()->routeIs('antrian.create');
    $nama = config('rumahsakit.nama', 'RS Bersama');
    $namaLengkap = config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama');
    $kontak = array_filter([
        'geo-alt' => config('rumahsakit.alamat'),
        'telephone' => config('rumahsakit.telepon'),
        'whatsapp' => config('rumahsakit.whatsapp'),
        'envelope' => config('rumahsakit.email'),
    ]);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body @class(['d-flex flex-column bn-pub', 'min-vh-dvh' => ! $fit, 'am-fit' => $fit, 'has-ctabar' => $ctaBar])>

<a class="nv-skip" href="#konten">Lewati ke konten</a>

<div class="nv-strip" role="status">
    <span><span class="nv-dot {{ $jamAktif && ! $buka ? 'off' : '' }}" aria-hidden="true"></span>{{ $statusTeks }}</span>
    @if ($jamAktif)<span><i class="bi bi-clock" aria-hidden="true"></i>Layanan sampai pukul {{ $jam('layanan_selesai') }}</span>@endif
</div>

<header class="nv-wrap" id="nvWrap">
    <nav class="nv" aria-label="Navigasi utama">
        <a class="nv-brand" href="{{ route('beranda') }}" aria-label="{{ $nama }}, ke beranda">@include('partials.brand')</a>

        <ul class="nv-links">
            @foreach ($menu as [$label, $url, $aktif, $ikon])
                <li><a class="nv-link {{ $aktif ? 'is-on' : '' }}" href="{{ $url }}" @if ($aktif) aria-current="page" @endif><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="nv-actions">
            @if ($telepon)
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $telepon) }}" class="nv-tel"><i class="bi bi-telephone" aria-hidden="true"></i>{{ $telepon }}</a>
            @endif
            @auth
                <a href="{{ route('dashboard') }}" class="nv-btn nv-btn-cta"><i class="bi bi-speedometer2" aria-hidden="true"></i>Buka dashboard</a>
            @else
                <a href="{{ route('login') }}" class="nv-btn nv-btn-ghost"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Masuk staf</a>
                <a href="{{ route('antrian.create') }}" class="nv-btn nv-btn-cta">Ambil nomor<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            @endauth
        </div>

        <button class="nv-burger" type="button" id="nvBurger" data-bs-toggle="offcanvas" data-bs-target="#nvSheet" aria-controls="nvSheet" aria-label="Buka menu">
            <span></span><span></span><span></span>
        </button>
    </nav>
</header>

{{-- Lembar menu di HP dan tablet --}}
<div class="offcanvas offcanvas-end nv-sheet" tabindex="-1" id="nvSheet" aria-labelledby="nvSheetJudul">
    <div class="offcanvas-header">
        <div id="nvSheetJudul">@include('partials.brand')</div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body">
        <div class="nv-sheet-status {{ $jamAktif && ! $buka ? 'off' : '' }}"><span class="nv-dot" aria-hidden="true"></span>{{ $statusTeks }}</div>

        <ul class="nv-sheet-links">
            @foreach ($menu as [$label, $url, $aktif, $ikon])
                <li><a class="nv-sheet-link {{ $aktif ? 'is-on' : '' }}" href="{{ $url }}" @if ($aktif) aria-current="page" @endif><span class="ic"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></span>{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="nv-sheet-foot">
            @auth
                <a href="{{ route('dashboard') }}" class="nv-btn nv-btn-cta"><i class="bi bi-speedometer2" aria-hidden="true"></i>Buka dashboard</a>
            @else
                <a href="{{ route('antrian.create') }}" class="nv-btn nv-btn-cta">Ambil nomor antrian<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('login') }}" class="nv-btn nv-btn-ghost"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Masuk staf</a>
            @endauth
        </div>
    </div>
</div>

@if ($full)
    <main id="konten" tabindex="-1" class="flex-grow-1">{{ $slot }}</main>
@else
    <main id="konten" tabindex="-1" class="container-xxl py-4 py-md-5 flex-grow-1" @if (! $wide) style="max-width: 46rem" @endif>
        {{ $slot }}
    </main>
@endif

{{-- Footer --}}
@unless ($fit)
<footer class="ft" aria-label="Footer">
    <div class="container-xxl">

        @if ($ctaBar)
            <div class="ft-cta">
                <div>
                    <h2>Berobat tanpa berdiri lama di loket</h2>
                    <p>Ambil nomor dari ponsel, lihat perkiraan waktu tunggu, lalu datang saat giliran Anda hampir tiba.</p>
                </div>
                <a href="{{ route('antrian.create') }}" class="btn btn-light btn-lg"><i class="bi bi-ticket-perforated me-2" aria-hidden="true"></i>Ambil nomor antrian</a>
            </div>
        @endif

        <div class="ft-grid">
            <div>
                <a href="{{ route('beranda') }}" class="d-inline-block text-decoration-none" aria-label="{{ $nama }}, ke beranda">@include('partials.brand', ['terang' => true])</a>
                <p class="ft-desc">{{ $namaLengkap }}. Sistem antrian poliklinik dengan perkiraan waktu tunggu yang dihitung dari teori antrian.</p>
            </div>

            <nav aria-labelledby="ft-nav">
                <h2 class="ft-h" id="ft-nav">Untuk pasien</h2>
                <ul class="ft-list">
                    <li><a href="{{ route('antrian.create') }}"><i class="bi bi-ticket-perforated" aria-hidden="true"></i>Ambil nomor antrian</a></li>
                    <li><a href="{{ route('beranda') }}#antrian"><i class="bi bi-list-ol" aria-hidden="true"></i>Antrian hari ini</a></li>
                    <li><a href="{{ route('antrian.layar') }}"><i class="bi bi-tv" aria-hidden="true"></i>Papan antrian</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="ft-staf">
                <h2 class="ft-h" id="ft-staf">Untuk staf</h2>
                <ul class="ft-list">
                    @auth
                        <li><a href="{{ route('dashboard') }}"><i class="bi bi-speedometer2" aria-hidden="true"></i>Buka dashboard</a></li>
                    @else
                        <li><a href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>Masuk staf</a></li>
                    @endauth
                </ul>
            </nav>

            <div>
                <h2 class="ft-h">Jam layanan</h2>
                <span class="ft-status"><span class="nv-dot {{ $jamAktif && ! $buka ? 'off' : '' }}" aria-hidden="true"></span>{{ $statusPendek }}</span>
                @if ($jamAktif)
                    <ul class="ft-minggu" aria-label="Jam buka per hari">
                        @foreach (\App\Services\JamOperasional::mingguan() as $m)
                            <li @class(['is-today' => $m['hari_ini'], 'is-tutup' => ! $m['buka']])>
                                <span class="ft-hari">{{ $m['label'] }}@if ($m['hari_ini'])<small>hari ini</small>@endif</span>
                                @if ($m['buka'])
                                    <span class="ft-jm"><b>{{ $m['pendaftaran'] }}</b><small>layanan sampai {{ $m['layanan'] }}</small></span>
                                @else
                                    <span class="ft-jm"><b>Tutup</b></span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    @if ($libur = \App\Services\JamOperasional::liburBerikutnya())
                        <p class="ft-libur"><i class="bi bi-calendar-x" aria-hidden="true"></i><span>Libur {{ $libur['tanggal']->translatedFormat('j F Y') }}: {{ $libur['keterangan'] }}</span></p>
                    @endif
                @else
                    <p class="mb-0 text-on-navy-muted">Pendaftaran antrian online tersedia setiap saat selama klinik beroperasi.</p>
                @endif

                @if ($kontak)
                    <ul class="ft-list mt-3">
                        @foreach ($kontak as $ikon => $isi)
                            <li><span><i class="bi bi-{{ $ikon }} me-2" aria-hidden="true"></i>{{ $isi }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="ft-bottom">
            <span>&copy; {{ now()->year }} {{ $namaLengkap }}. Seluruh hak dilindungi.</span>
            <button type="button" class="ft-top" id="ftTop"><i class="bi bi-arrow-up" aria-hidden="true"></i>Kembali ke atas</button>
        </div>
    </div>
</footer>
@endunless

{{-- Bilah ajakan di HP --}}
@if ($ctaBar)
    <div class="nv-ctabar">
        <span><span class="nv-dot {{ $jamAktif && ! $buka ? 'off' : '' }}" aria-hidden="true"></span>{{ $statusPendek }}</span>
        <a href="{{ route('antrian.create') }}" class="nv-btn nv-btn-cta">Ambil nomor<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
@endif

<script>
(function () {
    var wrap = document.getElementById('nvWrap'), burger = document.getElementById('nvBurger'), sheet = document.getElementById('nvSheet');
    if (!wrap) return;

    // Navbar tetap gelap; .is-scrolled hanya menambah bayangan.
    // Dua ambang (24px aktif, 8px mati) mencegah bayangan berkedip di batas.
    var scrolled = false, ticking = false;
    function baca() {
        var y = window.scrollY;
        if (!scrolled && y > 24) scrolled = true;
        else if (scrolled && y < 8) scrolled = false;
        wrap.classList.toggle('is-scrolled', scrolled);
        ticking = false;
    }
    baca();
    window.addEventListener('scroll', function () {
        if (!ticking) { ticking = true; window.requestAnimationFrame(baca); }
    }, { passive: true });

    if (sheet && burger) {
        sheet.addEventListener('show.bs.offcanvas', function () { burger.classList.add('is-open'); burger.setAttribute('aria-expanded', 'true'); });
        sheet.addEventListener('hidden.bs.offcanvas', function () { burger.classList.remove('is-open'); burger.setAttribute('aria-expanded', 'false'); });

        function tutupSheet() {
            var inst = window.bootstrap && window.bootstrap.Offcanvas && window.bootstrap.Offcanvas.getInstance(sheet);
            if (inst) inst.hide();
        }

        // Layar membesar ke ukuran desktop saat lembar menu terbuka (rotasi tablet, ubah ukuran jendela): tutup otomatis
        var lebar = window.matchMedia('(min-width: 992px)');
        var saatUbah = function () { if (lebar.matches) tutupSheet(); };
        if (lebar.addEventListener) lebar.addEventListener('change', saatUbah); else lebar.addListener(saatUbah);

        // Pilih menu di lembar: lembar menutup dulu supaya perpindahan halaman terasa halus
        sheet.addEventListener('click', function (e) {
            if (e.target.closest('a.nv-sheet-link, a.nv-btn')) tutupSheet();
        });
    }

    var atas = document.getElementById('ftTop');
    if (atas) atas.addEventListener('click', function () {
        var diam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: diam ? 'auto' : 'smooth' });
    });
})();
</script>

</body>
</html>
