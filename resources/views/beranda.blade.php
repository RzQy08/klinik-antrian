<x-public-layout title="Beranda" :full="true">

    @if (session('success') || session('error'))
        <div class="container-xxl pt-3">@include('partials.flash')</div>
    @endif

    @php
        $pertama = $polis->first();

        // Menu untuk staf yang sedang login. Pasien tidak melihat daftar ini.
        $menuStaf = [];
        if (auth()->check()) {
            $peranSaya = auth()->user()->role;

            $semuaMenu = [
                ['petugas', 'Antrian hari ini', route('antrian.index'), 'Ringkasan jumlah antrian semua poli', 'list-check'],
                ['petugas', 'Kelola satu poli', $pertama ? route('antrian.kelola', $pertama) : null, 'Panggil, panggil ulang, mulai layani, tandai tidak hadir', 'megaphone'],
                ['petugas', 'Analitik', route('antrian.analitik'), 'Waktu tunggu teori, data riil, dan rekomendasi dokter', 'bar-chart-line'],
                ['dokter', 'Pasien saya', route('antrian.dokter'), 'Mulai dan selesaikan pelayanan', 'person-badge'],
                ['admin', 'Ringkasan admin', route('admin.dashboard'), 'Pintu masuk ke data master', 'speedometer2'],
                ['admin', 'Kelola poli', route('admin.poli.index'), 'Tambah, ubah, nonaktifkan poli', 'hospital'],
                ['admin', 'Kelola dokter dan jadwal', route('admin.dokter.index'), 'Data dokter, jadwal praktik, akun login', 'calendar-week'],
            ];

            if (\Illuminate\Support\Facades\Route::has('antrian.riwayat')) {
                $semuaMenu[] = ['petugas', 'Riwayat dan laporan', route('antrian.riwayat'), 'Filter antrian lama dan unduh CSV', 'clock-history'];
            }

            foreach ($semuaMenu as [$perlu, $nama, $url, $keterangan, $ikon]) {
                $boleh = $perlu === 'petugas' ? in_array($peranSaya, ['admin', 'petugas'], true) : $peranSaya === $perlu;

                if ($boleh && $url) {
                    $menuStaf[] = compact('nama', 'url', 'keterangan', 'ikon');
                }
            }
        }
    @endphp

    {{-- ============ HERO ============ --}}
    <section class="bn-hero">
        <div class="container-xxl position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    @if (\App\Services\JamOperasional::aktif())
                        @if (\App\Services\JamOperasional::pendaftaranDibuka())
                            <span class="bn-status"><span class="bn-status-dot"></span>Pendaftaran buka sampai pukul {{ \App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup')) }}</span>
                        @else
                            <span class="bn-status bn-status-off"><span class="bn-status-dot"></span>Pendaftaran sedang tutup</span>
                        @endif
                    @else
                        <span class="bn-status"><span class="bn-status-dot"></span>Pendaftaran online dibuka</span>
                    @endif

                    <h1 class="bn-title">Ambil nomor dari HP,<br><span class="bn-title-accent">tak perlu antre di loket.</span></h1>
                    <p class="bn-lead">Pilih poli, isi NIK dan nama, nomor antrian langsung jadi. Perkiraan lama menunggu tampil di tiket, dan bila Anda mengisi nomor WhatsApp, kami kabari saat nomor Anda dipanggil.</p>

                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="{{ route('antrian.create') }}" class="btn btn-lg bn-btn-glow"><i class="bi bi-ticket-perforated me-2"></i>Ambil nomor antrian</a>
                        <a href="{{ route('antrian.layar') }}" class="btn btn-lg bn-btn-ghost"><i class="bi bi-tv me-2"></i>Lihat papan antrian</a>
                    </div>

                    <ul class="bn-facts list-unstyled mb-0">
                        <li><i class="bi bi-person-check"></i>Tanpa buat akun</li>
                        <li><i class="bi bi-hourglass-split"></i>Ada perkiraan waktu tunggu</li>
                        <li><i class="bi bi-whatsapp"></i>Dikabari lewat WhatsApp</li>
                    </ul>
                </div>

                {{-- Papan antrian ilustrasi (kaca) --}}
                <div class="col-lg-5" aria-hidden="true">
                    <div class="bn-stage">
                        <div class="bn-glass bn-board">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="bn-board-label"><span class="bn-live-dot"></span>Sedang dipanggil</span>
                                <span class="bn-board-label">{{ now()->format('H.i') }} WIB</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="kode kode-lg">A</span>
                                <span class="fw-semibold fs-5 text-white">Poli Umum</span>
                            </div>
                            <div class="bn-board-no numeral">A017</div>
                            <ul class="bn-board-rows list-unstyled mb-0">
                                <li><span class="kode">B</span><span>Poli Gigi</span><b class="numeral">B004</b></li>
                                <li><span class="kode">C</span><span>Poli Anak</span><b class="numeral">C009</b></li>
                                <li><span class="kode">D</span><span>Poli KIA</span><b class="numeral">D012</b></li>
                            </ul>
                        </div>

                        <div class="bn-chip bn-chip-1">
                            <span class="bn-chip-icon bn-chip-icon-warn"><i class="bi bi-megaphone-fill"></i></span>
                            <span class="lh-sm"><b class="d-block">Dipanggil A017</b><small class="text-body-secondary">Silakan ke Poli Umum</small></span>
                        </div>
                        <div class="bn-chip bn-chip-2">
                            <span class="bn-chip-icon bn-chip-icon-ok"><i class="bi bi-whatsapp"></i></span>
                            <span class="lh-sm"><b class="d-block">Giliran Anda!</b><small class="text-body-secondary">Pesan masuk ke WhatsApp</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ STATISTIK (menimpa tepi bawah hero) ============ --}}
    <section class="bn-stats-wrap" aria-label="Ringkasan hari ini">
        <div class="container-xxl">
            <div class="bn-stats">
                <div class="bn-stat"><span class="bn-stat-ico"><i class="bi bi-people"></i></span><div><div class="numeral">{{ $statistik['menunggu'] }}</div><div class="bn-stat-label">Pasien menunggu</div></div></div>
                <div class="bn-stat"><span class="bn-stat-ico bn-stat-ico-ok"><i class="bi bi-check2-circle"></i></span><div><div class="numeral">{{ $statistik['selesai'] }}</div><div class="bn-stat-label">Sudah dilayani</div></div></div>
                <div class="bn-stat"><span class="bn-stat-ico bn-stat-ico-navy"><i class="bi bi-hospital"></i></span><div><div class="numeral">{{ $statistik['poli'] }}</div><div class="bn-stat-label">Poli buka</div></div></div>
                <div class="bn-stat"><span class="bn-stat-ico bn-stat-ico-warn"><i class="bi bi-person-badge"></i></span><div><div class="numeral">{{ $statistik['dokter'] }}</div><div class="bn-stat-label">Dokter aktif</div></div></div>
            </div>
        </div>
    </section>

    {{-- ============ ANTRIAN LANGSUNG ============ --}}
    <section id="antrian" class="bn-band bn-band-stripes" style="scroll-margin-top: 4.5rem">
        <div class="container-xxl">
            <div class="bn-head">
                <div>
                    <span class="bn-eyebrow"><span class="bn-live-dot bn-live-dot-dark"></span>Hari ini</span>
                    <h2 class="bn-h2">Antrian di tiap poli</h2>
                    <p class="bn-sub">Angka di sini berubah sendiri, tidak perlu menyegarkan halaman.</p>
                </div>
                <a href="{{ route('antrian.layar') }}" class="btn btn-primary"><i class="bi bi-tv me-2"></i>Buka papan semua poli</a>
            </div>

            <livewire:beranda-poli />
        </div>
    </section>

    {{-- ============ MENU STAF, hanya setelah login ============ --}}
    @if ($menuStaf)
        <section class="bn-band bn-band-dots">
            <div class="container-xxl">
                <div class="bn-head"><div><span class="bn-eyebrow">Staf</span><h2 class="bn-h2">Menu Anda</h2></div></div>
                <div class="row g-3">
                    @foreach ($menuStaf as $menu)
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ $menu['url'] }}" class="bn-menu h-100">
                                <span class="bn-menu-ico"><i class="bi bi-{{ $menu['ikon'] }}"></i></span>
                                <span>
                                    <span class="d-block fw-semibold">{{ $menu['nama'] }}</span>
                                    <span class="d-block small text-body-secondary">{{ $menu['keterangan'] }}</span>
                                </span>
                                <i class="bi bi-arrow-up-right bn-menu-go"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ CARA KERJA ============ --}}
    <section id="cara-kerja" class="bn-band bn-band-plus" style="scroll-margin-top: 4.5rem">
        <div class="container-xxl">
            <div class="bn-head bn-head-center">
                <div>
                    <span class="bn-eyebrow">Alur</span>
                    <h2 class="bn-h2">Cara kerjanya</h2>
                    <p class="bn-sub mx-auto">Dari rumah sampai masuk ruang periksa.</p>
                </div>
            </div>

            <div class="bn-bento">
                <article class="bn-tile bn-tile-a">
                    <span class="bn-step">1</span>
                    <h3>Ambil nomor</h3>
                    <p>Pilih poli, lalu isi NIK dan nama. Tiket langsung keluar beserta perkiraan lama menunggu.</p>
                    <div class="bn-mini-form" aria-hidden="true">
                        <span class="bn-mini-chip is-on">Poli Umum</span><span class="bn-mini-chip">Poli Gigi</span><span class="bn-mini-chip">Poli Anak</span>
                        <span class="bn-mini-input">3201 &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; 0004</span>
                    </div>
                </article>

                <article class="bn-tile bn-tile-b">
                    <span class="bn-step">2</span>
                    <h3>Pantau giliran</h3>
                    <p>Tiket di HP ikut berubah sendiri, jadi Anda tahu sudah berapa orang di depan.</p>
                    <div class="bn-queue" aria-hidden="true"><span></span><span></span><span></span><span class="is-me">Anda</span></div>
                </article>

                <article class="bn-tile bn-tile-c">
                    <span class="bn-step">3</span>
                    <h3>Datang saat dipanggil</h3>
                    <p>Nomor Anda muncul di layar ruang tunggu, dan tiket berubah menjadi "Giliran Anda!".</p>
                </article>

                <article class="bn-tile bn-tile-wa">
                    <i class="bi bi-whatsapp bn-tile-icon"></i>
                    <h3>Kabar lewat WhatsApp</h3>
                    <p>Isi nomor HP sekali saja. Pesan masuk begitu nomor Anda dipanggil.</p>
                </article>

                <article class="bn-tile bn-tile-navy">
                    <span class="bn-tile-kicker">Contoh perkiraan di tiket</span>
                    <div class="numeral bn-tile-big">&plusmn; 12 <small>menit</small></div>
                    <p>Dihitung dengan teori antrian dari data pelayanan 14 hari terakhir, bukan sekadar tebakan.</p>
                </article>

                <article class="bn-tile bn-tile-tv">
                    <i class="bi bi-tv bn-tile-icon"></i>
                    <h3>Layar ruang tunggu</h3>
                    <p>Nomor di papan besar diperbarui tiap 2 detik dan terbaca dari jauh, jadi pasien tak perlu bolak-balik bertanya ke loket.</p>
                    <a href="{{ route('antrian.layar') }}" class="bn-link mt-auto pt-3">Lihat papan antrian <i class="bi bi-arrow-right"></i></a>
                </article>
            </div>
        </div>
    </section>

    {{-- ============ AJAKAN ============ --}}
    <section class="bn-cta-band">
        <div class="container-xxl">
            <div class="bn-cta">
                <div>
                    <h2>Siap ambil nomor?</h2>
                    <p>Cukup NIK dan nama, tanpa buat akun.</p>
                </div>
                <a href="{{ route('antrian.create') }}" class="btn btn-lg bn-btn-glow">Ambil nomor sekarang<i class="bi bi-arrow-right ms-2"></i></a>
            </div>
        </div>
    </section>

    {{-- Akses cepat untuk pengembangan: melayang di pojok, hanya saat APP_ENV=local --}}
    @if (app()->isLocal())
        <details class="bn-dev">
            <summary aria-label="Akses cepat pengembangan"><i class="bi bi-lightning-charge-fill"></i><span>Dev</span></summary>
            <div class="bn-dev-panel">
                <div class="bn-dev-title">Masuk cepat</div>
                @auth
                    <div class="bn-dev-now">Masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</div>
                @endauth
                @foreach (['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'] as $peran => $nama)
                    <form method="POST" action="{{ route('demo.masuk', $peran) }}">
                        @csrf
                        <button type="submit"><i class="bi bi-person-circle"></i>{{ $nama }}</button>
                    </form>
                @endforeach
            </div>
        </details>
    @endif
</x-public-layout>
