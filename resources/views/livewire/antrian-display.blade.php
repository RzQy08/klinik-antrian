@php
    $berikut = $berikut ?? collect();
    $diRuangan = $diRuangan ?? collect();
    $telat = $telat ?? collect();
    $sisa = $sisa ?? 0;
@endphp
<div wire:poll.2s.keep-alive class="ly ly-c{{ ($poli->id ?? 0) % 6 }}" data-t="{{ now()->timestamp }}">

    <header class="ly-top">
        @include('partials.brand', ['terang' => true])
        <div class="ly-tools" wire:ignore>
            <span class="ly-live"><span class="ly-dot"></span><span class="on">Langsung</span><span class="off">Menyambung ulang</span></span>
            <button type="button" class="ly-fs" data-fs aria-label="Layar penuh" title="Layar penuh (tombol F)"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i><i class="bi bi-fullscreen-exit" aria-hidden="true"></i></button>
        </div>
        <div class="ly-clock">
            <span class="ly-jam numeral">{{ now()->format('H:i') }}</span>
            <span class="ly-tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </header>

    <div class="ly-main" data-n="1">

        <section class="ly-now" aria-live="polite">
            <p class="ly-label"><span class="ly-dot"></span>Sedang dipanggil
                @if ($sedangDipanggil && $sedangDipanggil->jumlah_panggil > 1)
                    <span class="ly-badge">Panggilan ke-{{ $sedangDipanggil->jumlah_panggil }}</span>
                @endif
            </p>

            @if ($sedangDipanggil)
                <div wire:key="panggil-{{ $sedangDipanggil->id }}-{{ $sedangDipanggil->jumlah_panggil }}">
                    <div class="ly-who">
                        <span class="ly-kode">{{ $poli->kode_poli }}</span>
                        <h1 class="ly-poli-nama">{{ $poli->nama_poli }}</h1>
                    </div>
                    <div class="ly-no numeral" aria-label="Nomor {{ $sedangDipanggil->nomor_antrian }}">
                        @foreach (str_split((string) $sedangDipanggil->nomor_antrian) as $i => $c)<span class="ly-ch" style="--i:{{ $i }}" aria-hidden="true">{{ $c }}</span>@endforeach
                        <i class="ly-ring" aria-hidden="true"></i>
                    </div>
                </div>
                <p class="ly-ket">
                    <span class="is-cta"><i class="bi bi-arrow-right-circle-fill"></i>Silakan menuju {{ $poli->nama_poli }}</span>
                    @if ($sedangDipanggil->dokter)
                        <span><i class="bi bi-person-badge-fill"></i>{{ $sedangDipanggil->dokter->nama }}</span>
                    @endif
                </p>
            @else
                <div>
                    <div class="ly-who"><span class="ly-kode">{{ $poli->kode_poli }}</span><h1 class="ly-poli-nama">{{ $poli->nama_poli }}</h1></div>
                    <div class="ly-no numeral is-kosong" aria-hidden="true">···</div>
                </div>
                <p class="ly-ket"><span class="is-muted">Belum ada pasien yang dipanggil</span></p>
            @endif
        </section>

        <aside class="ly-side ly-side-poli" aria-label="Status antrian {{ $poli->nama_poli }}">
            @if ($diRuangan->count() > 1 || $telat->isNotEmpty())
                <div class="ly-panel">
                    <div class="ly-side-head"><h2 class="ly-side-title">Di ruang dokter</h2><span class="ly-side-sub">{{ $diRuangan->count() }} dokter melayani</span></div>
                    <ul class="ly-rooms">
                        @foreach ($diRuangan as $r)
                            <li wire:key="r-{{ $r->id }}-{{ $r->jumlah_panggil }}">
                                <span class="numeral">{{ $r->nomor_antrian }}</span>
                                <span class="ly-room-info"><b>{{ $r->dokter?->nama ?? 'Dokter' }}</b><small>{{ $r->status === 'dilayani' ? 'Sedang diperiksa' : 'Dipanggil, silakan masuk' }}</small></span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($telat->isNotEmpty())
                        <p class="ly-late"><i class="bi bi-hourglass-split" aria-hidden="true"></i><span>Terlambat, segera hadir:</span>
                            @foreach ($telat->take(6) as $t)<b class="numeral">{{ $t }}</b>@endforeach
                            @if ($telat->count() > 6)<em>+{{ $telat->count() - 6 }}</em>@endif
                        </p>
                    @endif
                </div>
            @endif

            <div class="ly-panel ly-panel-next">
                <div class="ly-side-head">
                    <h2 class="ly-side-title">Berikutnya</h2>
                    <span class="ly-side-sub">Harap bersiap</span>
                </div>
                @if ($berikut->isEmpty())
                    <p class="ly-empty">Belum ada yang menunggu</p>
                @else
                    <ol class="ly-grid">
                        @foreach ($berikut as $i => $no)
                            <li @class(['is-first' => $i === 0, 'is-soon' => $i > 0 && $i < 3]) style="--k:{{ $i }}" wire:key="n-{{ $no }}"><span class="numeral">{{ $no }}</span></li>
                        @endforeach
                        @if ($sisa > 0)<li class="is-more"><span>+{{ $sisa }}<small>lagi</small></span></li>@endif
                    </ol>
                @endif
            </div>

            <div class="ly-wait">
                <span>Masih menunggu<small>pasien di poli ini</small></span>
                <span class="numeral">{{ $jumlahMenunggu }}</span>
            </div>
        </aside>
    </div>
</div>
