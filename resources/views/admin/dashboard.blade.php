<x-app-layout>
    @php
        $jam = (int) now()->format('G');
        $sapaan = $jam < 11 ? 'pagi' : ($jam < 15 ? 'siang' : ($jam < 18 ? 'sore' : 'malam'));
        $namaDepan = explode(' ', trim(auth()->user()->name))[0];

        // [label, url, ikon, warna, keterangan, tab baru?]
        $akses = [
            ['Kelola poli', route('admin.poli.index'), 'hospital', 'primary', 'Tambah dan atur poli', false],
            ['Kelola dokter', route('admin.dokter.index'), 'person-badge', 'success', 'Dokter, jadwal, akun', false],
            ['Antrian hari ini', route('antrian.index'), 'people', 'warning', 'Panggil dan layani', false],
            ['Analitik', route('antrian.analitik'), 'bar-chart-line', 'info', 'Teori vs data riil', false],
            ['Riwayat', route('antrian.riwayat'), 'clock-history', 'navy', 'Filter dan unduh CSV', false],
            ['Papan TV', route('antrian.layar'), 'tv', 'primary', 'Layar ruang tunggu', true],
            ['Halaman pasien', route('antrian.create'), 'ticket-perforated', 'success', 'Form ambil nomor', true],
            ['Profil akun', route('profile.edit'), 'person-gear', 'danger', 'Data dan password', false],
        ];
    @endphp

    @include('partials.flash')

    <div class="ad-grid">

        {{-- Sapaan + ringkasan hari ini --}}
        <section class="ad-hero" aria-label="Ringkasan hari ini">
            <div class="ad-hero-top">
                <div>
                    <p class="ad-eyebrow"><i class="bi bi-calendar3"></i>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
                    <h1>Selamat {{ $sapaan }}, {{ $namaDepan }}</h1>
                    <p class="ad-lead">Ini kondisi klinik hari ini, dari antrian yang menunggu sampai pasien yang sudah selesai.</p>
                </div>
                <div class="ad-chips">
                    @if (\App\Services\JamOperasional::aktif())
                        @if (\App\Services\JamOperasional::pendaftaranDibuka())
                            <span class="ad-chip is-ok"><i class="bi bi-door-open"></i>Pendaftaran dibuka</span>
                        @else
                            <span class="ad-chip is-off"><i class="bi bi-door-closed"></i>Pendaftaran ditutup</span>
                        @endif
                        @if (\App\Services\JamOperasional::layananBerjalan())
                            <span class="ad-chip"><i class="bi bi-clock"></i>Layanan sampai {{ \App\Services\JamOperasional::tampil(config('operasional.layanan_selesai')) }}</span>
                        @else
                            <span class="ad-chip is-off"><i class="bi bi-moon"></i>Layanan selesai</span>
                        @endif
                    @else
                        <span class="ad-chip"><i class="bi bi-infinity"></i>Jam operasional bebas</span>
                    @endif
                </div>
            </div>

            <div class="ad-kpis">
                <div class="ad-kpi"><span class="ad-kpi-ico"><i class="bi bi-people"></i></span><div class="numeral">{{ $hariIni['menunggu'] }}</div><div class="ad-kpi-label">Menunggu</div></div>
                <div class="ad-kpi"><span class="ad-kpi-ico"><i class="bi bi-megaphone"></i></span><div class="numeral">{{ $hariIni['ditangani'] }}</div><div class="ad-kpi-label">Ditangani</div></div>
                <div class="ad-kpi"><span class="ad-kpi-ico"><i class="bi bi-check2-circle"></i></span><div class="numeral">{{ $hariIni['selesai'] }}</div><div class="ad-kpi-label">Selesai</div></div>
                <div class="ad-kpi"><span class="ad-kpi-ico"><i class="bi bi-person-x"></i></span><div class="numeral">{{ $hariIni['tidak_hadir'] }}</div><div class="ad-kpi-label">Tidak hadir</div></div>
                <div class="ad-kpi is-lead"><span class="ad-kpi-ico"><i class="bi bi-hourglass-split"></i></span><div class="numeral">{{ $rataTungguMenit ?? '-' }}@if ($rataTungguMenit !== null)<small> mnt</small>@endif</div><div class="ad-kpi-label">Rata-rata tunggu</div></div>
            </div>

            <div class="ad-days" aria-label="Pasien selesai 7 hari terakhir">
                <span class="ad-days-t">7 hari terakhir</span>
                @foreach ($tren as $hari)
                    <span class="ad-day {{ $hari['hari_ini'] ? 'is-today' : '' }} {{ $hari['jumlah'] > 0 ? 'has' : '' }}" title="{{ $hari['tanggal'] }}: {{ $hari['jumlah'] }} pasien selesai">
                        <b>{{ $hari['label'] }}</b><i>{{ $hari['jumlah'] }}</i>
                    </span>
                @endforeach
            </div>
        </section>

        {{-- Akses cepat --}}
        <section class="ad-card ad-area-quick">
            <header class="ad-card-head"><h2>Akses cepat</h2></header>
            <div class="ad-quick">
                @foreach ($akses as [$label, $url, $ikon, $warna, $ket, $tabBaru])
                    <a href="{{ $url }}" class="ad-tile" @if ($tabBaru) target="_blank" rel="noopener" @endif>
                        <span class="ad-ico ad-ico-{{ $warna }}"><i class="bi bi-{{ $ikon }}"></i></span>
                        <span class="ad-tile-text">
                            <span class="ad-tile-title">{{ $label }}@if ($tabBaru)<i class="bi bi-box-arrow-up-right"></i>@endif</span>
                            <span class="ad-tile-hint">{{ $ket }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        {{-- Status poli --}}
        <section class="ad-card ad-area-poli">
            <header class="ad-card-head">
                <h2>Status tiap poli</h2>
                <a href="{{ route('antrian.index') }}" class="ad-more">Semua antrian <i class="bi bi-arrow-right"></i></a>
            </header>

            @php $maksMenunggu = max(1, (int) $polis->max('menunggu_count')); @endphp
            <ul class="ad-poli">
                @forelse ($polis as $poli)
                    <li>
                        <span class="kode">{{ $poli->kode_poli }}</span>
                        <div class="ad-poli-body">
                            <div class="ad-poli-name">
                                {{ $poli->nama_poli }}
                                @if ($poli->menunggu_count > 10)<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-1">Ramai</span>@endif
                            </div>
                            <div class="ad-poli-stats">
                                <span><b>{{ $poli->menunggu_count }}</b> menunggu</span>
                                <span><b>{{ $poli->ditangani_count }}</b> ditangani</span>
                                <span><b>{{ $poli->selesai_count }}</b> selesai</span>
                            </div>
                            <div class="bar" aria-hidden="true"><span class="{{ $poli->menunggu_count > 10 ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $poli->menunggu_count > 0 ? max(6, round($poli->menunggu_count / $maksMenunggu * 100)) : 0 }}%"></span></div>
                        </div>
                        <div class="ad-poli-act">
                            <a href="{{ route('antrian.kelola', $poli) }}" class="btn btn-primary btn-sm">Kelola</a>
                            <a href="{{ route('antrian.display', $poli) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" aria-label="Buka layar TV {{ $poli->nama_poli }}"><i class="bi bi-tv"></i></a>
                        </div>
                    </li>
                @empty
                    <li class="ad-empty">Belum ada poli aktif. <a href="{{ route('admin.poli.create') }}">Tambahkan poli pertama</a>.</li>
                @endforelse
            </ul>
        </section>

        {{-- Perlu perhatian --}}
        <section class="ad-card ad-area-attn">
            <header class="ad-card-head">
                <h2>Perlu perhatian</h2>
                @if (count($perhatian) > 0)<span class="ad-count">{{ count($perhatian) }}</span>@endif
            </header>

            @forelse ($perhatian as $p)
                <div class="ad-note ad-note-{{ $p['tone'] }}">
                    <i class="bi bi-{{ $p['ikon'] }}"></i>
                    <div>
                        <div class="fw-semibold">{{ $p['judul'] }}</div>
                        <div class="small text-body-secondary">{{ $p['teks'] }}</div>
                        @if ($p['url'])<a href="{{ $p['url'] }}" class="ad-more mt-1 d-inline-block">{{ $p['aksi'] }} <i class="bi bi-arrow-right"></i></a>@endif
                    </div>
                </div>
            @empty
                <div class="ad-note ad-note-ok">
                    <i class="bi bi-patch-check"></i>
                    <div><div class="fw-semibold">Semuanya beres</div><div class="small text-body-secondary">Tidak ada yang perlu ditindaklanjuti saat ini.</div></div>
                </div>
            @endforelse
        </section>

        {{-- Tren 7 hari --}}
        <section class="ad-card ad-area-tren">
            <header class="ad-card-head">
                <h2>Pasien selesai, 7 hari terakhir</h2>
                <a href="{{ route('antrian.analitik') }}" class="ad-more">Analitik <i class="bi bi-arrow-right"></i></a>
            </header>
            <div class="ad-chart" role="img" aria-label="Grafik batang pasien selesai per hari selama 7 hari terakhir">
                @foreach ($tren as $hari)
                    <div class="ad-bar {{ $hari['hari_ini'] ? 'is-today' : '' }}" title="{{ $hari['tanggal'] }}: {{ $hari['jumlah'] }} pasien">
                        <span class="ad-bar-val">{{ $hari['jumlah'] }}</span>
                        <span class="ad-bar-fill" style="height: {{ $hari['jumlah'] > 0 ? max(6, round($hari['jumlah'] / $trenMaks * 100)) : 2 }}%"></span>
                        <span class="ad-bar-label">{{ $hari['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Aktivitas terbaru + data master --}}
        <section class="ad-card ad-area-akt">
            <header class="ad-card-head">
                <h2>Pendaftaran terbaru</h2>
                <a href="{{ route('antrian.riwayat') }}" class="ad-more">Riwayat <i class="bi bi-arrow-right"></i></a>
            </header>

            <ul class="ad-feed">
                @forelse ($terbaru as $a)
                    <li>
                        <span class="numeral ad-feed-no">{{ $a->nomor_antrian }}</span>
                        <div class="ad-feed-body">
                            <div class="fw-medium text-truncate">{{ $a->pasien?->nama }}</div>
                            <div class="small text-body-secondary">{{ $a->poli?->nama_poli }} &middot; {{ $a->waktu_daftar?->format('H.i') }}</div>
                        </div>
                        @include('partials.status', ['status' => $a->status])
                    </li>
                @empty
                    <li class="ad-empty">Belum ada pendaftaran hari ini.</li>
                @endforelse
            </ul>

            <footer class="ad-master">
                <a href="{{ route('admin.poli.index') }}"><b>{{ $ringkasan['jumlah_poli'] }}</b> poli</a>
                <a href="{{ route('admin.dokter.index') }}"><b>{{ $ringkasan['jumlah_dokter'] }}</b> dokter</a>
                <span><b>{{ $ringkasan['jumlah_user'] }}</b> akun</span>
            </footer>
        </section>
    </div>
</x-app-layout>
