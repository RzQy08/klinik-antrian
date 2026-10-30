<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1">Analitik antrian</h1>
                <p class="text-body-secondary mb-0">Data {{ $dari->locale('id')->translatedFormat('j M Y') }} sampai {{ $sampai->locale('id')->translatedFormat('j M Y') }}</p>
            </div>
            <div class="btn-group" role="group" aria-label="Rentang data">
                @foreach ([7, 14, 30] as $hari)
                    <a href="{{ route('antrian.analitik', ['hari' => $hari, 'target' => $target, 'sampai' => request('sampai')]) }}" class="btn btn-sm {{ $jumlahHari == $hari ? 'btn-primary' : 'btn-outline-primary' }}" @if ($jumlahHari == $hari) aria-current="true" @endif>{{ $hari }} hari</a>
                @endforeach
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
            <span class="small text-body-secondary">Target rata-rata tunggu untuk rekomendasi dokter:</span>
            <div class="btn-group" role="group" aria-label="Target waktu tunggu">
                @foreach ([5, 10, 15, 20] as $menit)
                    <a href="{{ route('antrian.analitik', ['hari' => $jumlahHari, 'target' => $menit, 'sampai' => request('sampai')]) }}" class="btn btn-sm {{ $target == $menit ? 'btn-primary' : 'btn-outline-primary' }}" @if ($target == $menit) aria-current="true" @endif>{{ $menit }} menit</a>
                @endforeach
            </div>
        </div>
    </x-slot>

    @php
        $dianalisis = $hasil->where('ada_data', true)->count();
        $stabil = $hasil->filter(fn ($h) => $h['ada_data'] && $h['stabil'])->count();
        $tidakStabil = $hasil->filter(fn ($h) => $h['ada_data'] && ! $h['stabil'])->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">@include('partials.stat', ['ikon' => 'bar-chart-line', 'label' => 'Poli dianalisis', 'nilai' => $dianalisis, 'warna' => 'primary'])</div>
        <div class="col-6 col-md-4">@include('partials.stat', ['ikon' => 'check-circle', 'label' => 'Poli stabil', 'nilai' => $stabil, 'warna' => 'success'])</div>
        <div class="col-6 col-md-4">@include('partials.stat', ['ikon' => 'exclamation-triangle', 'label' => 'Perlu tambahan dokter', 'nilai' => $tidakStabil, 'warna' => 'warning'])</div>
    </div>

    @foreach ($hasil as $item)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center gap-2">
                <h2 class="card-title-sm">{{ $item['poli']->nama_poli }}</h2>
                <span class="small text-body-secondary">{{ $item['jumlah_sampel'] }} pasien dianalisis</span>
            </div>

            <div class="card-body">
                @if (! $item['ada_data'])
                    <p class="mb-0">Belum ada data selesai yang cukup pada rentang ini. Coba perlebar rentang hari.</p>
                    @if (isset($item['pesan_error']))
                        <p class="small text-body-secondary mt-1 mb-0">{{ $item['pesan_error'] }}</p>
                    @endif
                @elseif (! $item['stabil'])
                    <div class="alert alert-danger mb-0" role="alert">
                        Sistem tidak stabil: kapasitas terpakai {{ round($item['rho'] * 100) }}% (ρ = {{ $item['rho'] }}).
                        Pasien datang lebih cepat daripada kemampuan dokter melayani, jadi antrian akan terus memanjang.
                        Tambahkan dokter atau perpendek waktu layanan.
                    </div>
                @else
                    @php
                        $teori = $item['wq_teori_menit'] ?? 0;
                        $aktual = $item['wq_aktual_menit'] ?? 0;
                        $maks = max($teori, $aktual, 0.1);
                        $rho = $item['rho'];
                        $rhoPersen = min(100, round($rho * 100));
                        $rhoWarna = $rho >= 0.85 ? 'bg-danger' : ($rho >= 0.7 ? 'bg-warning' : 'bg-success');
                    @endphp

                    <div class="row g-4">
                        <div class="col-lg-6">
                            <h3 class="h6 mb-3">Beban layanan</h3>
                            <div class="row row-cols-3 g-3 mb-4">
                                <div class="col"><div class="numeral fs-2 text-navy">{{ $item['lambda'] }}</div><div class="small text-body-secondary mt-1">pasien datang per jam (λ)</div></div>
                                <div class="col"><div class="numeral fs-2 text-navy">{{ $item['mu'] }}</div><div class="small text-body-secondary mt-1">dilayani per jam per dokter (μ)</div></div>
                                <div class="col"><div class="numeral fs-2 text-navy">{{ $item['jumlah_server'] }}</div><div class="small text-body-secondary mt-1">dokter bertugas (c)</div></div>
                            </div>
                            <div class="d-flex justify-content-between small mb-1"><span class="text-body-secondary">Kapasitas terpakai (ρ)</span><span class="fw-semibold">{{ $rhoPersen }}%</span></div>
                            <div class="bar" role="img" aria-label="Kapasitas terpakai {{ $rhoPersen }} persen"><span class="{{ $rhoWarna }}" style="width: {{ $rhoPersen }}%"></span></div>
                        </div>

                        <div class="col-lg-6">
                            <h3 class="h6 mb-3">Rata-rata waktu tunggu</h3>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span class="text-body-secondary">Hitungan teori (Erlang C)</span><span class="fw-semibold">{{ $teori }} menit</span></div>
                                <div class="bar"><span class="bg-primary" style="width: {{ round($teori / $maks * 100) }}%"></span></div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span class="text-body-secondary">Data riil</span><span class="fw-semibold">{{ $aktual }} menit</span></div>
                                <div class="bar"><span class="bg-warning" style="width: {{ round($aktual / $maks * 100) }}%"></span></div>
                            </div>
                            @if ($item['persentase_error'] !== null)
                                <div class="d-flex flex-wrap align-items-center gap-2 small text-body-secondary">
                                    Selisih {{ $item['selisih_menit'] }} menit ({{ $item['persentase_error'] }}%)
                                    @if ($item['persentase_error'] <= 20)
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Model sesuai</span>
                                    @else
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Selisih besar</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($item['ada_data'])
                    @include('partials.simulasi-dokter', ['item' => $item, 'target' => $target])
                @endif
            </div>
        </div>
    @endforeach

    <p class="small text-body-secondary" style="max-width: 44rem">
        Waktu tunggu teori dihitung dari λ dan μ rata-rata data historis dengan model M/M/c. Waktu tunggu riil diambil dari
        selisih waktu daftar dan waktu mulai dilayani setiap pasien. Selisih besar menandakan pola kedatangan atau lama
        layanan yang menyimpang dari asumsi Poisson dan eksponensial.
    </p>
</x-app-layout>
