<div wire:poll.2s.visible class="tv-shell">

    <header class="tv-header">
        @include('partials.brand', ['terang' => true])
        <div class="tv-clock">
            <span class="jam numeral">{{ now()->format('H:i') }}</span>
            <span class="tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </header>

    <div class="tv-grid">

        {{-- Panggilan terbaru dari poli mana pun --}}
        <section class="tv-card tv-hero">
            <p class="tv-label mb-0">
                <span class="tv-dot"></span>
                Panggilan terbaru
                @if ($terbaru && $terbaru->jumlah_panggil > 1)
                    <span class="badge text-bg-warning">Panggilan ke-{{ $terbaru->jumlah_panggil }}</span>
                @endif
            </p>

            @if ($terbaru)
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="kode kode-lg">{{ $poliTerbaru?->kode_poli }}</span>
                        <h1 class="tv-poli-name">{{ $poliTerbaru?->nama_poli }}</h1>
                    </div>

                    <div wire:key="terbaru-{{ $terbaru->id }}-{{ $terbaru->jumlah_panggil }}" class="tv-nomor numeral nomor-baru">{{ $terbaru->nomor_antrian }}</div>

                    @if ($terbaru->dokter)
                        <div class="tv-dokter mt-2"><i class="bi bi-person-badge-fill"></i> {{ $terbaru->dokter->nama }}</div>
                    @endif
                    <p class="tv-instruksi">Silakan menuju {{ $poliTerbaru?->nama_poli }}</p>
                </div>
            @else
                <div>
                    <div class="tv-nomor numeral" style="color: rgba(255,255,255,.18)">-</div>
                    <p class="tv-dokter text-on-navy-muted">Belum ada pasien yang dipanggil</p>
                </div>
            @endif

            <div>
                <p class="tv-label mb-0">Panggilan sebelumnya</p>
                <ul class="tv-recent">
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
        <aside class="tv-card tv-side">
            <h2 class="tv-side-title">Semua poli</h2>

            <div class="tv-poli-list">
                @forelse ($baris as $b)
                    @php
                        $aktif = $terbaru && $terbaru->poli_id === $b->poli->id;
                        $info = $b->menunggu.' menunggu';
                        if ($b->berikut->isNotEmpty()) {
                            $info .= ', berikutnya '.$b->berikut->implode(', ');
                        }
                    @endphp
                    <div class="tv-poli-row {{ $aktif ? 'is-active' : '' }}">
                        <span class="kode">{{ $b->poli->kode_poli }}</span>
                        <div class="min-w-0">
                            <div class="nama text-truncate">{{ $b->poli->nama_poli }}</div>
                            <div class="info">{{ $info }}</div>
                        </div>
                        <div class="no numeral {{ $b->sekarang ? '' : 'kosong' }}">{{ $b->sekarang?->nomor_antrian ?? '—' }}</div>
                    </div>
                @empty
                    <p class="tv-side-title">Belum ada poli aktif.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
