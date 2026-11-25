{{-- Akses cepat untuk pengembangan: melayang di pojok, hanya saat APP_ENV=local.
         Tombol x menyembunyikannya sampai halaman dimuat ulang (sengaja tanpa penyimpanan, cocok untuk screenshot). --}}
    @if (\App\Support\AksesLokal::diizinkan())
        <div class="bn-dev {{ ($kanan ?? false) ? 'bn-dev-kanan' : '' }}" data-dev>
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
                    <div class="bn-dev-head bn-dev-head-2"><span class="bn-dev-title">Uji coba</span>
                        <span class="bn-dev-now">Jam: <strong>{{ \App\Services\JamOperasional::modeUji() ? 'diabaikan' : 'berlaku' }}</strong></span></div>
                    <form method="POST" action="{{ route('demo.mode-uji') }}">
                        @csrf
                        <button type="submit" @class(['is-aktif' => \App\Services\JamOperasional::modeUji()])>
                            <span class="bn-dev-ico"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
                            <span class="bn-dev-txt"><b>{{ \App\Services\JamOperasional::modeUji() ? 'Matikan mode uji' : 'Nyalakan mode uji' }}</b><small>Daftar dan panggil kapan saja, abaikan jam</small></span>
                            <i class="bi bi-arrow-right-short bn-dev-go" aria-hidden="true"></i>
                        </button>
                    </form>
                    <a class="bn-dev-link" href="{{ route('antrian.create') }}">
                        <span class="bn-dev-ico"><i class="bi bi-person-plus" aria-hidden="true"></i></span>
                        <span class="bn-dev-txt"><b>Daftar sebagai pasien</b><small>Buka form ambil nomor antrian</small></span>
                        <i class="bi bi-arrow-right-short bn-dev-go" aria-hidden="true"></i>
                    </a>
                    <form method="POST" action="{{ route('demo.pasien-uji') }}">
                        @csrf
                        <button type="submit">
                            <span class="bn-dev-ico"><i class="bi bi-people" aria-hidden="true"></i></span>
                            <span class="bn-dev-txt"><b>Tambah pasien uji</b><small>Satu pasien fiktif di poli acak</small></span>
                            <i class="bi bi-arrow-right-short bn-dev-go" aria-hidden="true"></i>
                        </button>
                    </form>
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
