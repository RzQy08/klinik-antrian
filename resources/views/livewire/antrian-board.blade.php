@php
    // nilai bawaan supaya tampilan tetap aman walau ada data yang belum terkirim dari komponen
    $terbaru = $terbaru ?? null;
    $poliTerbaru = $poliTerbaru ?? null;
    $baris = $baris ?? collect();
    $jumlahPoli = $baris->count();
    // kepadatan daftar: 1 = lega, 2 = rapat, 3 = dua kolom, 4 = tiga kolom ringkas
    $tier = $jumlahPoli <= 5 ? 1 : ($jumlahPoli <= 8 ? 2 : ($jumlahPoli <= 16 ? 3 : 4));
@endphp
<div wire:poll.2s.keep-alive class="ly" data-t="{{ now()->timestamp }}">

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

    <div class="ly-main" data-n="{{ $tier }}">

        {{-- Panggilan terbaru dari poli mana pun --}}
        <section class="ly-now" aria-live="polite">
            <p class="ly-label"><span class="ly-dot"></span>Sedang dipanggil
                @if ($terbaru && $terbaru->jumlah_panggil > 1)
                    <span class="ly-badge">Panggilan ke-{{ $terbaru->jumlah_panggil }}</span>
                @endif
            </p>

            @if ($terbaru)
                <div wire:key="panggil-{{ $terbaru->id }}-{{ $terbaru->jumlah_panggil }}">
                    <div class="ly-who">
                        <span class="ly-kode">{{ $poliTerbaru?->kode_poli }}</span>
                        <h1 class="ly-poli-nama">{{ $poliTerbaru?->nama_poli }}</h1>
                    </div>
                    <div class="ly-no numeral" aria-label="Nomor {{ $terbaru->nomor_antrian }}">
                        @foreach (str_split((string) $terbaru->nomor_antrian) as $i => $c)<span class="ly-ch" style="--i:{{ $i }}" aria-hidden="true">{{ $c }}</span>@endforeach
                        <i class="ly-ring" aria-hidden="true"></i>
                    </div>
                </div>
                <p class="ly-ket">
                    <span class="is-cta"><i class="bi bi-arrow-right-circle-fill"></i>Silakan menuju {{ $poliTerbaru?->nama_poli }}</span>
                    @if ($terbaru->dokter)
                        <span><i class="bi bi-person-badge-fill"></i>{{ $terbaru->dokter->nama }}</span>
                    @endif
                </p>
            @else
                <div><div class="ly-no numeral is-kosong" aria-hidden="true">···</div></div>
                <p class="ly-ket"><span class="is-muted">Belum ada pasien yang dipanggil</span></p>
            @endif

            <div class="ly-stats">
                <div class="ly-stat"><span class="numeral">{{ $baris->sum('menunggu') }}</span><span>pasien<br>menunggu</span></div>
                <div class="ly-stat"><span class="numeral">{{ $baris->filter(fn ($b) => $b->sekarang)->count() }}<small style="font-size:.5em;opacity:.55">/{{ $jumlahPoli }}</small></span><span>poli sedang<br>melayani</span></div>
            </div>
        </section>

        {{-- Semua poli --}}
        <aside class="ly-side" aria-label="Semua poli">
            <div class="ly-side-head">
                <h2 class="ly-side-title">Semua poli</h2>
                <span class="ly-side-sub">{{ $jumlahPoli }} poli aktif</span>
            </div>
            <ul class="ly-list" data-n="{{ $tier }}">
                @forelse ($baris as $i => $b)
                    @php $aktif = $terbaru && $terbaru->poli_id === $b->poli->id; @endphp
                    <li class="ly-card ly-c{{ $i % 6 }} {{ $aktif ? 'is-active' : '' }}" style="--k:{{ min($i, 12) }}" wire:key="poli-{{ $b->poli->id }}">
                        <span class="ly-kode">{{ $b->poli->kode_poli }}</span>
                        <div class="ly-card-body">
                            <div class="ly-card-nama">{{ $b->poli->nama_poli }}</div>
                            <div class="ly-card-info">
                                <span><b>{{ $b->menunggu }}</b> menunggu</span>
                                @if ($b->berikut->isNotEmpty())
                                    <span>berikut:</span>
                                    @foreach ($b->berikut as $n)<span class="ly-next">{{ $n }}</span>@endforeach
                                @endif
                            </div>
                        </div>
                        <div class="ly-card-no">
                            <small>Sekarang</small>
                            @if ($b->sekarang)
                                <span class="numeral" wire:key="n-{{ $b->sekarang->id }}-{{ $b->sekarang->jumlah_panggil }}">{{ $b->sekarang->nomor_antrian }}</span>
                            @else
                                <span class="numeral is-kosong">-</span>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="ly-kosong">Belum ada poli aktif.</li>
                @endforelse
            </ul>
        </aside>
    </div>
</div>
