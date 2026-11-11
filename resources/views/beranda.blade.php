<x-public-layout  :full="true">

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

        $praktikLabel = [
            'berlangsung' => ['ok', 'Sedang praktik'],
            'belum' => ['warn', 'Belum mulai'],
            'selesai' => ['off', 'Praktik selesai'],
            'libur' => ['off', 'Tidak praktik'],
        ];

        $telepon = config('rumahsakit.telepon');
        $whatsapp = config('rumahsakit.whatsapp');
        $alamat = config('rumahsakit.alamat');
        $email = config('rumahsakit.email');

        // Warna pembeda tiap poli (data-w, 0 sampai 5). Urutan poli sama dengan kartu Livewire: kode poli.
        $indeksPoli = $polis->pluck('nama_poli')->values()->flip();

        // Kartu info kaca di dasar hero. Yang datanya kosong di konfigurasi tidak ditampilkan.
        $jamLayanan = config('rumahsakit.jam_layanan') ?: (\App\Services\JamOperasional::aktif()
            ? 'Sampai pukul '.\App\Services\JamOperasional::tampil((string) \App\Services\JamOperasional::jam('layanan_selesai'))
            : null);
        $nomorWa = $whatsapp ? preg_replace('/^0/', '62', preg_replace('/\D/', '', $whatsapp)) : null;

        $info = array_values(array_filter([
            $jamLayanan ? ['clock', 'Jam layanan', $jamLayanan, null] : null,
            $telepon
                ? ['telephone', 'Telepon', $telepon, 'tel:'.preg_replace('/[^\d+]/', '', $telepon)]
                : ($whatsapp ? ['whatsapp', 'WhatsApp', $whatsapp, 'https://wa.me/'.$nomorWa] : null),
            $alamat
                ? ['geo-alt', 'Alamat', $alamat, config('rumahsakit.maps')]
                : ($email ? ['envelope', 'Email', $email, 'mailto:'.$email] : null),
        ]));
    @endphp

    {{-- ============ HERO (menyelip di bawah navbar pil) ============ --}}
    <section class="hm-hero" id="beranda">
        <svg class="hm-ecg" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0 84H430L452 84 466 58 482 108 500 14 518 96 532 84 560 84H930L950 84 962 66 976 100 988 84H1440"/>
        </svg>

        <div class="container-xxl position-relative">
            <div class="hm-hero-grid">
                <div class="hm-hero-copy">
                    @if (\App\Services\JamOperasional::aktif())
                        @if (\App\Services\JamOperasional::pendaftaranDibuka())
                            <span class="hm-pill"><span class="hm-dot"></span>Pendaftaran buka sampai pukul {{ \App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam('pendaftaran_tutup')) }}</span>
                        @else
                            <span class="hm-pill hm-pill-off"><span class="hm-dot"></span>Pendaftaran sedang tutup</span>
                        @endif
                    @else
                        <span class="hm-pill"><span class="hm-dot"></span>Pendaftaran online dibuka</span>
                    @endif

                    <h1 class="hm-title">Daftar dari rumah, <span class="hm-accent">tak perlu antre di loket.</span></h1>
                    <p class="hm-lead">Pilih poli, isi NIK dan nama, nomor antrian langsung jadi. Pantau dari HP dan tiba di rumah sakit saat giliran tinggal sekitar 30 menit.</p>

                    <div class="hm-cta-row">
                        <a href="{{ route('antrian.create') }}" class="btn btn-lg hm-btn hm-btn-primary"><i class="bi bi-ticket-perforated" aria-hidden="true"></i>Ambil nomor</a>
                        <a href="{{ route('antrian.layar') }}" class="btn btn-lg hm-btn hm-btn-ghost"><i class="bi bi-tv" aria-hidden="true"></i>Lihat papan</a>
                    </div>

                    <ul class="hm-facts list-unstyled mb-0">
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Tanpa buat akun</li>
                        <li><i class="bi bi-hourglass-split" aria-hidden="true"></i>Perkiraan waktu tunggu</li>
                        <li><i class="bi bi-whatsapp" aria-hidden="true"></i>Kabar lewat WhatsApp</li>
                    </ul>
                </div>

                {{-- Panel antrian hari ini: membaca data langsung, berganti poli otomatis (resources/js/beranda-antrian.js) --}}
                <aside class="hm-panel" id="antrian-langsung" aria-label="Antrian hari ini"
                       data-hero-antrian
                       data-endpoint="{{ route('antrian.hari-ini') }}"
                       data-poll="5000"
                       data-putar="6000">
                    <div class="hm-panel-head">
                        <div>
                            <span class="hm-eyebrow">Antrian hari ini</span>
                            <time class="hm-panel-date" data-tanggal></time>
                        </div>
                        <div class="hm-panel-tools">
                            <span class="hm-live" data-mode><i aria-hidden="true"></i><span>Langsung</span></span>
                            <button type="button" class="hm-icon-btn" data-jeda aria-pressed="false" aria-label="Jeda pergantian poli otomatis" title="Jeda atau lanjutkan">
                                <i class="bi bi-pause-fill hm-i-pause" aria-hidden="true"></i><i class="bi bi-play-fill hm-i-play" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="hm-stage" data-stage>
                        <div class="hm-stage-note">Memuat antrian…</div>
                    </div>
                    <div class="hm-tabs" data-tabs role="group" aria-label="Pilih poli"></div>
                    <p class="hm-panel-foot"><i class="bi bi-clock" aria-hidden="true"></i><span data-diperbarui>Menunggu data…</span></p>

                    <script type="application/json" data-awal>@json($ringkasan, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
                    <noscript><p class="hm-stage-note">Aktifkan JavaScript untuk melihat nomor yang sedang dipanggil, atau buka <a href="{{ route('antrian.layar') }}">papan antrian</a>.</p></noscript>
                </aside>
            </div>

            <ul class="hm-info" aria-label="Informasi layanan">
                @foreach ($info as [$ikon, $label, $isi, $url])
                    <li>
                        @if ($url)
                            <a class="hm-info-item" href="{{ $url }}" @if (str_starts_with($url, 'http')) target="_blank" rel="noopener" @endif>
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><span><small>{{ $label }}</small><b>{{ $isi }}</b></span>
                            </a>
                        @else
                            <div class="hm-info-item">
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><span><small>{{ $label }}</small><b>{{ $isi }}</b></span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============ CARA KERJA ============ --}}
    <section id="cara-kerja" class="hm-section hm-fit hm-about-sec">
        <div class="container-xxl hm-about">
            <div class="hm-about-media">
                <div class="hm-about-visual" aria-hidden="true">
                    <span class="hm-offset"></span>
                    <div class="hm-chip hm-chip-a"><i class="bi bi-bell-fill"></i>2 nomor lagi giliran Anda</div>
                    <div class="hm-ticket">
                        <div class="hm-ticket-head"><span>{{ config('rumahsakit.nama', 'RS Bersama') }}</span><small>Tiket antrian</small></div>
                        <div class="hm-ticket-body">
                            <small>Nomor antrian Anda</small>
                            <strong class="numeral">A014</strong>
                            <p>Poli Umum</p>
                        </div>
                        <div class="hm-ticket-foot">
                            <div><span>Perkiraan dipanggil</span><b>&plusmn; 25 menit</b></div>
                            <span class="hm-barcode"></span>
                        </div>
                    </div>
                    <div class="hm-chip hm-chip-b"><i class="bi bi-whatsapp"></i>Kabar masuk ke WhatsApp</div>
                </div>
                <a href="#langkah" class="hm-orb" aria-label="Lihat empat langkah mengambil nomor"><i class="bi bi-arrow-down" aria-hidden="true"></i></a>
            </div>

            <div>
                <span class="hm-eyebrow hm-eyebrow-soft">Alur</span>
                <h2 class="hm-h2">Daftar dari rumah, tiba di rumah sakit saat giliran dekat</h2>
                <p class="hm-sub">Nomor dihitung dengan teori antrian dari data pelayanan 14 hari terakhir, bukan sekadar tebakan. Anda bisa mendaftar dari mana saja, lalu datang saat giliran sudah dekat, biasanya sekitar 30 menit sebelumnya.</p>

                <ul class="hm-checks">
                    <li><i class="bi bi-check-lg" aria-hidden="true"></i>Daftar dari rumah, tanpa antre di loket</li>
                    <li><i class="bi bi-check-lg" aria-hidden="true"></i>Perkiraan waktu tunggu dari data 14 hari terakhir</li>
                    <li><i class="bi bi-check-lg" aria-hidden="true"></i>Tiket di HP ikut berubah sesuai antrian</li>
                    <li><i class="bi bi-check-lg" aria-hidden="true"></i>Nomor tampil di layar ruang tunggu</li>
                </ul>
            </div>
        </div>
    </section>

    {{-- ============ STATISTIK ============ --}}
    <section class="hm-band" aria-label="Ringkasan hari ini">
        <div class="container-xxl">
            <div class="hm-stats">
                <div class="hm-stat"><i class="bi bi-people hm-stat-ico" aria-hidden="true"></i><div><b class="numeral" data-stat="menunggu">{{ $statistik['menunggu'] }}</b><span>Pasien menunggu</span></div></div>
                <div class="hm-stat"><i class="bi bi-check2-circle hm-stat-ico" aria-hidden="true"></i><div><b class="numeral" data-stat="selesai">{{ $statistik['selesai'] }}</b><span>Sudah dilayani</span></div></div>
                <div class="hm-stat"><i class="bi bi-hospital hm-stat-ico" aria-hidden="true"></i><div><b class="numeral" data-stat="poli">{{ $statistik['poli'] }}</b><span>Poli buka</span></div></div>
                <div class="hm-stat"><i class="bi bi-person-badge hm-stat-ico" aria-hidden="true"></i><div><b class="numeral" data-stat="dokter">{{ $statistik['dokter'] }}</b><span>Dokter aktif</span></div></div>
            </div>
        </div>
    </section>

    {{-- ============ EMPAT LANGKAH + PANDUAN ============ --}}
    <section id="langkah" class="hm-section hm-fit hm-soft hm-lang-sec">
        <div class="container-xxl">
            <div class="hm-head">
                <div>
                    <span class="hm-eyebrow hm-eyebrow-soft">Panduan pasien</span>
                    <h2 class="hm-h2">Empat langkah, dari HP sampai ruang periksa</h2>
                    <p class="hm-sub">Tidak perlu akun dan tidak perlu antre di loket.</p>
                </div>
            </div>

            <ol class="hm-lang">
                <li class="hm-lg" data-w="0">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="{{ route('antrian.create') }}">
                        <span class="hm-lg-no" aria-hidden="true">01</span>
                        <h3>Daftar dari rumah</h3>
                        <p>Pilih poli, isi NIK dan nama dari HP. Tiket langsung keluar.</p>
                        <em>Isi data diri <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="1">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-phone" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="#antrian">
                        <span class="hm-lg-no" aria-hidden="true">02</span>
                        <h3>Pantau giliran</h3>
                        <p>Tiket di HP ikut berubah sesuai antrian, lengkap dengan perkiraan waktu tunggu.</p>
                        <em>Lihat antrian poli <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="2">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-hospital" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="#cara-kerja">
                        <span class="hm-lg-no" aria-hidden="true">03</span>
                        <h3>Tiba saat giliran dekat</h3>
                        <p>Usahakan sudah di rumah sakit saat nomor tinggal sekitar 30 menit lagi. Isi WhatsApp untuk dikabari.</p>
                        <em>Cara hitungnya <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="3">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-tv" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="{{ route('antrian.layar') }}">
                        <span class="hm-lg-no" aria-hidden="true">04</span>
                        <h3>Masuk saat dipanggil</h3>
                        <p>Nomor besar di papan antrian adalah nomor yang sedang dipanggil.</p>
                        <em>Lihat papan <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
            </ol>

            <div class="hm-rule" role="note">
                <span class="hm-rule-node"><i class="bi bi-house-door" aria-hidden="true"></i><span><small>Mulai</small><b>Daftar dari rumah</b></span></span>
                <span class="hm-rule-line" aria-hidden="true"><b><i class="bi bi-clock" aria-hidden="true"></i>&plusmn; 30 menit</b></span>
                <span class="hm-rule-node"><i class="bi bi-hospital" aria-hidden="true"></i><span><small>Paling lambat</small><b>Sudah di rumah sakit</b></span></span>
            </div>
        </div>
    </section>

    {{-- ============ ANTRIAN TIAP POLI ============ --}}
    <section id="antrian" class="bn-band bn-band-stripes hm-fit">
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

    {{-- ============ DOKTER PRAKTIK HARI INI ============ --}}
    @php
        $perPoli = collect($dokterHariIni)->groupBy('poli');
        $sedangPraktik = collect($dokterHariIni)->where('praktik', 'berlangsung')->count();
    @endphp
    <section id="dokter" class="hm-section hm-fit hm-dokter">
        <div class="container-xxl">
            <div class="hm-head">
                <div>
                    <span class="hm-eyebrow hm-eyebrow-soft">Jadwal</span>
                    <h2 class="hm-h2">Dokter praktik hari ini</h2>
                    <p class="hm-sub">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
                </div>
                @if ($dokterHariIni)
                    <p class="hm-dk-sum"><span><b class="numeral">{{ count($dokterHariIni) }}</b>dokter</span><span class="is-ok"><b class="numeral">{{ $sedangPraktik }}</b>sedang praktik</span></p>
                @endif
            </div>

            @if ($dokterHariIni)
                <div class="hm-dk" data-dk>
                    @if ($perPoli->count() > 1)
                        <div class="hm-dk-tabs" role="group" aria-label="Saring menurut poli">
                            <button type="button" class="hm-dk-tab" data-dk-tab="*" aria-pressed="true">Semua<b>{{ count($dokterHariIni) }}</b></button>
                            @foreach ($perPoli as $namaPoli => $daftar)
                                <button type="button" class="hm-dk-tab" data-w="{{ ($indeksPoli->get($namaPoli, 0)) % 6 }}" data-dk-tab="{{ \Illuminate\Support\Str::slug($namaPoli) }}" aria-pressed="false"><i aria-hidden="true"></i>{{ $namaPoli }}<b>{{ $daftar->count() }}</b></button>
                            @endforeach
                        </div>
                    @endif

                    <div class="hm-dk-grid">
                        @foreach (collect($dokterHariIni)->sortBy(fn ($d) => $d['praktik'] === 'berlangsung' ? 0 : 1)->values() as $d)
                            @php [$kelas, $teks] = $praktikLabel[$d['praktik']] ?? $praktikLabel['libur']; @endphp
                            <article class="hm-dkc is-{{ $kelas }}" data-dk-kartu data-poli="{{ \Illuminate\Support\Str::slug($d['poli']) }}" data-w="{{ ($indeksPoli->get($d['poli'], 0)) % 6 }}">
                                <span class="hm-dkc-av" aria-hidden="true">{{ $d['inisial'] }}</span>
                                <div class="hm-dkc-info">
                                    <h3>{{ $d['nama'] }}</h3>
                                    <p>{{ $d['poli'] }}</p>
                                </div>
                                <span class="hm-badge hm-badge-{{ $kelas }}">{{ $teks }}</span>
                                @if ($d['jam'])<p class="hm-dkc-jam"><i class="bi bi-clock" aria-hidden="true"></i><span>{{ $d['jam'] }}</span></p>@endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="hm-empty">Belum ada dokter dengan jadwal praktik hari ini.</p>
            @endif
        </div>
    </section>

    <div class="hm-closing">
    {{-- ============ FASILITAS ============ --}}
    <section class="hm-fas">
        <div class="container-xxl">
            <div class="hm-fas-grid">
                <div>
                    <h2>Layanan digital untuk kunjungan yang lebih ringan</h2>
                    <p>Dari tiket di HP sampai layar besar di ruang tunggu, semua dibuat supaya Anda tahu kapan harus datang.</p>
                </div>
                <div class="hm-fas-tiles">
                    <a class="hm-ftile" href="{{ route('antrian.layar') }}"><i class="bi bi-tv" aria-hidden="true"></i>Papan antrian ruang tunggu</a>
                    <div class="hm-ftile"><i class="bi bi-ticket-perforated" aria-hidden="true"></i>Tiket digital di HP</div>
                    <div class="hm-ftile"><i class="bi bi-whatsapp" aria-hidden="true"></i>Kabar lewat WhatsApp</div>
                    <div class="hm-ftile"><i class="bi bi-hourglass-split" aria-hidden="true"></i>Perkiraan waktu tunggu</div>
                    <div class="hm-ftile"><i class="bi bi-person-check" aria-hidden="true"></i>Daftar tanpa akun</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ AJAKAN ============ --}}
    <section class="hm-cta-band" id="kontak">
        <div class="container-xxl">
            <div class="hm-cta {{ ($telepon || $whatsapp) ? '' : 'hm-cta-solo' }}">
                <div class="hm-cta-copy">
                    <h2>Butuh bantuan mengambil nomor?</h2>
                    <p>Cukup NIK dan nama, tanpa buat akun. Petugas kami siap membantu bila Anda kesulitan.</p>
                </div>
                @if ($telepon || $whatsapp)
                    <div class="hm-help">
                        @if ($telepon)
                            <a class="hm-help-row" href="tel:{{ preg_replace('/[^\d+]/', '', $telepon) }}">
                                <i class="bi bi-telephone-fill" aria-hidden="true"></i>
                                <span><small>Telepon</small><b>{{ $telepon }}</b></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        @endif
                        @if ($whatsapp)
                            <a class="hm-help-row" href="https://wa.me/{{ $nomorWa }}" target="_blank" rel="noopener">
                                <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                <span><small>WhatsApp</small><b>{{ $whatsapp }}</b></span>
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
    </div>

    {{-- Akses cepat untuk pengembangan: melayang di pojok, hanya saat APP_ENV=local.
         Tombol x menyembunyikannya sampai halaman dimuat ulang (sengaja tanpa penyimpanan, cocok untuk screenshot). --}}
    @if (\App\Support\AksesLokal::diizinkan())
        <div class="bn-dev" data-dev>
            <details class="bn-dev-menu">
                <summary aria-label="Akses cepat pengembangan">
                    <span class="bn-dev-dot" aria-hidden="true"></span>
                    <span class="bn-dev-label">DEV</span>
                    <span class="bn-dev-env">local</span>
                </summary>
                <div class="bn-dev-panel">
                    <div class="bn-dev-head">
                        <span class="bn-dev-title">Masuk cepat</span>
                        @auth
                            <span class="bn-dev-now">Aktif: <strong>{{ auth()->user()->name }}</strong></span>
                        @else
                            <span class="bn-dev-now">Belum masuk</span>
                        @endauth
                    </div>
                    @foreach ([
                        'admin' => ['Admin', 'Data master dan laporan', 'shield-lock'],
                        'petugas' => ['Petugas', 'Panggil dan kelola antrian', 'megaphone'],
                        'dokter' => ['Dokter', 'Layani pasien', 'person-badge'],
                    ] as $peran => [$nama, $ket, $ikon])
                        <form method="POST" action="{{ route('demo.masuk', $peran) }}">
                            @csrf
                            <button type="submit" @class(['is-aktif' => auth()->check() && auth()->user()->role === $peran])>
                                <span class="bn-dev-ico"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i></span>
                                <span class="bn-dev-txt"><b>{{ $nama }}</b><small>{{ $ket }}</small></span>
                                <i class="bi bi-arrow-right-short bn-dev-go" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endforeach
                    <button type="button" class="bn-dev-sembunyi" data-dev-hide><i class="bi bi-eye-slash" aria-hidden="true"></i>Sembunyikan sampai dimuat ulang</button>
                </div>
            </details>
            <button type="button" class="bn-dev-x" data-dev-hide aria-label="Sembunyikan panel Dev sampai halaman dimuat ulang" title="Sembunyikan (muncul lagi saat dimuat ulang)"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        <script>
            // Delegasi klik: aman dipakai berulang, dan tidak menyimpan apa pun, jadi reload memunculkannya lagi.
            if (!window.__devHide) {
                window.__devHide = true;
                document.addEventListener('click', function (e) {
                    var b = e.target.closest('[data-dev-hide]');
                    if (b) { var d = b.closest('[data-dev]'); if (d) d.hidden = true; }
                });
            }
        </script>
    @endif
</x-public-layout>
