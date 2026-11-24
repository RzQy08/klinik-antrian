@php
    $warna = 'pt-c'.(ord(strtoupper(substr((string) $poli->kode_poli, 0, 1))) % 6);
    $berikutnya = $daftarMenunggu->first();
    $setelahnya = $daftarMenunggu->slice(1, 3)->pluck('nomor_antrian');
@endphp
<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('antrian.index') }}" class="pt-back"><i class="bi bi-arrow-left" aria-hidden="true"></i>Semua poli</a>
        <div class="pt-kelola-head {{ $warna }}">
            <span class="pt-kode pt-kode-lg" aria-hidden="true">{{ $poli->kode_poli }}</span>
            <div>
                <h1 class="pt-title mb-1">{{ $poli->nama_poli }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="pt-page {{ $warna }}">
        @include('partials.flash')

        @unless ($layananBerjalan)
            <div class="alert alert-danger d-flex gap-2" role="alert"><i class="bi bi-clock-history mt-1" aria-hidden="true"></i><div>{{ \App\Services\JamOperasional::pesanLayananSelesai() }}</div></div>
        @endunless

        <section aria-label="Ringkasan poli" class="mb-4">
            <div class="pt-stats">
                <div class="pt-stat pt-c0"><span class="pt-stat-ico"><i class="bi bi-people"></i></span><div><div class="pt-stat-n">{{ $daftarMenunggu->count() }}</div><div class="pt-stat-l">Menunggu</div></div></div>
                <div class="pt-stat pt-c1"><span class="pt-stat-ico"><i class="bi bi-megaphone"></i></span><div><div class="pt-stat-n">{{ $sedangDilayani->count() }}</div><div class="pt-stat-l">Sedang ditangani</div></div></div>
                <div class="pt-stat pt-c3"><span class="pt-stat-ico"><i class="bi bi-alarm"></i></span><div><div class="pt-stat-n">{{ $telat->count() }}</div><div class="pt-stat-l">Telat</div></div></div>
                <div class="pt-stat pt-c2"><span class="pt-stat-ico"><i class="bi bi-person-x"></i></span><div><div class="pt-stat-n">{{ $tidakHadir->count() }}</div><div class="pt-stat-l">Tidak hadir</div></div></div>
                <div class="pt-stat pt-c5"><span class="pt-stat-ico"><i class="bi bi-person-badge"></i></span><div><div class="pt-stat-n">{{ $dokters->count() }}</div><div class="pt-stat-l">Dokter bertugas</div></div></div>
            </div>
        </section>

        {{-- Navigasi lompat untuk layar kecil --}}
        <nav class="pt-jump d-lg-none" aria-label="Loncat ke bagian">
            <a href="#bagian-ditangani">Ditangani <span class="n">{{ $sedangDilayani->count() }}</span></a>
            <a href="#bagian-menunggu">Menunggu <span class="n">{{ $daftarMenunggu->count() }}</span></a>
            <a href="#bagian-telat">Telat <span class="n">{{ $telat->count() }}</span></a>
            <a href="#bagian-tidak-hadir">Tidak hadir <span class="n">{{ $tidakHadir->count() }}</span></a>
        </nav>

        <div class="row g-4">

            {{-- Konsol panggil --}}
            <div class="col-lg-4 order-first">
                <div class="pt-console-wrap">
                    <section class="pt-console" aria-labelledby="judul-panggil">
                        <h2 class="pt-console-label" id="judul-panggil">
                            <i class="bi bi-megaphone-fill" aria-hidden="true"></i>Nomor berikutnya
                            @if (config('antrian.panggil_otomatis'))<span class="pt-lim"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Otomatis</span>@endif
                            @if ($berikutnya && $berikutnya->limpahan)
                                <span class="pt-lim">Limpahan {{ $berikutnya->nomor_lama }}</span>
                            @endif
                        </h2>

                        @if (! $berikutnya)
                            <p class="pt-console-empty"><i class="bi bi-emoji-smile" aria-hidden="true"></i><span>Belum ada pasien yang menunggu.</span></p>
                        @elseif ($dokters->isEmpty())
                            <div class="pt-console-no" aria-label="Nomor {{ $berikutnya->nomor_antrian }}">{{ $berikutnya->nomor_antrian }}</div>
                            <p class="pt-console-empty"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><span>Belum ada dokter aktif di poli ini. Minta admin mengaktifkan dokter lewat menu Dokter.</span></p>
                        @else
                            <div class="pt-console-no">{{ $berikutnya->nomor_antrian }}</div>
                            <div class="pt-console-name">{{ $berikutnya->pasien->nama }}</div>
                            <div class="pt-console-meta">
                                @if ($berikutnya->limpahan)
                                    Dari {{ $berikutnya->tanggal_asal->locale('id')->translatedFormat('j M') }}
                                @else
                                    <span class="pt-since" data-since="{{ $berikutnya->waktu_daftar->toIso8601String() }}" data-awalan="Menunggu " data-akhiran="">Menunggu {{ $berikutnya->waktu_daftar->locale('id')->diffForHumans(null, true) }}</span>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('antrian.panggil', $poli) }}" class="d-grid gap-3 mt-4 pt-guard">
                                @csrf
                                <div>
                                    <label for="dokter_id" class="form-label">Dokter yang bertugas</label>
                                    <select name="dokter_id" id="dokter_id" required class="form-select" data-poli="{{ $poli->id }}">
                                        @foreach ($dokters as $dokter)
                                            <option value="{{ $dokter->id }}">{{ $dokter->nama }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-warning pt-call" @disabled(! $layananBerjalan)>
                                    <i class="bi bi-megaphone-fill me-2" aria-hidden="true"></i>Panggil {{ $berikutnya->nomor_antrian }}
                                </button>
                            </form>

                            @if ($setelahnya->isNotEmpty())
                                <div class="pt-after">Setelah ini: <b>{{ $setelahnya->implode(', ') }}</b>@if ($daftarMenunggu->count() > 4) dan {{ $daftarMenunggu->count() - 4 }} lagi @endif</div>
                            @endif
                        @endif
                    </section>
                </div>
            </div>

            {{-- Daftar --}}
            <div class="col-lg-8 d-grid gap-4 align-content-start">

                <section class="pt-sec" id="bagian-ditangani" aria-labelledby="h-ditangani">
                    <div class="pt-sec-head"><h2 id="h-ditangani">Sedang ditangani</h2><span class="pt-sec-n">{{ $sedangDilayani->count() }}</span></div>
                    @if ($sedangDilayani->isEmpty())
                        <div class="pt-empty"><i class="bi bi-person-check" aria-hidden="true"></i><div>Belum ada pasien yang dipanggil.</div></div>
                    @else
                        <ul class="pt-list">
                            @foreach ($sedangDilayani as $antrian)
                                <li class="pt-case is-{{ $antrian->status }}">
                                    <div class="pt-case-main">
                                        <span class="pt-no">{{ $antrian->nomor_antrian }}</span>
                                        <div class="pt-case-body">
                                            <div class="pt-case-name">{{ $antrian->pasien->nama }}</div>
                                            <div class="pt-case-meta">
                                                <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $antrian->dokter?->nama ?? '-' }}</span>
                                                @if ($antrian->jumlah_panggil > 1)<span><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Panggilan ke-{{ $antrian->jumlah_panggil }}</span>@endif
                                                @if ($antrian->status === 'dipanggil' && $antrian->waktu_dipanggil)
                                                    <span class="pt-since" data-since="{{ $antrian->waktu_dipanggil->toIso8601String() }}" data-awalan="Dipanggil " data-akhiran=" lalu"><i class="bi bi-clock" aria-hidden="true"></i>Dipanggil {{ $antrian->waktu_dipanggil->format('H.i') }}</span>
                                                    <span class="pt-sisa" data-sampai="{{ $antrian->waktu_dipanggil->copy()->addMinutes((int) config('antrian.batas_telat_menit'))->toIso8601String() }}" data-awalan="Telat dalam "><i class="bi bi-alarm" aria-hidden="true"></i>Telat dalam {{ max(0, (int) config('antrian.batas_telat_menit') - (int) $antrian->waktu_dipanggil->diffInMinutes(now())) }} mnt</span>
                                                @elseif ($antrian->waktu_mulai_dilayani)
                                                    <span class="pt-since" data-since="{{ $antrian->waktu_mulai_dilayani->toIso8601String() }}" data-awalan="Dilayani " data-akhiran=""><i class="bi bi-clock" aria-hidden="true"></i>Mulai {{ $antrian->waktu_mulai_dilayani->format('H.i') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @include('partials.status', ['status' => $antrian->status])
                                    </div>

                                    <div class="pt-acts">
                                        @if ($antrian->status === 'dipanggil')
                                            <form method="POST" action="{{ route('antrian.mulai-layani', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-primary"><i class="bi bi-play-fill me-1" aria-hidden="true"></i>Mulai layani<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                            <form method="POST" action="{{ route('antrian.panggil-ulang', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-outline-secondary" @disabled(! $layananBerjalan)><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Panggil ulang<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                            <form method="POST" action="{{ route('antrian.tidak-hadir', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-outline-danger"><i class="bi bi-person-x me-1" aria-hidden="true"></i>Tidak hadir<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                        @elseif ($antrian->status === 'dilayani')
                                            <form method="POST" action="{{ route('antrian.selesai', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>Selesai<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="pt-sec" id="bagian-menunggu" aria-labelledby="h-menunggu">
                    <div class="pt-sec-head"><h2 id="h-menunggu">Menunggu</h2><span class="pt-sec-n">{{ $daftarMenunggu->count() }}</span></div>
                    @if ($daftarMenunggu->count() > 4)
                        <div class="pt-cari"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="cariPasien" class="form-control" placeholder="Cari nomor atau nama pasien" autocomplete="off" aria-label="Cari pasien yang menunggu"></div>
                        <p class="pt-cari-kosong" id="cariKosong" hidden>Tidak ada pasien yang cocok.</p>
                    @endif
                    @if ($daftarMenunggu->isEmpty())
                        <div class="pt-empty"><i class="bi bi-inbox" aria-hidden="true"></i><div>Tidak ada pasien yang menunggu.</div></div>
                    @else
                        <ol class="pt-list" id="daftarMenunggu">
                            @foreach ($daftarMenunggu as $antrian)
                                <li class="pt-case {{ $loop->first ? 'is-next' : '' }}">
                                    <div class="pt-case-main">
                                        <span class="pt-pos" aria-label="Urutan {{ $loop->iteration }}">{{ $loop->iteration }}</span>
                                        <span class="pt-no">{{ $antrian->nomor_antrian }}</span>
                                        <div class="pt-case-body">
                                            <div class="pt-case-name">{{ $antrian->pasien->nama }}</div>
                                            <div class="pt-case-meta">
                                                @if ($antrian->limpahan)
                                                    <span><span class="pt-lim">Limpahan {{ $antrian->nomor_lama }}</span> dari {{ $antrian->tanggal_asal->locale('id')->translatedFormat('j M') }}</span>
                                                @else
                                                    <span class="pt-since" data-since="{{ $antrian->waktu_daftar->toIso8601String() }}" data-awalan="Menunggu " data-akhiran=""><i class="bi bi-hourglass-split" aria-hidden="true"></i>Menunggu {{ $antrian->waktu_daftar->locale('id')->diffForHumans(null, true) }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if ($loop->first)<span class="pt-next-tag">Berikutnya</span>@endif
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                <section class="pt-sec" id="bagian-telat" aria-labelledby="h-telat">
                    <div class="pt-sec-head"><h2 id="h-telat">Telat sementara</h2><span class="pt-sec-n">{{ $telat->count() }}</span></div>
                    @if ($telat->isEmpty())
                        <div class="pt-empty"><i class="bi bi-alarm" aria-hidden="true"></i><div>Tidak ada pasien yang telat.</div></div>
                    @else
                        <ul class="pt-list">
                            @foreach ($telat as $antrian)
                                <li class="pt-case is-telat">
                                    <div class="pt-case-main">
                                        <span class="pt-no">{{ $antrian->nomor_antrian }}</span>
                                        <div class="pt-case-body">
                                            <div class="pt-case-name">{{ $antrian->pasien->nama }}</div>
                                            <div class="pt-case-meta">
                                                <span><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $antrian->dokter?->nama ?? '-' }}</span>
                                                <span>Telat sejak {{ $antrian->waktu_telat?->format('H.i') }}</span>
                                                @if ($antrian->waktu_telat)
                                                    <span class="pt-sisa" data-sampai="{{ $antrian->waktu_telat->copy()->addMinutes((int) config('antrian.batas_tidak_hadir_menit'))->toIso8601String() }}" data-awalan="Tidak hadir dalam "><i class="bi bi-hourglass-split" aria-hidden="true"></i>Tidak hadir dalam {{ max(0, (int) config('antrian.batas_tidak_hadir_menit') - (int) $antrian->waktu_telat->diffInMinutes(now())) }} mnt</span>
                                                @endif
                                            </div>
                                        </div>
                                        @include('partials.status', ['status' => $antrian->status])
                                    </div>
                                    <div class="pt-acts">
                                        <form method="POST" action="{{ route('antrian.panggil-ulang', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-primary" @disabled(! $layananBerjalan)><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Panggil ulang<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                        <form method="POST" action="{{ route('antrian.tidak-hadir', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-outline-danger"><i class="bi bi-person-x me-1" aria-hidden="true"></i>Tidak hadir<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>

                <section class="pt-sec" id="bagian-tidak-hadir" aria-labelledby="h-tidak-hadir">
                    <div class="pt-sec-head"><h2 id="h-tidak-hadir">Tidak hadir</h2><span class="pt-sec-n">{{ $tidakHadir->count() }}</span></div>
                    @if ($tidakHadir->isEmpty())
                        <div class="pt-empty"><i class="bi bi-people" aria-hidden="true"></i><div>Tidak ada pasien yang tidak hadir.</div></div>
                    @else
                        <ul class="pt-list">
                            @foreach ($tidakHadir as $antrian)
                                <li class="pt-case">
                                    <div class="pt-case-main">
                                        <span class="pt-no">{{ $antrian->nomor_antrian }}</span>
                                        <div class="pt-case-body">
                                            <div class="pt-case-name">{{ $antrian->pasien->nama }}</div>
                                            <div class="pt-case-meta"><span>Dipanggil {{ $antrian->jumlah_panggil }} kali, terakhir pukul {{ $antrian->waktu_dipanggil?->format('H.i') }}</span></div>
                                        </div>
                                        <form method="POST" action="{{ route('antrian.panggil-ulang', $antrian) }}" class="pt-guard">@csrf<button type="submit" class="btn btn-outline-primary" @disabled(! $layananBerjalan)><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>Panggil ulang<span class="visually-hidden"> {{ $antrian->nomor_antrian }}</span></button></form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var dasar = {{ \Illuminate\Support\Js::from(route('antrian.versi', $poli)) }};
        var versi = null, sibuk = false;

        function rel(menit) {
            if (menit < 1) return 'baru saja';
            if (menit < 60) return menit + ' mnt';
            return Math.floor(menit / 60) + ' jam ' + (menit % 60) + ' mnt';
        }
        function segar() {
            document.querySelectorAll('.pt-since[data-since]').forEach(function (el) {
                var t = new Date(el.dataset.since);
                if (isNaN(t)) return;
                var m = Math.max(0, Math.floor((Date.now() - t) / 60000));
                var ikon = el.querySelector('i');
                var akhiran = m < 1 ? '' : (el.dataset.akhiran || '');
                el.textContent = (el.dataset.awalan || '') + rel(m) + akhiran;
                if (ikon) el.insertBefore(ikon, el.firstChild);
            });
            document.querySelectorAll('.pt-sisa[data-sampai]').forEach(function (el) {
                var t = new Date(el.dataset.sampai);
                if (isNaN(t)) return;
                var m = Math.max(0, Math.ceil((t - Date.now()) / 60000));
                var ikon = el.querySelector('i');
                el.textContent = (el.dataset.awalan || '') + m + ' mnt';
                if (ikon) el.insertBefore(ikon, el.firstChild);
            });
        }

        function pasang() {
            // Dokter terakhir yang dipilih diingat per poli, jadi petugas tidak memilih ulang tiap panggilan
            var sel = document.getElementById('dokter_id');
            if (sel) {
                var kunci = 'pt.dokter.' + sel.dataset.poli;
                try {
                    var tersimpan = localStorage.getItem(kunci);
                    if (tersimpan && sel.querySelector('option[value="' + tersimpan + '"]')) sel.value = tersimpan;
                    sel.addEventListener('change', function () { localStorage.setItem(kunci, sel.value); });
                } catch (e) {}
            }

            // Pencarian cepat di daftar menunggu (disaring di halaman, tanpa memuat ulang)
            var cari = document.getElementById('cariPasien'), daftar = document.getElementById('daftarMenunggu');
            if (cari && daftar) {
                cari.addEventListener('input', function () {
                    var q = cari.value.trim().toLowerCase(), ada = 0;
                    daftar.querySelectorAll('li').forEach(function (li) {
                        var cocok = !q || li.textContent.toLowerCase().indexOf(q) !== -1;
                        li.hidden = !cocok;
                        if (cocok) ada++;
                    });
                    document.getElementById('cariKosong').hidden = ada > 0;
                });
            }

            // Cegah klik ganda pada tombol aksi
            document.querySelectorAll('form.pt-guard').forEach(function (f) {
                f.addEventListener('submit', function () {
                    sibuk = true;
                    setTimeout(function () {
                        f.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                        f.setAttribute('aria-busy', 'true');
                    }, 0);
                });
            });
            segar();
        }

        // Perubahan antrian (pasien baru, panggilan otomatis, telat) langsung tampil tanpa muat ulang penuh
        function periksa() {
            if (document.hidden || sibuk) return;
            fetch(dasar, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d) return;
                    if (versi === null) { versi = d.v; return; }
                    if (d.v === versi) return;
                    var cari = document.getElementById('cariPasien');
                    if (cari && document.activeElement === cari) return; // jangan ganggu yang sedang mengetik
                    return fetch(location.href, { credentials: 'same-origin' }).then(function (r) { return r.text(); }).then(function (html) {
                        var baru = new DOMParser().parseFromString(html, 'text/html').querySelector('.pt-page');
                        var lama = document.querySelector('.pt-page');
                        if (baru && lama) { lama.innerHTML = baru.innerHTML; versi = d.v; pasang(); }
                    });
                })
                .catch(function () {});
        }

        pasang();
        setInterval(segar, 30000);
        periksa();
        setInterval(periksa, 6000);
    })();
    </script>
</x-app-layout>
