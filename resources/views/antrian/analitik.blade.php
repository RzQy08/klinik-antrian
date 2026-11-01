@php
    $warna = fn ($kode) => 'lp-c'.(ord(strtoupper(substr((string) $kode, 0, 1))) % 6);
    $dianalisis = $hasil->where('ada_data', true)->count();
    $stabil = $hasil->filter(fn ($h) => $h['ada_data'] && $h['stabil'])->count();
    $tidakStabil = $hasil->filter(fn ($h) => $h['ada_data'] && ! $h['stabil'])->count();
    // Poli yang perlu perhatian: tidak stabil, atau rekomendasi dokter lebih banyak dari yang bertugas
    $perlu = $hasil->filter(fn ($h) => $h['ada_data'] && (! $h['stabil'] || (($h['rekomendasi_dokter'] ?? 0) > $h['jumlah_server'])));
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="lp-head">
            <div>
                <h1 class="lp-title">Analitik antrian</h1>
                <p class="lp-sub">Data {{ $dari->locale('id')->translatedFormat('j M Y') }} sampai {{ $sampai->locale('id')->translatedFormat('j M Y') }}</p>
            </div>
            <div class="lp-toolbar">
                <span class="lp-lab" id="lab-rentang">Rentang</span>
                <div class="lp-seg" role="group" aria-labelledby="lab-rentang">
                    @foreach ([7, 14, 30] as $hari)
                        <a href="{{ route('antrian.analitik', ['hari' => $hari, 'target' => $target, 'sampai' => request('sampai')]) }}" class="{{ $jumlahHari == $hari ? 'is-on' : '' }}" @if ($jumlahHari == $hari) aria-current="true" @endif>{{ $hari }} hari</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="lp-toolbar mt-3">
            <span class="lp-lab" id="lab-target">Target rata-rata tunggu untuk rekomendasi dokter</span>
            <div class="lp-seg" role="group" aria-labelledby="lab-target">
                @foreach ([5, 10, 15, 20] as $menit)
                    <a href="{{ route('antrian.analitik', ['hari' => $jumlahHari, 'target' => $menit, 'sampai' => request('sampai')]) }}" class="{{ $target == $menit ? 'is-on' : '' }}" @if ($target == $menit) aria-current="true" @endif>{{ $menit }} menit</a>
                @endforeach
            </div>
        </div>
    </x-slot>

    <div class="lp-page">
        <section aria-label="Ringkasan analitik">
            <div class="lp-stats">
                <div class="lp-stat lp-c0"><span class="lp-stat-ico"><i class="bi bi-bar-chart-line"></i></span><div><div class="lp-stat-n">{{ $dianalisis }}</div><div class="lp-stat-l">Poli dianalisis</div></div></div>
                <div class="lp-stat lp-c5"><span class="lp-stat-ico"><i class="bi bi-check-circle"></i></span><div><div class="lp-stat-n">{{ $stabil }}</div><div class="lp-stat-l">Poli stabil</div></div></div>
                <div class="lp-stat lp-c2"><span class="lp-stat-ico"><i class="bi bi-exclamation-triangle"></i></span><div><div class="lp-stat-n">{{ $tidakStabil }}</div><div class="lp-stat-l">Perlu tambahan dokter</div></div></div>
            </div>

            @if ($dianalisis > 0)
                @if ($perlu->isNotEmpty())
                    <div class="lp-insight" role="status"><i class="bi bi-lightbulb" aria-hidden="true"></i><div><strong>Perlu perhatian:</strong> {{ $perlu->map(fn ($h) => $h['poli']->nama_poli)->implode(', ') }}. Buka kartu poli di bawah untuk melihat simulasi dan rekomendasi jumlah dokter.</div></div>
                @else
                    <div class="lp-insight is-baik" role="status"><i class="bi bi-check2-circle" aria-hidden="true"></i><div>Semua poli yang dianalisis sudah cukup dokter untuk target tunggu {{ $target }} menit.</div></div>
                @endif
            @endif
        </section>

        @foreach ($hasil as $item)
            @php
                $rho = $item['rho'] ?? 0;
                $rhoPersen = min(100, (int) round($rho * 100));
                $kelas = ! ($item['stabil'] ?? false) ? 'bad' : ($rho >= 0.85 ? 'bad' : ($rho >= 0.7 ? 'warn' : 'ok'));
                $label = ! ($item['stabil'] ?? false) ? 'Tidak stabil' : ($rho >= 0.85 ? 'Kritis' : ($rho >= 0.7 ? 'Padat' : 'Aman'));
            @endphp
            <article class="lp-card {{ $warna($item['poli']->kode_poli) }}" aria-labelledby="poli-{{ $item['poli']->id }}">
                <header class="lp-card-head">
                    <span class="lp-kode" aria-hidden="true">{{ $item['poli']->kode_poli }}</span>
                    <h2 class="lp-card-nama" id="poli-{{ $item['poli']->id }}">{{ $item['poli']->nama_poli }}</h2>
                    @if ($item['ada_data'])<span class="lp-flag {{ $kelas }}">{{ $label }}</span>@endif
                    <span class="lp-card-sampel">{{ $item['jumlah_sampel'] }} pasien dianalisis</span>
                </header>

                @if (! $item['ada_data'])
                    <div class="lp-body"><div>
                        <p class="mb-0">Belum ada data selesai yang cukup pada rentang ini. Coba perlebar rentang hari.</p>
                        @if (isset($item['pesan_error']))<p class="small text-body-secondary mt-1 mb-0">{{ $item['pesan_error'] }}</p>@endif
                    </div></div>
                @elseif (! $item['stabil'])
                    <div class="lp-alert" role="alert">
                        <strong>Sistem tidak stabil.</strong> Kapasitas terpakai {{ round($rho * 100) }}% (ρ = {{ $rho }}).
                        Pasien datang lebih cepat daripada kemampuan dokter melayani, jadi antrian akan terus memanjang.
                        Tambahkan dokter atau perpendek waktu layanan.
                    </div>
                @else
                    @php
                        $teori = $item['wq_teori_menit'] ?? 0;
                        $aktual = $item['wq_aktual_menit'] ?? 0;
                        $maks = max($teori, $aktual, 0.1);
                    @endphp
                    <div class="lp-body">
                        <section aria-label="Beban layanan">
                            <h3 class="lp-h3">Beban layanan</h3>
                            <div class="lp-metrics">
                                <div><div class="lp-metric-n">{{ $item['lambda'] }}</div><div class="lp-metric-l">pasien datang per jam (λ)</div></div>
                                <div><div class="lp-metric-n">{{ $item['mu'] }}</div><div class="lp-metric-l">dilayani per jam per dokter (μ)</div></div>
                                <div><div class="lp-metric-n">{{ $item['jumlah_server'] }}</div><div class="lp-metric-l">dokter bertugas (c)</div></div>
                            </div>
                            <div class="lp-gauge-row">
                                <div class="lp-gauge is-{{ $kelas }}" style="--p: {{ $rhoPersen }}" role="img" aria-label="Kapasitas terpakai {{ $rhoPersen }} persen"><span>{{ $rhoPersen }}%</span></div>
                                <div class="lp-gauge-teks"><strong>Kapasitas terpakai (ρ)</strong><span>{{ $label }}. Di atas 85% antrian mulai sulit dikendalikan.</span></div>
                            </div>
                        </section>

                        <section aria-label="Waktu tunggu">
                            <h3 class="lp-h3">Rata-rata waktu tunggu</h3>
                            <div class="lp-bar-row">
                                <div class="lp-bar-lab"><span>Hitungan teori (Erlang C)</span><strong>{{ $teori }} menit</strong></div>
                                <div class="lp-bar"><i class="t" style="width: {{ round($teori / $maks * 100) }}%"></i></div>
                            </div>
                            <div class="lp-bar-row">
                                <div class="lp-bar-lab"><span>Data riil</span><strong>{{ $aktual }} menit</strong></div>
                                <div class="lp-bar"><i class="a" style="width: {{ round($aktual / $maks * 100) }}%"></i></div>
                            </div>
                            @if ($item['persentase_error'] !== null)
                                <div class="lp-diff">
                                    Selisih {{ $item['selisih_menit'] }} menit ({{ $item['persentase_error'] }}%)
                                    @if ($item['persentase_error'] <= 20)
                                        <span class="lp-flag ok">Model sesuai</span>
                                    @else
                                        <span class="lp-flag warn">Selisih besar</span>
                                    @endif
                                </div>
                            @endif
                        </section>
                    </div>
                @endif

                @if ($item['ada_data'])
                    <details class="lp-detail" @if ($perlu->contains(fn ($h) => $h['poli']->id === $item['poli']->id)) open @endif>
                        <summary>Simulasi dan rekomendasi dokter</summary>
                        <div class="lp-detail-isi">
                            @include('partials.simulasi-dokter', ['item' => $item, 'target' => $target])
                        </div>
                    </details>
                @endif
            </article>
        @endforeach

        <p class="lp-note">
            Waktu tunggu teori dihitung dari λ dan μ rata-rata data historis dengan model M/M/c. Waktu tunggu riil diambil dari
            selisih waktu daftar dan waktu mulai dilayani setiap pasien. Selisih besar menandakan pola kedatangan atau lama
            layanan yang menyimpang dari asumsi Poisson dan eksponensial.
        </p>
    </div>
</x-app-layout>
