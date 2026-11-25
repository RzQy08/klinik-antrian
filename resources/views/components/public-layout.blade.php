@props(['title' => null, 'wide' => false, 'full' => false, 'fit' => false, 'bare' => false])
@php
    $jamAktif = \App\Services\JamOperasional::aktif();
    $buka = \App\Services\JamOperasional::pendaftaranDibuka();
    $jam = fn (string $kunci) => \App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam($kunci));

    $statusTeks = ! $jamAktif
        ? 'Antrian online aktif'
        : ($buka ? 'Pendaftaran dibuka sampai pukul '.$jam('pendaftaran_tutup') : 'Pendaftaran ditutup, dibuka lagi pukul '.$jam('pendaftaran_buka'));
    $statusPendek = ! $jamAktif ? 'Antrian online aktif' : ($buka ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup');

    $menu = [
        ['Beranda', route('beranda'), request()->routeIs('beranda'), 'house-door'],
        ['Ambil antrian', route('antrian.create'), request()->routeIs('antrian.create'), 'ticket-perforated'],
        ['Papan antrian', route('antrian.layar'), request()->routeIs('antrian.layar'), 'tv'],
    ];

    $telepon = config('rumahsakit.telepon');

    $ctaBar = ! $bare && ! request()->routeIs('antrian.create');
    $nama = config('rumahsakit.nama', 'RSU Kota Tangerang');
    $namaLengkap = config('rumahsakit.nama_lengkap', 'RSU Kota Tangerang');
    $alamat = config('rumahsakit.alamat');
    $telp = config('rumahsakit.telepon');
    $wa = config('rumahsakit.whatsapp');
    $email = config('rumahsakit.email');
    $kontak = array_values(array_filter([
        $alamat ? ['geo-alt', 'Alamat', $alamat, config('rumahsakit.maps')] : null,
        $telp ? ['telephone', 'Telepon', $telp, 'tel:'.preg_replace('/[^\d+]/', '', $telp)] : null,
        $wa ? ['whatsapp', 'WhatsApp', $wa, 'https://wa.me/'.preg_replace('/^0/', '62', preg_replace('/\D/', '', $wa))] : null,
        $email ? ['envelope', 'Email', $email, 'mailto:'.$email] : null,
    ]));
    $sosial = array_values(array_filter([
        config('rumahsakit.instagram') ? ['instagram', 'Instagram', config('rumahsakit.instagram')] : null,
        config('rumahsakit.facebook') ? ['facebook', 'Facebook', config('rumahsakit.facebook')] : null,
    ]));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body @class(['d-flex flex-column bn-pub', 'min-vh-dvh' => ! $fit, 'am-fit' => $fit, 'has-ctabar' => $ctaBar, 'tk-page' => $bare])>

@unless ($bare)
<a class="nv-skip" href="#konten">Lewati ke konten</a>

<div class="nv-strip" role="status">
    <span><span class="nv-dot {{ $jamAktif && ! $buka ? 'off' : '' }}" data-live-dot aria-hidden="true"></span><span data-live-status>{{ $statusTeks }}</span></span>
    <span data-live-layanan @if (! $jamAktif) hidden @endif><i class="bi bi-clock" aria-hidden="true"></i>Layanan sampai pukul <span data-live-selesai>{{ $jamAktif ? $jam('layanan_selesai') : '' }}</span></span>
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
        <div class="nv-sheet-status {{ $jamAktif && ! $buka ? 'off' : '' }}" data-live-sheet><span class="nv-dot" aria-hidden="true"></span><span data-live-status>{{ $statusTeks }}</span></div>

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
            @endauth
        </div>
    </div>
</div>

@endunless

@if ($bare)
    <main id="konten" tabindex="-1" class="flex-grow-1">{{ $slot }}</main>
@elseif ($full)
    <main id="konten" tabindex="-1" class="flex-grow-1">{{ $slot }}</main>
@else
    <main id="konten" tabindex="-1" class="container-xxl py-4 py-md-5 flex-grow-1" @if (! $wide) style="max-width: 46rem" @endif>
        {{ $slot }}
    </main>
@endif

{{-- Footer --}}
@unless ($fit || $bare)
<footer class="ft" aria-label="Footer">
    <div class="container-xxl">

        @if ($ctaBar)
            <div class="ft-cta">
                <div>
                    <h2>Daftar dari rumah, tanpa antre di loket</h2>
                    <p>Ambil nomor dari ponsel, lalu datang ke rumah sakit saat giliran Anda tinggal sekitar 30 menit lagi.</p>
                </div>
                <a href="{{ route('antrian.create') }}" class="btn btn-light btn-lg">Ambil nomor<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
            </div>
        @endif

        <div class="ft-main">
            <div class="ft-brand">
                <a href="{{ route('beranda') }}" class="d-inline-block" aria-label="{{ $nama }}, ke beranda">@include('partials.brand', ['terang' => true])</a>
                <p class="ft-desc">{{ $namaLengkap }}. Ambil nomor antrian poliklinik dari ponsel, lihat giliran Anda, lalu berangkat saat sudah dekat.</p>
                @if ($sosial)
                    <ul class="ft-sosial" aria-label="Media sosial">
                        @foreach ($sosial as [$ikon, $label, $url])
                            <li><a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ $label }}"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <nav aria-labelledby="ft-nav">
                <h2 class="ft-h" id="ft-nav">Layanan pasien</h2>
                <ul class="ft-links">
                    <li><a href="{{ route('antrian.create') }}">Ambil nomor antrian</a></li>
                    <li><a href="{{ route('beranda') }}#antrian">Antrian hari ini</a></li>
                    <li><a href="{{ route('beranda') }}#dokter">Jadwal dokter</a></li>
                    <li><a href="{{ route('antrian.layar') }}">Papan antrian</a></li>
                    <li><a href="{{ route('antrian.cek') }}">Cek tiket saya</a></li>
                </ul>
            </nav>

            <div>
                <h2 class="ft-h">Hubungi kami</h2>
                @if ($telepon)
                    <a class="ft-call" href="tel:{{ preg_replace('/[^\d+]/', '', $telepon) }}"><i class="bi bi-telephone-fill" aria-hidden="true"></i><span><small>Telepon</small><b>{{ $telepon }}</b></span></a>
                @endif
                @if ($kontak)
                    <ul class="ft-kontak">
                        @foreach ($kontak as [$ikon, $label, $isi, $url])
                            @continue($telepon && $label === 'Telepon')
                            <li>
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
                                <span><small>{{ $label }}</small>@if ($url)<a href="{{ $url }}" @if (str_starts_with($url, 'http')) target="_blank" rel="noopener" @endif>{{ $isi }}</a>@else<b>{{ $isi }}</b>@endif</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="ft-note">Hubungi petugas di loket informasi rumah sakit.</p>
                @endif
                @if (config('rumahsakit.maps'))
                    <a class="ft-arah" href="{{ config('rumahsakit.maps') }}" target="_blank" rel="noopener"><i class="bi bi-sign-turn-right" aria-hidden="true"></i>Petunjuk arah</a>
                @endif
            </div>

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
                    <p class="ft-note">Pendaftaran antrian online tersedia selama rumah sakit beroperasi.</p>
                @endif
            </div>
        </div>

        <div class="ft-bottom">
            <p class="ft-copy">&copy; {{ now()->year }} {{ $namaLengkap }}</p>
            <p class="ft-aman"><i class="bi bi-shield-lock" aria-hidden="true"></i>Data Anda hanya dipakai untuk keperluan antrian.</p>
            <div class="ft-aksi">
                @auth
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                @endauth
                <button type="button" class="ft-top" id="ftTop"><i class="bi bi-arrow-up" aria-hidden="true"></i>Ke atas</button>
            </div>
        </div>
    </div>
    <div class="ft-mark" aria-hidden="true">{{ $nama }}</div>
</footer>
@endunless

{{-- Bilah ajakan di HP --}}
@if ($ctaBar)
    <div class="nv-ctabar">
        <span><span class="nv-dot {{ $jamAktif && ! $buka ? 'off' : '' }}" aria-hidden="true"></span>{{ $statusPendek }}</span>
        @if ($telepon)
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $telepon) }}" class="nv-ctabar-tel" aria-label="Telepon {{ $telepon }}"><i class="bi bi-telephone-fill" aria-hidden="true"></i></a>
        @endif
        <a href="{{ route('antrian.create') }}" class="nv-btn nv-btn-cta">Ambil nomor<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
    </div>
@endif

<script>
(function () {
    var wrap = document.getElementById('nvWrap'), burger = document.getElementById('nvBurger'), sheet = document.getElementById('nvSheet');
    if (!wrap) return;

    // Navbar tetap gelap; .is-scrolled hanya menambah bayangan.
    // Dua ambang (24px aktif, 8px mati) mencegah bayangan berkedip di batas.
    var scrolled = false;
    function baca() {
        var y = window.scrollY;
        if (!scrolled && y > 24) scrolled = true;
        else if (scrolled && y < 8) scrolled = false;
        wrap.classList.toggle('is-scrolled', scrolled);
    }
    baca();
    window.addEventListener('scroll', baca, { passive: true });

    if (sheet && burger) {
        sheet.addEventListener('show.bs.offcanvas', function () { burger.classList.add('is-open'); });
        sheet.addEventListener('hidden.bs.offcanvas', function () { burger.classList.remove('is-open'); });
    }

    var atas = document.getElementById('ftTop');
    if (atas) atas.addEventListener('click', function () {
        var diam = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: diam ? 'auto' : 'smooth' });
    });
})();
</script>

{{-- Bila perangkat ini punya tiket aktif, tombol "Ambil nomor" langsung berubah menjadi "Antrian saya" (tanpa berkedip) --}}
<script>
(function () {
    try {
        var d = JSON.parse(localStorage.getItem('rs.tiket') || '[]'), n = new Date();
        var h = n.getFullYear() + '-' + String(n.getMonth() + 1).padStart(2, '0') + '-' + String(n.getDate()).padStart(2, '0');
        var t = d.filter(function (x) { return !x.tgl || x.tgl === h; })[0];
        if (!t) return;
        document.querySelectorAll('a[href$="/antrian/ambil"]').forEach(function (a) {
            for (var i = 0; i < a.childNodes.length; i++) {
                var c = a.childNodes[i];
                if (c.nodeType === 3 && /ambil/i.test(c.nodeValue)) {
                    a.dataset.asalHref = a.href; a.dataset.asalTeks = c.nodeValue;
                    a.href = t.url; c.nodeValue = 'Antrian saya';
                    break;
                }
            }
        });
    } catch (e) {}
})();
</script>

@include('partials.dev-masuk')
</body>
</html>
