@php
    $aksen = 'pa-c'.(ord(strtoupper(substr((string) $poli->kode_poli, 0, 1))) % 6);
@endphp
<div wire:poll.2s.visible class="pa-shell {{ $aksen }}">

    <header class="pa-top">
        @include('partials.brand', ['terang' => true])
        <div class="pa-clock">
            <span class="pa-jam numeral">{{ now()->format('H:i') }}</span>
            <span class="pa-tgl">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
        </div>
    </header>

    <div class="pa-main">

        <section class="pa-hero" aria-live="polite" aria-atomic="true">
            <p class="pa-label">
                <span class="pa-live" aria-hidden="true"></span>
                Sedang dipanggil
                @if ($sedangDipanggil && $sedangDipanggil->jumlah_panggil > 1)
                    <span class="pa-badge">Panggilan ke-{{ $sedangDipanggil->jumlah_panggil }}</span>
                @endif
            </p>

            <div>
                <div class="pa-hero-poli">
                    <span class="pa-kode">{{ $poli->kode_poli }}</span>
                    <h1 class="pa-hero-nama">{{ $poli->nama_poli }}</h1>
                </div>
                @if ($sedangDipanggil)
                    <div wire:key="nomor-{{ $sedangDipanggil->id }}-{{ $sedangDipanggil->jumlah_panggil }}" class="pa-no numeral pa-baru">{{ $sedangDipanggil->nomor_antrian }}</div>
                @else
                    <div class="pa-no numeral is-kosong">&mdash;</div>
                @endif
            </div>

            <div class="pa-banner">
                @if ($sedangDipanggil)
                    <span>
                        @if ($sedangDipanggil->status === 'dilayani')
                            <i class="bi bi-heart-pulse"></i>Sedang dilayani
                        @else
                            <i class="bi bi-arrow-right-circle"></i>Silakan menuju {{ $poli->nama_poli }}
                        @endif
                    </span>
                    @if ($sedangDipanggil->dokter)
                        <span class="pa-sub"><i class="bi bi-person-badge"></i>{{ $sedangDipanggil->dokter->nama }}</span>
                    @endif
                @else
                    <span class="pa-sub">Belum ada pasien yang dipanggil</span>
                @endif
            </div>
        </section>

        <aside class="pa-side" aria-label="Riwayat panggilan">
            <h2 class="pa-side-title">Sudah dipanggil</h2>
            <ul class="pa-hist">
                @forelse ($riwayatDipanggil as $riwayat)
                    <li>
                        <span class="numeral">{{ $riwayat->nomor_antrian }}</span>
                        <small>{{ $riwayat->waktu_dipanggil?->format('H:i') }}</small>
                    </li>
                @empty
                    <li><small>Belum ada.</small></li>
                @endforelse
            </ul>

            <div class="pa-wait">
                <span>Masih menunggu</span>
                <span class="numeral">{{ $jumlahMenunggu }}</span>
                <span>pasien</span>
            </div>
        </aside>
    </div>
</div>
