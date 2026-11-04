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
    @endphp

    {{-- ============ HERO ============ --}}
    <section class="hm-hero" id="beranda">
        <div class="container-xxl position-relative">
            <div class="hm-hero-grid">
                <div class="hm-hero-copy">
                    @if (\App\Services\JamOperasional::aktif())
                        @if (\App\Services\JamOperasional::pendaftaranDibuka())
                            <span class="hm-pill"><span class="hm-dot"></span>Pendaftaran buka sampai pukul {{ \App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup')) }}</span>
                        @else
                            <span class="hm-pill hm-pill-off"><span class="hm-dot"></span>Pendaftaran sedang tutup</span>
                        @endif
                    @else
                        <span class="hm-pill"><span class="hm-dot"></span>Pendaftaran online dibuka</span>
                    @endif

                    <h1 class="hm-title">Ambil nomor dari HP, <span class="hm-accent">tak perlu antre di loket.</span></h1>
                    <p class="hm-lead">Pilih poli, isi NIK dan nama, nomor antrian langsung jadi. Perkiraan lama menunggu tampil di tiket, dan bila Anda mengisi nomor WhatsApp, kami kabari saat nomor Anda dipanggil.</p>

                    <div class="hm-cta-row">
                        <a href="{{ route('antrian.create') }}" class="btn btn-lg hm-btn hm-btn-primary"><i class="bi bi-ticket-perforated me-2" aria-hidden="true"></i>Ambil nomor antrian</a>
                        <a href="{{ route('antrian.layar') }}" class="btn btn-lg hm-btn hm-btn-ghost"><i class="bi bi-tv me-2" aria-hidden="true"></i>Lihat papan antrian</a>
                    </div>

                    <ul class="hm-facts list-unstyled mb-0">
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Tanpa buat akun</li>
                        <li><i class="bi bi-hourglass-split" aria-hidden="true"></i>Ada perkiraan waktu tunggu</li>
                        <li><i class="bi bi-whatsapp" aria-hidden="true"></i>Dikabari lewat WhatsApp</li>
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
                        <div class="hm-stage-note">Memuat data antrian…</div>
                    </div>
                    <div class="hm-tabs" data-tabs role="group" aria-label="Pilih poli"></div>
                    <p class="hm-panel-foot"><i class="bi bi-clock" aria-hidden="true"></i><span data-diperbarui>Menunggu data…</span></p>

                    <script type="application/json" data-awal>@json($ringkasan, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
                    <noscript><p class="hm-stage-note">Aktifkan JavaScript untuk melihat nomor yang sedang dipanggil, atau buka <a href="{{ route('antrian.layar') }}">papan antrian</a>.</p></noscript>
                </aside>
            </div>
        </div>
    </section>

    {{-- ============ STATISTIK (menimpa tepi bawah hero) ============ --}}
    <section class="hm-stats-wrap" aria-label="Ringkasan hari ini">
        <div class="container-xxl">
            <div class="hm-stats">
                <div class="hm-stat"><span class="hm-stat-ico"><i class="bi bi-people" aria-hidden="true"></i></span><div><b class="numeral" data-stat="menunggu">{{ $statistik['menunggu'] }}</b><span>Pasien menunggu</span></div></div>
                <div class="hm-stat"><span class="hm-stat-ico hm-ico-ok"><i class="bi bi-check2-circle" aria-hidden="true"></i></span><div><b class="numeral" data-stat="selesai">{{ $statistik['selesai'] }}</b><span>Sudah dilayani</span></div></div>
                <div class="hm-stat"><span class="hm-stat-ico hm-ico-navy"><i class="bi bi-hospital" aria-hidden="true"></i></span><div><b class="numeral" data-stat="poli">{{ $statistik['poli'] }}</b><span>Poli buka</span></div></div>
                <div class="hm-stat"><span class="hm-stat-ico hm-ico-warn"><i class="bi bi-person-badge" aria-hidden="true"></i></span><div><b class="numeral" data-stat="dokter">{{ $statistik['dokter'] }}</b><span>Dokter aktif</span></div></div>
            </div>
        </div>
    </section>

    {{-- ============ CARA KERJA ============ --}}
    <section id="cara-kerja" class="hm-section hm-about-sec">
        <div class="container-xxl hm-about">
            <div class="hm-about-visual" aria-hidden="true">
                <div class="hm-chip hm-chip-a"><i class="bi bi-bell-fill"></i>2 nomor lagi giliran Anda</div>
                <div class="hm-ticket">
                    <small>Nomor antrian Anda</small>
                    <strong class="numeral">A014</strong>
                    <p>Poli Umum</p>
                    <div class="hm-ticket-row"><span>Perkiraan dipanggil</span><b>&plusmn; 25 menit</b></div>
                </div>
                <div class="hm-chip hm-chip-b"><i class="bi bi-whatsapp"></i>Kabar masuk ke WhatsApp</div>
            </div>

            <div>
                <span class="hm-eyebrow hm-eyebrow-soft">Alur</span>
                <h2 class="hm-h2">Cara kerjanya</h2>
                <p class="hm-sub">Dari rumah sampai masuk ruang periksa, tanpa berdiri lama di loket. Nomor dihitung dengan teori antrian dari data pelayanan 14 hari terakhir, bukan sekadar tebakan.</p>

                <div class="hm-feats">
                    <div class="hm-feat"><span class="hm-feat-ico"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span><div><h3>1. Ambil nomor</h3><p>Pilih poli, isi NIK dan nama. Tiket langsung keluar.</p></div></div>
                    <div class="hm-feat"><span class="hm-feat-ico"><i class="bi bi-phone" aria-hidden="true"></i></span><div><h3>2. Pantau giliran</h3><p>Tiket di HP ikut berubah sendiri sesuai antrian.</p></div></div>
                    <div class="hm-feat"><span class="hm-feat-ico"><i class="bi bi-whatsapp" aria-hidden="true"></i></span><div><h3>3. Dikabari WhatsApp</h3><p>Pesan masuk begitu nomor Anda dipanggil.</p></div></div>
                    <div class="hm-feat"><span class="hm-feat-ico"><i class="bi bi-door-open" aria-hidden="true"></i></span><div><h3>4. Datang saat dipanggil</h3><p>Nomor muncul di layar ruang tunggu dan di tiket Anda.</p></div></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ ANTRIAN TIAP POLI ============ --}}
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

    {{-- ============ DOKTER PRAKTIK HARI INI ============ --}}
    <section id="dokter" class="hm-section">
        <div class="container-xxl">
            <div class="hm-head">
                <div>
                    <span class="hm-eyebrow hm-eyebrow-soft">Jadwal</span>
                    <h2 class="hm-h2">Dokter praktik hari ini</h2>
                    <p class="hm-sub">Dokter yang punya jadwal hari ini di setiap poli.</p>
                </div>
                <div class="hm-arrows" aria-hidden="true">
                    <button type="button" class="hm-round" data-dr-prev tabindex="-1"><i class="bi bi-chevron-left"></i></button>
                    <button type="button" class="hm-round" data-dr-next tabindex="-1"><i class="bi bi-chevron-right"></i></button>
                </div>
            </div>

            @if ($dokterHariIni)
                <div class="hm-dr-track" data-dr-track tabindex="0" aria-label="Daftar dokter praktik hari ini">
                    @foreach ($dokterHariIni as $d)
                        @php [$kelas, $teks] = $praktikLabel[$d['praktik']] ?? $praktikLabel['libur']; @endphp
                        <article class="hm-dr">
                            <span class="hm-avatar" style="--n: {{ $loop->index % 5 }}" aria-hidden="true">{{ $d['inisial'] }}</span>
                            <h3>{{ $d['nama'] }}</h3>
                            <p class="hm-dr-poli">{{ $d['poli'] }}</p>
                            @if ($d['jam'])<p class="hm-dr-jam"><i class="bi bi-clock" aria-hidden="true"></i>{{ $d['jam'] }}</p>@endif
                            <span class="hm-badge hm-badge-{{ $kelas }}">{{ $teks }}</span>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="hm-empty">Belum ada dokter dengan jadwal praktik hari ini.</p>
            @endif
        </div>
    </section>

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

    {{-- ============ PANDUAN PASIEN ============ --}}
    <section class="hm-section hm-soft">
        <div class="container-xxl">
            <div class="hm-head">
                <div>
                    <span class="hm-eyebrow hm-eyebrow-soft">Panduan</span>
                    <h2 class="hm-h2">Panduan untuk pasien</h2>
                </div>
            </div>

            <div class="hm-guides">
                <a class="hm-guide" href="{{ route('antrian.create') }}">
                    <span class="hm-guide-top hm-g1"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span>
                    <span class="hm-guide-body"><b>Ambil nomor antrian</b><span>Pilih poli, isi NIK dan nama, tiket langsung jadi.</span><em>Mulai sekarang <i class="bi bi-arrow-right"></i></em></span>
                </a>
                <a class="hm-guide" href="{{ route('antrian.layar') }}">
                    <span class="hm-guide-top hm-g2"><i class="bi bi-tv" aria-hidden="true"></i></span>
                    <span class="hm-guide-body"><b>Membaca papan antrian</b><span>Nomor yang tampil besar adalah nomor yang sedang dipanggil.</span><em>Lihat papan <i class="bi bi-arrow-right"></i></em></span>
                </a>
                <a class="hm-guide" href="#antrian">
                    <span class="hm-guide-top hm-g3"><i class="bi bi-list-ol" aria-hidden="true"></i></span>
                    <span class="hm-guide-body"><b>Pantau antrian poli</b><span>Lihat nomor dan jumlah yang menunggu tanpa datang ke klinik.</span><em>Lihat antrian <i class="bi bi-arrow-right"></i></em></span>
                </a>
                <a class="hm-guide" href="#cara-kerja">
                    <span class="hm-guide-top hm-g4"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                    <span class="hm-guide-body"><b>Perkiraan waktu tunggu</b><span>Dihitung dari data pelayanan 14 hari terakhir dan muncul di tiket.</span><em>Cara kerjanya <i class="bi bi-arrow-right"></i></em></span>
                </a>
            </div>
        </div>
    </section>

    {{-- ============ AJAKAN ============ --}}
    <section class="hm-cta-band" id="kontak">
        <div class="container-xxl">
            <div class="hm-cta">
                <div class="hm-cta-copy">
                    <h2>Butuh bantuan mengambil nomor?</h2>
                    <p>Cukup NIK dan nama, tanpa buat akun. Petugas kami siap membantu bila Anda kesulitan.</p>
                    <div class="hm-cta-tags">
                        <span><i class="bi bi-shield-check" aria-hidden="true"></i>Data aman</span>
                        <span><i class="bi bi-people" aria-hidden="true"></i>Petugas ramah</span>
                        <span><i class="bi bi-lightning-charge" aria-hidden="true"></i>Layanan cepat</span>
                    </div>
                </div>
                <div class="{{ ($telepon || $whatsapp) ? 'hm-call' : 'hm-call-solo' }}">
                    @if ($telepon || $whatsapp)
                        <div>
                            <small>{{ $telepon ? 'Hubungi kami' : 'WhatsApp kami' }}</small>
                            @if ($telepon)
                                <a class="hm-tel" href="tel:{{ preg_replace('/[^\d+]/', '', $telepon) }}"><i class="bi bi-telephone-fill" aria-hidden="true"></i>{{ $telepon }}</a>
                            @else
                                <span class="hm-tel"><i class="bi bi-whatsapp" aria-hidden="true"></i>{{ $whatsapp }}</span>
                            @endif
                        </div>
                    @endif
                    <a href="{{ route('antrian.create') }}" class="btn btn-lg hm-btn hm-btn-primary">Ambil nomor sekarang<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i></a>
                </div>
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
