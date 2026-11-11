@php
    $warna = fn ($kode) => 'pt-c'.(ord(strtoupper(substr((string) $kode, 0, 1))) % 6);
    $maks = max(1, (int) $polis->max('menunggu_count'));
    $totalMenunggu = (int) $polis->sum('menunggu_count');
    $palingRamai = $polis->sortByDesc('menunggu_count')->first();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="pt-head">
            <div>
                <h1 class="pt-title">Antrian hari ini</h1>
                <p class="pt-sub">
                    <span><i class="bi bi-calendar3"></i>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
                    <span><i class="bi bi-clock"></i><time class="pt-jam" id="ptJam">{{ now()->format('H:i:s') }}</time> WIB</span>
                </p>
            </div>

            <div class="pt-head-actions">
                @if (\App\Services\JamOperasional::aktif())
                    @if (\App\Services\JamOperasional::pendaftaranDibuka())
                        <span class="pt-pill ok"><i class="bi bi-door-open"></i>Pendaftaran dibuka</span>
                    @else
                        <span class="pt-pill off"><i class="bi bi-door-closed"></i>Pendaftaran ditutup</span>
                    @endif
                    @if (\App\Services\JamOperasional::layananBerjalan())
                        <span class="pt-pill info"><i class="bi bi-activity"></i>Layanan sampai {{ \App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam('layanan_selesai')) }}</span>
                    @else
                        <span class="pt-pill off"><i class="bi bi-moon-stars"></i>Layanan selesai</span>
                    @endif
                @endif
                <a href="{{ route('antrian.layar') }}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="bi bi-tv me-2"></i>Papan semua poli<span class="visually-hidden"> (tab baru)</span></a>
            </div>
        </div>
    </x-slot>

    <div class="pt-page">
        @include('partials.flash')

        <section aria-label="Ringkasan hari ini">
            <div class="pt-stats">
                <div class="pt-stat pt-c0"><span class="pt-stat-ico"><i class="bi bi-people"></i></span><div><div class="pt-stat-n">{{ $totalMenunggu }}</div><div class="pt-stat-l">Pasien menunggu</div></div></div>
                <div class="pt-stat pt-c1"><span class="pt-stat-ico"><i class="bi bi-megaphone"></i></span><div><div class="pt-stat-n">{{ $polis->sum('dipanggil_count') }}</div><div class="pt-stat-l">Sedang dipanggil</div></div></div>
                <div class="pt-stat pt-c5"><span class="pt-stat-ico"><i class="bi bi-check2-circle"></i></span><div><div class="pt-stat-n">{{ $polis->sum('selesai_count') }}</div><div class="pt-stat-l">Selesai hari ini</div></div></div>
                <div class="pt-stat pt-c4"><span class="pt-stat-ico"><i class="bi bi-building"></i></span><div><div class="pt-stat-n">{{ $polis->count() }}</div><div class="pt-stat-l">Poli aktif</div></div></div>
            </div>
        </section>

        <div class="pt-bar">
            <h2>Pilih poli untuk dikelola</h2>
            <div class="pt-live" id="ptLive" role="group" aria-label="Penyegaran otomatis">
                <span class="pt-live-dot" aria-hidden="true"></span>
                <span id="ptLiveTeks">Diperbarui otomatis tiap 30 detik</span>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="ptJeda" aria-pressed="false">Jeda</button>
            </div>
        </div>

        <section aria-label="Daftar poli">
            <div class="pt-poli-grid">
                @forelse ($polis as $poli)
                    @php
                        $persen = $poli->menunggu_count > 0 ? max(6, (int) round($poli->menunggu_count / $maks * 100)) : 0;
                        $ramai = $poli->menunggu_count > 10;
                        $terpadat = $palingRamai && $palingRamai->id === $poli->id && $poli->menunggu_count > 0 && ! $ramai;
                    @endphp
                    <article class="pt-poli {{ $warna($poli->kode_poli) }}" data-kode="{{ $poli->kode_poli }}" aria-labelledby="poli-{{ $poli->id }}">
                        <div class="pt-poli-top">
                            <span class="pt-kode" aria-hidden="true">{{ $poli->kode_poli }}</span>
                            <h3 class="pt-poli-nama" id="poli-{{ $poli->id }}">{{ $poli->nama_poli }}</h3>
                            @if ($ramai)
                                <span class="pt-tag"><i class="bi bi-exclamation-triangle-fill me-1"></i>Ramai</span>
                            @elseif ($terpadat)
                                <span class="pt-tag">Terpadat</span>
                            @endif
                        </div>

                        <div class="pt-wait">
                            <span class="pt-wait-n">{{ $poli->menunggu_count }}</span>
                            <span class="pt-wait-l">pasien menunggu</span>
                        </div>

                        <div class="pt-load" role="img" aria-label="Beban antrian {{ $persen }} persen dibanding poli terpadat"><span style="width: {{ $persen }}%"></span></div>

                        <div class="pt-counts">
                            <span class="pt-count"><i class="bi bi-megaphone" aria-hidden="true"></i><b>{{ $poli->dipanggil_count }}</b> dipanggil</span>
                            <span class="pt-count"><i class="bi bi-check2-circle" aria-hidden="true"></i><b>{{ $poli->selesai_count }}</b> selesai</span>
                        </div>

                        <div class="pt-poli-acts">
                            <a href="{{ route('antrian.kelola', $poli) }}" class="btn btn-primary">Kelola antrian<i class="bi bi-arrow-right ms-2" aria-hidden="true"></i><span class="visually-hidden"> {{ $poli->nama_poli }}</span></a>
                            <a href="{{ route('antrian.display', $poli) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" aria-label="Buka layar TV {{ $poli->nama_poli }} (tab baru)" title="Layar TV poli ini"><i class="bi bi-tv" aria-hidden="true"></i></a>
                        </div>
                    </article>
                @empty
                    <div class="pt-sec w-100"><div class="pt-empty"><i class="bi bi-building" aria-hidden="true"></i><div>Belum ada poli aktif. Minta admin menambahkan poli lewat menu Poli.</div></div></div>
                @endforelse
            </div>
        </section>
    </div>

    <script>
    (function () {
        var jam = document.getElementById('ptJam');
        function tik() {
            if (!jam) return;
            var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
            jam.textContent = p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
        }
        setInterval(tik, 1000);

        // Penyegaran otomatis, bisa dijeda (aksesibilitas: pengguna mengendalikan pembaruan)
        var jeda = false, tombol = document.getElementById('ptJeda'), box = document.getElementById('ptLive'), teks = document.getElementById('ptLiveTeks');
        tombol.addEventListener('click', function () {
            jeda = !jeda;
            tombol.setAttribute('aria-pressed', jeda ? 'true' : 'false');
            tombol.textContent = jeda ? 'Lanjutkan' : 'Jeda';
            box.classList.toggle('is-jeda', jeda);
            teks.textContent = jeda ? 'Pembaruan dijeda' : 'Diperbarui otomatis tiap 30 detik';
        });
        setInterval(function () {
            if (!jeda && document.visibilityState === 'visible') location.reload();
        }, 30000);
    })();
    </script>
</x-app-layout>
