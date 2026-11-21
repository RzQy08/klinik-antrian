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
                    @php $lay = \App\Services\RingkasanAntrian::layanan(); @endphp
                    <span class="hm-pill {{ $lay['aktif'] && ! $lay['buka'] ? 'hm-pill-off' : '' }}" data-live-pill><span class="hm-dot"></span><span data-live-pill-teks>{{ $lay['pill'] }}</span></span>

                    <h1 class="hm-title">Ambil nomor dari rumah, <span class="hm-accent">datang saat giliran Anda dekat.</span></h1>
                    <p class="hm-lead">Pilih poli dan daftar lewat HP. Pantau nomor yang sedang dipanggil, lalu berangkat sekitar 30 menit sebelum giliran Anda.</p>

                    <div class="hm-cta-row">
                        <a href="{{ route('antrian.create') }}" class="btn btn-lg hm-btn hm-btn-primary"><i class="bi bi-ticket-perforated" aria-hidden="true"></i>Ambil nomor</a>
                        <a href="{{ route('antrian.layar') }}" class="btn btn-lg hm-btn hm-btn-ghost"><i class="bi bi-tv" aria-hidden="true"></i>Lihat papan</a>
                    </div>

                    <ul class="hm-facts list-unstyled mb-0">
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Tanpa akun</li>
                        <li><i class="bi bi-hourglass-split" aria-hidden="true"></i>Perkiraan waktu tunggu</li>
                        <li><i class="bi bi-whatsapp" aria-hidden="true"></i>Diingatkan lewat WhatsApp</li>
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
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><span><small>{{ $label }}</small><b @if ($label === 'Jam layanan' && ! config('rumahsakit.jam_layanan')) data-live-jamlayanan @endif>{{ $isi }}</b></span>
                            </a>
                        @else
                            <div class="hm-info-item">
                                <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><span><small>{{ $label }}</small><b @if ($label === 'Jam layanan' && ! config('rumahsakit.jam_layanan')) data-live-jamlayanan @endif>{{ $isi }}</b></span>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
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
                    <h2 class="hm-h2">Datang saat giliran Anda sudah dekat</h2>
                    <p class="hm-sub">Daftar dari HP, pantau giliran, lalu berangkat. Perkiraan waktu tunggu dihitung dari data pelayanan 14 hari terakhir.</p>
                </div>
            </div>

            <ol class="hm-lang">
                <li class="hm-lg" data-w="0">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-ticket-perforated" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="{{ route('antrian.create') }}">
                        <span class="hm-lg-no" aria-hidden="true">01</span>
                        <h3>Daftar dari rumah</h3>
                        <p>Pilih poli, isi NIK dan nama. Nomor antrian langsung terbit.</p>
                        <em>Daftar sekarang <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="1">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-phone" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="#antrian">
                        <span class="hm-lg-no" aria-hidden="true">02</span>
                        <h3>Pantau giliran</h3>
                        <p>Lihat posisi antrian dan perkiraan waktu tunggu.</p>
                        <em>Lihat antrian <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="2">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-hospital" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="{{ config('rumahsakit.maps') ?: '#kontak' }}" @if (config('rumahsakit.maps')) target="_blank" rel="noopener" @endif>
                        <span class="hm-lg-no" aria-hidden="true">03</span>
                        <h3>Tiba saat giliran dekat</h3>
                        <p>Tiba paling lambat sekitar 30 menit sebelum giliran Anda.</p>
                        <em>{{ config('rumahsakit.maps') ? 'Petunjuk arah' : 'Hubungi kami' }} <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
                <li class="hm-lg" data-w="3">
                    <span class="hm-lg-rail"><span class="hm-lg-ico"><i class="bi bi-tv" aria-hidden="true"></i></span></span>
                    <a class="hm-lg-card" href="{{ route('antrian.layar') }}">
                        <span class="hm-lg-no" aria-hidden="true">04</span>
                        <h3>Masuk saat dipanggil</h3>
                        <p>Masuk ke poli saat nomor Anda dipanggil.</p>
                        <em>Lihat papan <i class="bi bi-arrow-right" aria-hidden="true"></i></em>
                    </a>
                </li>
            </ol>

        </div>
    </section>

    {{-- ============ ANTRIAN TIAP POLI ============ --}}
    <section id="antrian" class="bn-band bn-band-stripes hm-fit">
        <div class="container-xxl">
            <div class="bn-head">
                <div>
                    <span class="bn-eyebrow"><span class="bn-live-dot bn-live-dot-dark"></span>Hari ini</span>
                    <h2 class="bn-h2">Antrian di tiap poli</h2>
                    <p class="bn-sub">Diperbarui otomatis tanpa memuat ulang halaman.</p>
                </div>
                <a href="{{ route('antrian.layar') }}" class="btn btn-primary"><i class="bi bi-tv me-2"></i>Papan antrian</a>
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
    {{-- Isi awal dari server; setelah itu diperbarui diam-diam oleh resources/js/beranda-antrian.js --}}
    <section id="dokter" class="hm-section hm-fit hm-dokter" data-dokter-live>
        <div class="container-xxl">
            <div class="hm-head">
                <div>
                    <h2 class="hm-h2">Dokter praktik hari ini</h2>
                    <p class="hm-sub" data-live-tanggal>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
                </div>
                <p class="hm-dk-sum" data-dk-sum @if (! $dokterHariIni) hidden @endif><span><b class="numeral" data-dk-total>{{ count($dokterHariIni) }}</b>dokter</span><span class="is-ok"><b class="numeral" data-dk-aktif>{{ $sedangPraktik }}</b>sedang praktik</span></p>
            </div>

            <div class="hm-dk" data-dk @if (! $dokterHariIni) hidden @endif>
                <div class="hm-dk-tabs" role="group" aria-label="Saring menurut poli" data-dk-tabs @if ($perPoli->count() < 2) hidden @endif>
                    <button type="button" class="hm-dk-tab" data-dk-tab="*" aria-pressed="true">Semua<b>{{ count($dokterHariIni) }}</b></button>
                    @foreach ($perPoli as $namaPoli => $daftar)
                        <button type="button" class="hm-dk-tab" data-w="{{ ($indeksPoli->get($namaPoli, 0)) % 6 }}" data-dk-tab="{{ \Illuminate\Support\Str::slug($namaPoli) }}" aria-pressed="false"><i aria-hidden="true"></i>{{ $namaPoli }}<b>{{ $daftar->count() }}</b></button>
                    @endforeach
                </div>

                <div class="hm-dk-grid" data-dk-grid>
                    @foreach ($dokterHariIni as $d)
                        @php [$kelas, $teks] = $praktikLabel[$d['praktik']] ?? $praktikLabel['libur']; @endphp
                        <article class="hm-dkc is-{{ $kelas }}" data-dk-kartu data-id="{{ $d['id'] }}" data-poli="{{ \Illuminate\Support\Str::slug($d['poli']) }}" data-w="{{ ($indeksPoli->get($d['poli'], 0)) % 6 }}">
                            <span class="hm-dkc-av" aria-hidden="true">{{ $d['inisial'] }}</span>
                            <div class="hm-dkc-info">
                                <h3>{{ $d['nama'] }}</h3>
                                <p>{{ $d['poli'] }}</p>
                            </div>
                            <span class="hm-badge hm-badge-{{ $kelas }}" data-k="badge" @if ($d['praktik'] === 'berlangsung') hidden @endif>{{ $teks }}</span>
                            <p class="hm-dkc-jam" data-k="jam" @if (! $d['jam']) hidden @endif><i class="bi bi-clock" aria-hidden="true"></i><span>{{ $d['jam'] }}</span></p>
                        </article>
                    @endforeach
                </div>
            </div>
            <p class="hm-empty" data-dk-kosong @if ($dokterHariIni) hidden @endif>Belum ada dokter dengan jadwal praktik hari ini.</p>
        </div>
    </section>

    <div class="hm-closing">
    {{-- ============ AJAKAN ============ --}}
    <section class="hm-cta-band" id="kontak">
        <div class="container-xxl">
            <div class="hm-cta">
                <i class="bi bi-headset hm-cta-bg" aria-hidden="true"></i>
                <div class="hm-cta-copy">
                    <span class="hm-cta-tag"><i class="bi bi-life-preserver" aria-hidden="true"></i>Butuh bantuan?</span>
                    <h2>Bingung cara mengambil nomor?</h2>
                    <p>Petugas kami siap membantu memilih poli dan mendaftarkan Anda sampai nomor antrian terbit.</p>
                    <a href="{{ route('antrian.create') }}" class="hm-cta-btn">Coba ambil nomor sendiri<i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </div>
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
                    @php
                        $maps = config('rumahsakit.maps');
                        $lokasi = '<span><small>Datang langsung</small><b>'.e($alamat ?: 'Loket informasi rumah sakit').'</b>'.($jamLayanan ? '<em'.(config('rumahsakit.jam_layanan') ? '' : ' data-live-jamlayanan').'>'.e($jamLayanan).'</em>' : '').'</span>';
                    @endphp
                    @if ($maps)
                        <a class="hm-help-row" href="{{ $maps }}" target="_blank" rel="noopener">
                            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>{!! $lokasi !!}<i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                        </a>
                    @else
                        <div class="hm-help-row">
                            <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>{!! $lokasi !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    </div>
</x-public-layout>
