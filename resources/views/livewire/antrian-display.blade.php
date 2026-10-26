<div wire:poll.2s.visible class="tv-shell">

    <header class="tv-header">
        @include('partials.brand', ['terang' => true])
        <div class="tv-clock">
            <span class="jam numeral">{{ now()->format('H:i') }}</span>
            <span class="tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </header>

    <div class="tv-grid">

        <section class="tv-card tv-hero">
            <p class="tv-label mb-0">
                <span class="tv-dot"></span>
                Sedang dipanggil
                @if ($sedangDipanggil && $sedangDipanggil->jumlah_panggil > 1)
                    <span class="badge text-bg-warning">Panggilan ke-{{ $sedangDipanggil->jumlah_panggil }}</span>
                @endif
            </p>

            <div>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="kode kode-lg">{{ $poli->kode_poli }}</span>
                    <h1 class="tv-poli-name">{{ $poli->nama_poli }}</h1>
                </div>

                @if ($sedangDipanggil)
                    <div wire:key="nomor-{{ $sedangDipanggil->id }}-{{ $sedangDipanggil->jumlah_panggil }}" class="tv-nomor numeral nomor-baru">{{ $sedangDipanggil->nomor_antrian }}</div>
                    @if ($sedangDipanggil->dokter)
                        <div class="tv-dokter mt-2"><i class="bi bi-person-badge-fill"></i> {{ $sedangDipanggil->dokter->nama }}</div>
                    @endif
                    <p class="tv-instruksi">Silakan menuju {{ $poli->nama_poli }}</p>
                @else
                    <div class="tv-nomor numeral" style="color: rgba(255,255,255,.18)">-</div>
                    <p class="tv-dokter text-on-navy-muted">Belum ada pasien yang dipanggil</p>
                @endif
            </div>

            <div></div>
        </section>

        <aside class="tv-card tv-side">
            <h2 class="tv-side-title">Sudah dipanggil</h2>
            <ul class="tv-hist">
                @forelse ($riwayatDipanggil as $riwayat)
                    <li>
                        <span class="numeral">{{ $riwayat->nomor_antrian }}</span>
                        <small>{{ $riwayat->waktu_dipanggil?->format('H:i') }}</small>
                    </li>
                @empty
                    <li><small>Belum ada.</small></li>
                @endforelse
            </ul>

            <div class="tv-wait">
                <span class="tv-side-title">Masih menunggu</span>
                <span class="numeral jml">{{ $jumlahMenunggu }}</span>
                <span class="tv-side-title">pasien</span>
            </div>
        </aside>
    </div>
</div>
