@php
    $berikut = $berikut ?? collect();
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

        <aside class="ly-side" aria-label="Antrian berikutnya">
            <div class="ly-side-head">
                <h2 class="ly-side-title">Berikutnya</h2>
                <span class="ly-side-sub">bersiap ya</span>
            </div>
            <ul class="ly-nextlist">
                @forelse ($berikut as $i => $no)
                    <li style="--k:{{ $i }}"><span class="numeral">{{ $no }}</span><small>{{ $i === 0 ? 'Selanjutnya' : 'Menyusul' }}</small></li>
                @empty
                    <li class="is-empty">Tidak ada antrian yang menunggu</li>
                @endforelse
            </ul>
            <div class="ly-wait">
                <span>Masih menunggu<small>pasien di poli ini</small></span>
                <span class="numeral">{{ $jumlahMenunggu }}</span>
            </div>
        </aside>
    </div>
</div>
