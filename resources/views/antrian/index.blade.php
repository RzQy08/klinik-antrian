<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1">Antrian hari ini</h1>
                <p class="text-body-secondary mb-0">{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @if (\App\Services\JamOperasional::aktif())
                    @if (\App\Services\JamOperasional::pendaftaranDibuka())
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Pendaftaran dibuka</span>
                    @else
                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Pendaftaran ditutup</span>
                    @endif
                    @if (\App\Services\JamOperasional::layananBerjalan())
                        <span class="badge rounded-pill bg-info-subtle text-info-emphasis">Layanan sampai {{ \App\Services\JamOperasional::tampil(config('operasional.layanan_selesai')) }}</span>
                    @else
                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Layanan selesai</span>
                    @endif
                @endif
                <a href="{{ route('antrian.layar') }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm"><i class="bi bi-tv me-1"></i>Papan semua poli</a>
            </div>
        </div>
    </x-slot>

    @include('partials.flash')

    @php $maks = max(1, $polis->max('menunggu_count')); @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'people', 'label' => 'Pasien menunggu', 'nilai' => $polis->sum('menunggu_count'), 'warna' => 'primary'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'megaphone', 'label' => 'Sedang dipanggil', 'nilai' => $polis->sum('dipanggil_count'), 'warna' => 'warning'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'check-circle', 'label' => 'Selesai hari ini', 'nilai' => $polis->sum('selesai_count'), 'warna' => 'success'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'building', 'label' => 'Poli aktif', 'nilai' => $polis->count(), 'warna' => 'navy'])</div>
    </div>

    <div class="card">
        <div class="card-header"><h2 class="card-title-sm">Per poli</h2></div>
        <ul class="list-group list-group-flush">
            @forelse ($polis as $poli)
                <li class="list-group-item p-3 p-md-4">
                    <div class="row align-items-center g-3">
                        <div class="col-12 col-md-5 d-flex align-items-center gap-3">
                            <span class="kode">{{ $poli->kode_poli }}</span>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    {{ $poli->nama_poli }}
                                    @if ($poli->menunggu_count > 10)
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-1">Ramai</span>
                                    @endif
                                </div>
                                <div class="bar mt-2" style="max-width: 16rem" aria-hidden="true"><span class="bg-primary" style="width: {{ round($poli->menunggu_count / $maks * 100) }}%"></span></div>
                            </div>
                        </div>

                        <div class="col-7 col-md-4">
                            <div class="row row-cols-3 g-2 text-center">
                                <div class="col"><div class="numeral fs-3">{{ $poli->menunggu_count }}</div><div class="small text-body-secondary">Menunggu</div></div>
                                <div class="col"><div class="numeral fs-3">{{ $poli->dipanggil_count }}</div><div class="small text-body-secondary">Dipanggil</div></div>
                                <div class="col"><div class="numeral fs-3">{{ $poli->selesai_count }}</div><div class="small text-body-secondary">Selesai</div></div>
                            </div>
                        </div>

                        <div class="col-5 col-md-3 d-flex flex-wrap justify-content-end gap-2">
                            <a href="{{ route('antrian.kelola', $poli) }}" class="btn btn-primary btn-sm">Kelola</a>
                            <a href="{{ route('antrian.display', $poli) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" aria-label="Buka layar TV {{ $poli->nama_poli }}"><i class="bi bi-tv"></i></a>
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-group-item p-4 text-body-secondary">Belum ada poli aktif. Tambahkan poli lewat menu Poli.</li>
            @endforelse
        </ul>
    </div>
</x-app-layout>
