@php
    // nilai bawaan supaya tampilan tetap aman walau ada data yang belum terkirim dari komponen
    $baris = $baris ?? collect();
    $aktif = $aktif ?? null;
    $jumlahPoli = $baris->count();
    $poliMelayani = $baris->filter(fn ($b) => $b->sekarang)->count();
    // kepadatan daftar: 1 = lega, 2 = rapat, 3 = dua kolom, 4 = tiga kolom ringkas
    $tier = $jumlahPoli <= 5 ? 1 : ($jumlahPoli <= 8 ? 2 : ($jumlahPoli <= 16 ? 3 : 4));
@endphp
<div wire:poll.2s.keep-alive="segarkan" class="ly {{ $jeda ? 'is-jeda' : '' }}" data-t="{{ now()->timestamp }}">

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

        {{-- Poli yang sedang disorot: berputar otomatis, melompat saat ada panggilan baru --}}
        <section class="ly-now" aria-live="polite">
            <p class="ly-label"><span class="ly-dot"></span>
                @if ($aktif && $aktif->sekarang && $aktif->sekarang->status === 'dilayani')
                    Sedang dilayani
                @elseif ($aktif && ! $aktif->sekarang)
                    Belum ada panggilan
                @else
                    Sedang dipanggil
                @endif
                @if ($aktif && $aktif->sekarang && $aktif->sekarang->jumlah_panggil > 1)
                    <span class="ly-badge">Panggilan ke-{{ $aktif->sekarang->jumlah_panggil }}</span>
                @endif
                @if ($aktif && $panggilanBaru)
                    <span class="ly-badge ly-badge-baru">Panggilan baru</span>
                @endif
            </p>

            @if ($aktif)
                <div wire:key="sorot-{{ $aktif->poli->id }}-{{ $aktif->sekarang?->id ?? 'kosong' }}-{{ $aktif->sekarang?->jumlah_panggil ?? 0 }}">
                    <div class="ly-who">
                        <span class="ly-kode">{{ $aktif->poli->kode_poli }}</span>
                        <h1 class="ly-poli-nama">{{ $aktif->poli->nama_poli }}</h1>
                    </div>
                    @if ($aktif->sekarang)
                        <div class="ly-no numeral" aria-label="Nomor {{ $aktif->sekarang->nomor_antrian }}">
                            @foreach (str_split((string) $aktif->sekarang->nomor_antrian) as $i => $c)<span class="ly-ch" style="--i:{{ $i }}" aria-hidden="true">{{ $c }}</span>@endforeach
                            <i class="ly-ring" aria-hidden="true"></i>
                        </div>
                    @else
                        <div class="ly-no numeral is-kosong" aria-hidden="true">···</div>
                    @endif
                </div>
                <p class="ly-ket">
                    @if ($aktif->sekarang)
                        @if ($aktif->sekarang->status === 'dilayani')
                            <span class="is-cta"><i class="bi bi-heart-pulse"></i>Sedang dilayani</span>
                        @else
                            <span class="is-cta"><i class="bi bi-arrow-right-circle-fill"></i>Silakan menuju {{ $aktif->poli->nama_poli }}</span>
                        @endif
                        @if ($aktif->sekarang->dokter)
                            <span><i class="bi bi-person-badge-fill"></i>{{ $aktif->sekarang->dokter->nama }}</span>
                        @endif
                    @else
                        <span class="is-muted">Belum ada pasien yang dipanggil</span>
                    @endif
                </p>
            @else
                <div><div class="ly-no numeral is-kosong" aria-hidden="true">···</div></div>
                <p class="ly-ket"><span class="is-muted">Belum ada poli aktif</span></p>
            @endif

            <div class="ly-stats">
                <div class="ly-stat"><span class="numeral">{{ $baris->sum('menunggu') }}</span><span>pasien<br>menunggu</span></div>
                <div class="ly-stat"><span class="numeral">{{ $poliMelayani }}<small style="font-size:.5em;opacity:.55">/{{ $jumlahPoli }}</small></span><span>poli sedang<br>melayani</span></div>
            </div>
        </section>

        {{-- Semua poli --}}
        <aside class="ly-side" aria-label="Semua poli">
            <div class="ly-side-head">
                <h2 class="ly-side-title">Semua poli</h2>
                <div class="ly-side-tools">
                    <span class="ly-side-sub">{{ $jumlahPoli }} poli aktif</span>
                    @if ($jumlahPoli > 1)
                        <button type="button" class="ly-jeda" wire:click="toggleJeda" aria-pressed="{{ $jeda ? 'true' : 'false' }}"
                                aria-label="{{ $jeda ? 'Lanjutkan pergantian poli otomatis' : 'Jeda pergantian poli otomatis' }}"
                                title="{{ $jeda ? 'Lanjutkan pergantian otomatis' : 'Jeda pergantian otomatis' }}">
                            <i class="bi {{ $jeda ? 'bi-play-fill' : 'bi-pause-fill' }}" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>
            <ul class="ly-list" data-n="{{ $tier }}">
                @forelse ($baris as $i => $b)
                    @php $sorot = $aktif && $aktif->poli->id === $b->poli->id; @endphp
                    <li class="ly-card ly-c{{ $i % 6 }} {{ $sorot ? 'is-active' : '' }}" style="--k:{{ min($i, 12) }}"
                        wire:key="poli-{{ $b->poli->id }}"
                        wire:click="pilih({{ $b->poli->id }})"
                        wire:keydown.enter="pilih({{ $b->poli->id }})"
                        role="button" tabindex="0"
                        @if ($sorot) aria-current="true" @endif
                        aria-label="Tampilkan {{ $b->poli->nama_poli }}">
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
