<x-app-layout>
    <x-slot name="header">
        <h1 class="h2 mb-1">Pasien saya</h1>
        <p class="text-body-secondary mb-0">{{ $dokter->nama }}, {{ $dokter->poli->nama_poli }}</p>
    </x-slot>

    @include('partials.flash')

    @php
        $sedangDilayani = $antrianSaya->firstWhere('status', 'dilayani');
        $menungguDimulai = $antrianSaya->where('status', 'dipanggil');
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6">@include('partials.stat', ['ikon' => 'megaphone', 'label' => 'Menunggu dimulai', 'nilai' => $menungguDimulai->count(), 'warna' => 'warning'])</div>
        <div class="col-6">@include('partials.stat', ['ikon' => 'check-circle', 'label' => 'Selesai (10 terakhir)', 'nilai' => $riwayatSelesaiHariIni->count(), 'warna' => 'success'])</div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8 d-grid gap-4 align-content-start">

            @if ($sedangDilayani)
                <section class="board board-pattern p-4 p-md-5">
                    <div class="small board-muted">Sedang dilayani</div>
                    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mt-2">
                        <div>
                            <div class="numeral" style="font-size: 4.5rem">{{ $sedangDilayani->nomor_antrian }}</div>
                            <div class="h4 text-white mt-3 mb-1">{{ $sedangDilayani->pasien->nama }}</div>
                            <div class="small board-muted">Mulai pukul {{ $sedangDilayani->waktu_mulai_dilayani?->format('H:i') }}</div>
                        </div>
                        <form method="POST" action="{{ route('antrian.selesai', $sedangDilayani) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-lg"><i class="bi bi-check-lg me-2"></i>Selesai melayani</button>
                        </form>
                    </div>
                </section>
            @else
                <div class="card"><div class="card-body text-body-secondary">Belum ada pasien yang sedang dilayani.</div></div>
            @endif

            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Menunggu dimulai ({{ $menungguDimulai->count() }})</h2></div>
                @if ($menungguDimulai->isEmpty())
                    <div class="card-body text-body-secondary">Belum ada pasien yang dipanggil untuk Anda. Petugas akan memanggil pasien berikutnya.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($menungguDimulai as $antrian)
                            <li class="list-group-item d-flex flex-wrap align-items-center gap-3 p-3 p-md-4">
                                <span class="numeral fs-2 text-navy" style="min-width: 5.5rem">{{ $antrian->nomor_antrian }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $antrian->pasien->nama }}</div>
                                    <div class="small text-body-secondary">Dipanggil pukul {{ $antrian->waktu_dipanggil?->format('H:i') }}</div>
                                </div>
                                <form method="POST" action="{{ route('antrian.mulai-layani', $antrian) }}">@csrf<button type="submit" class="btn btn-primary">Mulai layani</button></form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Terakhir selesai</h2></div>
                @if ($riwayatSelesaiHariIni->isEmpty())
                    <div class="card-body text-body-secondary small">Belum ada pasien yang selesai hari ini.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($riwayatSelesaiHariIni as $antrian)
                            <li class="list-group-item d-flex align-items-center gap-3 px-3 py-3">
                                <span class="numeral fs-4 text-body-secondary" style="min-width: 4rem">{{ $antrian->nomor_antrian }}</span>
                                <span class="flex-grow-1 small text-truncate">{{ $antrian->pasien->nama }}</span>
                                <span class="small text-body-secondary">{{ $antrian->waktu_selesai?->format('H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
