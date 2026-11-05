@php
    // Warna tiap poli dipilih dari huruf kode poli, jadi selalu konsisten
    $warna = fn ($kode) => 'pa-c'.(ord(strtoupper(substr((string) $kode, 0, 1))) % 6);
    $jumlahPoli = $baris->count();
@endphp
<div wire:poll.2s.visible="segarkan" class="pa-shell pa-board {{ $jeda ? 'is-jeda' : '' }}">

    <header class="pa-top">
        @include('partials.brand', ['terang' => true])
        <div class="pa-tools">
            <button type="button" class="pa-fs" aria-label="Layar penuh" title="Layar penuh"
                    onclick="document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen()">
                <i class="bi bi-fullscreen" aria-hidden="true"></i>
            </button>
            <div class="pa-clock">
                <span class="pa-jam numeral">{{ now()->format('H:i') }}</span>
                <span class="pa-tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
            </div>
        </div>
    </header>

    <div class="pa-main">

        {{-- Sorotan poli: berputar otomatis, melompat saat ada panggilan baru --}}
        <section class="pa-hero" aria-label="Sorotan poli">
            <div class="pa-stage">
                @forelse ($baris as $b)
                    @php
                        $aktif = $b->poli->id === $aktifId;
                        $s = $b->sekarang;
                    @endphp
                    <article wire:key="slide-{{ $b->poli->id }}"
                             class="pa-slide {{ $warna($b->poli->kode_poli) }} {{ $aktif ? 'is-active' : '' }}"
                             @unless ($aktif) aria-hidden="true" @endunless>

                        <p class="pa-label">
                            <span class="pa-live" aria-hidden="true"></span>
                            @if ($s)
                                {{ $s->status === 'dilayani' ? 'Sedang dilayani' : 'Sedang dipanggil' }}
                            @else
                                Belum ada panggilan
                            @endif
                            @if ($s && $s->jumlah_panggil > 1)
                                <span class="pa-badge">Panggilan ke-{{ $s->jumlah_panggil }}</span>
                            @endif
                            @if ($aktif && $panggilanBaru)
                                <span class="pa-badge pa-badge-baru">Panggilan baru</span>
                            @endif
                        </p>

                        <div class="pa-slide-mid">
                            <div class="pa-hero-poli">
                                <span class="pa-kode">{{ $b->poli->kode_poli }}</span>
                                <h2 class="pa-hero-nama">{{ $b->poli->nama_poli }}</h2>
                            </div>
                            @if ($s)
                                <div wire:key="no-{{ $s->id }}-{{ $s->jumlah_panggil }}" class="pa-no numeral">{{ $s->nomor_antrian }}</div>
                            @else
                                <div class="pa-no numeral is-kosong">&mdash;</div>
                            @endif
                        </div>

                        <div class="pa-banner">
                            @if ($s)
                                <span>
                                    @if ($s->status === 'dilayani')
                                        <i class="bi bi-heart-pulse" aria-hidden="true"></i>Sedang dilayani
                                    @else
                                        <i class="bi bi-arrow-right-circle" aria-hidden="true"></i>Silakan menuju {{ $b->poli->nama_poli }}
                                    @endif
                                </span>
                                @if ($s->dokter)
                                    <span class="pa-sub"><i class="bi bi-person-badge" aria-hidden="true"></i>{{ $s->dokter->nama }}</span>
                                @endif
                            @else
                                <span class="pa-sub">Belum ada pasien yang dipanggil</span>
                            @endif
                        </div>

                        <div class="pa-foot">
                            <div class="pa-next">
                                <span class="pa-foot-l">Berikutnya</span>
                                @forelse ($b->berikut as $nomor)
                                    <span class="pa-nochip numeral">{{ $nomor }}</span>
                                @empty
                                    <span class="pa-nochip is-kosong">Tidak ada yang menunggu</span>
                                @endforelse
                            </div>
                            <div class="pa-stats">
                                <span><b>{{ $b->menunggu }}</b> menunggu</span>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="pa-empty">Belum ada poli aktif.</div>
                @endforelse
            </div>

            @if ($jumlahPoli > 0)
                <nav class="pa-dots" aria-label="Pilih poli">
                    @foreach ($baris as $b)
                        @php $aktif = $b->poli->id === $aktifId; @endphp
                        <button type="button" wire:key="dot-{{ $b->poli->id }}" wire:click="pilih({{ $b->poli->id }})"
                                class="pa-dot {{ $warna($b->poli->kode_poli) }} {{ $aktif ? 'is-active' : '' }}"
                                aria-label="Tampilkan {{ $b->poli->nama_poli }}" @if ($aktif) aria-current="true" @endif>
                            <span class="k">{{ $b->poli->kode_poli }}</span>
                            @if ($aktif && $jumlahPoli > 1)
                                <i wire:key="fill-{{ $sampai }}" class="fill" style="--dur: {{ $durasiAktif }}s" aria-hidden="true"></i>
                            @endif
                        </button>
                    @endforeach

                    @if ($jumlahPoli > 1)
                        <button type="button" class="pa-jeda" wire:click="toggleJeda" aria-pressed="{{ $jeda ? 'true' : 'false' }}"
                                aria-label="{{ $jeda ? 'Lanjutkan pergantian poli otomatis' : 'Jeda pergantian poli otomatis' }}">
                            <i class="bi {{ $jeda ? 'bi-play-fill' : 'bi-pause-fill' }}" aria-hidden="true"></i>
                        </button>
                    @endif
                </nav>
            @endif
        </section>

        {{-- Semua poli --}}
        <aside class="pa-side" aria-label="Status semua poli">
            <h2 class="pa-side-title">Semua poli</h2>

            <div class="pa-rows">
                @forelse ($baris as $b)
                    @php $aktif = $b->poli->id === $aktifId; @endphp
                    <button type="button" wire:key="row-{{ $b->poli->id }}" wire:click="pilih({{ $b->poli->id }})"
                            class="pa-row {{ $warna($b->poli->kode_poli) }} {{ $aktif ? 'is-active' : '' }}"
                            aria-label="Tampilkan {{ $b->poli->nama_poli }}">
                        <span class="pa-kode">{{ $b->poli->kode_poli }}</span>
                        <span>
                            <span class="pa-row-nama d-block">{{ $b->poli->nama_poli }}</span>
                            <span class="pa-row-info d-block">
                                <b>{{ $b->menunggu }}</b> menunggu @if ($b->berikut->isNotEmpty()), berikutnya {{ $b->berikut->take(3)->implode(', ') }}@endif
                            </span>
                        </span>
                        <span wire:key="rn-{{ $b->poli->id }}-{{ $b->sekarang?->id }}-{{ $b->sekarang?->jumlah_panggil }}"
                              class="pa-row-no numeral {{ $b->sekarang ? 'pa-baru' : 'is-kosong' }}">{{ $b->sekarang?->nomor_antrian ?? '—' }}</span>
                    </button>
                @empty
                    <p class="pa-side-title">Belum ada poli aktif.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>