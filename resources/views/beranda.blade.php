<x-public-layout title="Beranda" :wide="true">

    @include('partials.flash')

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

    {{-- Pembuka --}}
    <section class="hero-panel mb-5">
    <div class="row align-items-center g-5">
        <div class="col-lg-7">
            @if (\App\Services\JamOperasional::aktif())
                @if (\App\Services\JamOperasional::pendaftaranDibuka())
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis mb-3"><i class="bi bi-circle-fill me-1" style="font-size: .5rem"></i> Pendaftaran dibuka sampai pukul {{ \App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup')) }}</span>
                @else
                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis mb-3"><i class="bi bi-circle-fill me-1" style="font-size: .5rem"></i> Pendaftaran ditutup</span>
                @endif
            @endif

            <h1 class="hero-title display-4 fw-bold mb-4">Ambil nomor antrian, <span class="text-primary">tahu kapan giliran Anda.</span></h1>
            <p class="lead text-body-secondary mb-4" style="max-width: 34rem">Pilih poli dan isi data singkat. Perkiraan waktu tunggu langsung muncul, dan layar di ruang tunggu memperbarui nomor setiap 2 detik.</p>

            <div class="d-flex flex-wrap gap-2 mb-4">
                <a href="{{ route('antrian.create') }}" class="btn btn-primary btn-lg"><i class="bi bi-ticket-perforated me-2"></i>Ambil nomor antrian</a>
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-lg">Buka dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg">Masuk sebagai staf</a>
                @endauth
            </div>

            <ul class="hero-facts list-unstyled mb-0">
                <li><i class="bi bi-person-check"></i>Tanpa login</li>
                <li><i class="bi bi-hourglass-split"></i>Estimasi tunggu otomatis</li>
                <li><i class="bi bi-display"></i>Layar ruang tunggu langsung</li>
            </ul>
        </div>

        {{-- Contoh tiket, hanya ilustrasi --}}
        <div class="col-lg-5" aria-hidden="true">
            <div class="card hero-ticket overflow-hidden mx-auto" style="max-width: 21rem">
                <div class="board board-pattern text-center px-4 pt-4 pb-5 rounded-0">
                    <div class="small board-muted">Poli Umum</div>
                    <div class="numeral mt-2" style="font-size: 6.5rem">A017</div>
                    <div class="small board-muted mt-3">Contoh tiket</div>
                </div>
                <div class="ticket-cut"><span class="hole hole-l"></span><span class="hole hole-r"></span></div>
                <div class="card-body d-flex justify-content-between align-items-baseline">
                    <span class="text-body-secondary">Perkiraan tunggu</span>
                    <span class="fs-4 fw-bold display-font">&plusmn; 12 menit</span>
                </div>
            </div>
        </div>
    </div>
    </section>

    {{-- Masuk cepat, hanya tampil di mode lokal --}}
    @if (app()->isLocal())
        <section class="card border-primary-subtle mb-5">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div style="max-width: 36rem">
                    <h2 class="card-title-sm"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Masuk cepat untuk pengujian</h2>
                    <p class="small text-body-secondary mb-0 mt-1">Tanpa perlu mengingat email dan password. Tombol ini hanya muncul saat APP_ENV=local dan hilang sendiri di server produksi.</p>
                    @auth
                        <p class="small mb-0 mt-2">Sekarang masuk sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }}).</p>
                    @endauth
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @foreach (['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'] as $peran => $nama)
                        <form method="POST" action="{{ route('demo.masuk', $peran) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">Masuk sebagai {{ $nama }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Antrian hari ini --}}
    <section id="antrian" class="mb-5" style="scroll-margin-top: 5rem">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
            <h2 class="h3 mb-0">Antrian hari ini</h2>
            <a href="{{ route('antrian.layar') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-tv me-1"></i>Buka papan semua poli</a>
        </div>

        <div class="card">
            <ul class="list-group list-group-flush">
                @forelse ($polis as $poli)
                    <li class="list-group-item d-flex flex-wrap align-items-center gap-3 p-3 p-md-4">
                        <span class="kode">{{ $poli->kode_poli }}</span>
                        <div class="flex-grow-1 fw-semibold">{{ $poli->nama_poli }}</div>
                        <div class="d-flex align-items-baseline gap-2">
                            <span class="numeral fs-2 text-navy">{{ $poli->menunggu_count }}</span>
                            <span class="small text-body-secondary">menunggu</span>
                        </div>
                        <a href="{{ route('antrian.display', $poli) }}" class="btn btn-outline-secondary btn-sm">Lihat layar</a>
                    </li>
                @empty
                    <li class="list-group-item p-4 text-body-secondary">Belum ada poli aktif. Jalankan <code>php artisan db:seed</code> atau tambahkan poli lewat dashboard admin.</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- Menu staf: hanya tampil setelah login --}}
    @if ($menuStaf)
        <section class="mb-5">
            <h2 class="h3 mb-3">Menu Anda</h2>
            <div class="row g-3">
                @foreach ($menuStaf as $menu)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ $menu['url'] }}" class="card h-100 text-decoration-none">
                            <div class="card-body d-flex gap-3">
                                <i class="bi bi-{{ $menu['ikon'] }} fs-3 text-primary"></i>
                                <div>
                                    <div class="fw-semibold">{{ $menu['nama'] }}</div>
                                    <div class="small text-body-secondary">{{ $menu['keterangan'] }}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Cara kerja untuk pasien --}}
    <section class="mb-4">
        <h2 class="h3 mb-3">Cara kerjanya</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <span class="numeral fs-2 text-navy">1</span>
                    <h3 class="h6 mt-2">Ambil nomor</h3>
                    <p class="small text-body-secondary mb-0">Pilih poli dan isi NIK serta nama. Tiket langsung tampil dengan perkiraan waktu tunggu.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <span class="numeral fs-2 text-navy">2</span>
                    <h3 class="h6 mt-2">Pantau giliran</h3>
                    <p class="small text-body-secondary mb-0">Tiket memperbarui diri otomatis. Anda juga bisa melihat layar antrian poli kapan saja.</p>
                </div></div>
            </div>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <span class="numeral fs-2 text-navy">3</span>
                    <h3 class="h6 mt-2">Datang saat dipanggil</h3>
                    <p class="small text-body-secondary mb-0">Nomor Anda muncul di layar ruang tunggu dan tiket berubah menjadi "Giliran Anda!".</p>
                </div></div>
            </div>
        </div>
    </section>
</x-public-layout>
