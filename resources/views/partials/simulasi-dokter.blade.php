{{-- Rekomendasi dan simulasi jumlah dokter untuk satu poli. Variabel: $item, $target --}}
<hr class="my-4">
<h3 class="h6 mb-2">Rekomendasi jumlah dokter</h3>

@if ($item['rekomendasi_dokter'] === null)
    <p class="mb-3">Dengan beban saat ini, sampai 10 dokter pun belum memenuhi target tunggu {{ $target }} menit. Perlu peninjauan jam layanan atau lama layanan per pasien.</p>
@elseif ($item['rekomendasi_dokter'] > $item['jumlah_server'])
    <p class="mb-3">
        Untuk rata-rata tunggu di bawah <strong>{{ $target }} menit</strong> dan kapasitas terpakai di bawah 85%,
        poli ini memerlukan <strong>{{ $item['rekomendasi_dokter'] }} dokter</strong>
        (saat ini {{ $item['jumlah_server'] }}, tambah {{ $item['rekomendasi_dokter'] - $item['jumlah_server'] }}).
    </p>
@elseif ($item['rekomendasi_dokter'] === $item['jumlah_server'])
    <p class="mb-3">Jumlah dokter saat ini ({{ $item['jumlah_server'] }}) sudah tepat untuk target tunggu {{ $target }} menit dan kapasitas terpakai di bawah 85%.</p>
@else
    <p class="mb-3">
        Target tunggu {{ $target }} menit sebenarnya sudah tercapai dengan <strong>{{ $item['rekomendasi_dokter'] }} dokter</strong>
        (saat ini {{ $item['jumlah_server'] }}). Kelebihan dokter berarti sebagian waktu mereka menganggur.
    </p>
@endif

<div class="table-responsive">
    <table class="table table-sm align-middle mb-2">
        <thead>
            <tr>
                <th scope="col">Dokter</th>
                <th scope="col" class="text-end">Kapasitas terpakai (ρ)</th>
                <th scope="col" class="text-end">Peluang harus menunggu</th>
                <th scope="col" class="text-end">Rata-rata tunggu (Wq)</th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($item['simulasi'] as $baris)
                <tr @class(['table-active' => $baris['c'] === $item['jumlah_server']])>
                    <td class="fw-semibold">{{ $baris['c'] }}</td>
                    <td class="text-end">{{ round($baris['rho'] * 100) }}%</td>
                    @if ($baris['stabil'])
                        <td class="text-end">{{ round($baris['peluang_menunggu'] * 100) }}%</td>
                        <td class="text-end">{{ $baris['wq_menit'] }} menit</td>
                    @else
                        <td class="text-end text-body-secondary">-</td>
                        <td class="text-end text-danger">tidak stabil</td>
                    @endif
                    <td class="small">
                        @if ($baris['c'] === $item['jumlah_server'])<span class="badge rounded-pill text-bg-secondary">Saat ini</span>@endif
                        @if ($baris['c'] === $item['rekomendasi_dokter'])<span class="badge rounded-pill text-bg-success">Rekomendasi</span>@endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="small text-body-secondary mb-0">Simulasi mengandaikan dokter tambahan melayani secepat rata-rata dokter sekarang (μ sama) dan jumlah pasien datang tidak berubah.</p>
