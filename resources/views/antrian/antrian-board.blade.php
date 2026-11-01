@php
    // Warna tiap poli dipilih dari huruf kode poli, jadi selalu konsisten
    $warna = fn ($kode) => 'pa-c'.(ord(strtoupper(substr((string) $kode, 0, 1))) % 6);
@endphp
<div wire:poll.2s.visible class="pa-shell">

    <header class="pa-top">
        @include('partials.brand', ['terang' => true])
        <div class="pa-clock">
            <span class="pa-jam numeral">{{ now()->format('H:i') }}</span>
            <span class="pa-tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </header>

    <div class="pa-main">

        {{-- Panggilan terbaru dari poli mana pun --}}
        <section class="pa-hero" aria-live="polite" aria-atomic="true">
            <p class="pa-label">
                <span class="pa-live" aria-hidden="true"></span>
                Panggilan terbaru
                @if ($terbaru && $terbaru->jumlah_panggil > 1)
                    <span class="pa-badge">Panggilan ke-{{ $terbaru->jumlah_panggil }}</span>
                @endif
            </p>

            @if ($terbaru)
                <div>
                    <div class="pa-hero-poli">
                        <span class="pa-kode">{{ $poliTerbaru?->kode_poli }}</span>
                        <h1 class="pa-hero-nama">{{ $poliTerbaru?->nama_poli }}</h1>
                    </div>
                    <div wire:key="terbaru-{{ $terbaru->id }}-{{ $terbaru->jumlah_panggil }}" class="pa-no numeral pa-baru">{{ $terbaru->nomor_antrian }}</div>
                </div>

                <div class="pa-banner">
                    <span>
                        @if ($terbaru->status === 'dilayani')
                            <i class="bi bi-heart-pulse"></i>Sedang dilayani
                        @else
                            <i class="bi bi-arrow-right-circle"></i>Silakan menuju {{ $poliTerbaru?->nama_poli }}
                        @endif
                    </span>
                    @if ($terbaru->dokter)
                        <span class="pa-sub"><i class="bi bi-person-badge"></i>{{ $terbaru->dokter->nama }}</span>
                    @endif
                </div>
            @else
                <div>
                    <div class="pa-no numeral is-kosong">&mdash;</div>
                </div>
                <div class="pa-banner"><span class="pa-sub">Belum ada pasien yang dipanggil</span></div>
            @endif

            <div>
                <p class="pa-prev-label">Panggilan sebelumnya</p>
                <ul class="pa-chips">
                    @forelse ($sebelumnya as $s)
                        <li>
                            <span class="numeral">{{ $s->nomor_antrian }}</span>
                            <small>{{ $s->waktu_dipanggil?->format('H:i') }}</small>
                        </li>
                    @empty
                        <li><small>Belum ada.</small></li>
                    @endforelse
                </ul>
            </div>
        </section>

        {{-- Semua poli --}}
        <aside class="pa-side" aria-label="Status semua poli">
            <h2 class="pa-side-title">Semua poli</h2>

            <div class="pa-rows">
                @forelse ($baris as $b)
                    @php $aktif = $terbaru && $terbaru->poli_id === $b->poli->id; @endphp
                    <div class="pa-row {{ $warna($b->poli->kode_poli) }} {{ $aktif ? 'is-active' : '' }}">
                        <span class="pa-kode">{{ $b->poli->kode_poli }}</span>
                        <div>
                            <div class="pa-row-nama">{{ $b->poli->nama_poli }}</div>
                            <div class="pa-row-info">
                                <b>{{ $b->menunggu }}</b> menunggu@if ($b->berikut->isNotEmpty()), berikutnya {{ $b->berikut->implode(', ') }}@endif
                            </div>
                        </div>
                        <div wire:key="rn-{{ $b->poli->id }}-{{ $b->sekarang?->id }}-{{ $b->sekarang?->jumlah_panggil }}"
                             class="pa-row-no numeral {{ $b->sekarang ? 'pa-baru' : 'is-kosong' }}">{{ $b->sekarang?->nomor_antrian ?? '—' }}</div>
                    </div>
                @empty
                    <p class="pa-side-title">Belum ada poli aktif.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
