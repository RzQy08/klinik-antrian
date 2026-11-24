@php
    $warna = fn ($kode) => 'lp-c'.(ord(strtoupper(substr((string) $kode, 0, 1))) % 6);
    $tanpa = fn (array $kunci) => request()->except(array_merge($kunci, ['page']));
    $hariIni = now()->toDateString();
    $rentang = ['Hari ini' => 0, '7 hari' => 6, '30 hari' => 29, '90 hari' => 90];
    $adaFilter = $filter['poli_id'] || $filter['status'] || $filter['q'];
    $poliTerpilih = $filter['poli_id'] ? $polis->firstWhere('id', $filter['poli_id']) : null;
    $belum = ($ringkasan['menunggu'] ?? 0) + ($ringkasan['dipanggil'] ?? 0) + ($ringkasan['telat'] ?? 0) + ($ringkasan['dilayani'] ?? 0);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="lp-head">
            <div>
                <h1 class="lp-title">Riwayat antrian</h1>
                <p class="lp-sub">{{ $filter['dari']->locale('id')->translatedFormat('j M Y') }} sampai {{ $filter['sampai']->locale('id')->translatedFormat('j M Y') }}</p>
            </div>
            <a href="{{ route('antrian.riwayat.csv', request()->query()) }}" class="btn btn-primary"><i class="bi bi-download me-2" aria-hidden="true"></i>Unduh CSV</a>
        </div>
    </x-slot>

    <div class="lp-page">
        @include('partials.errors')

        {{-- Pintasan rentang --}}
        <div class="lp-quick">
            <span class="lp-lab" id="lab-rentang">Rentang cepat</span>
            <div class="lp-seg" role="group" aria-labelledby="lab-rentang">
                @foreach ($rentang as $nama => $mundur)
                    @php $aktif = $filter['sampai']->toDateString() === $hariIni && $filter['dari']->toDateString() === now()->subDays($mundur)->toDateString(); @endphp
                    <a href="{{ route('antrian.riwayat', $tanpa(['dari', 'sampai']) + ['dari' => now()->subDays($mundur)->toDateString(), 'sampai' => $hariIni]) }}" class="{{ $aktif ? 'is-on' : '' }}" @if ($aktif) aria-current="true" @endif>{{ $nama }}</a>
                @endforeach
            </div>
        </div>

        {{-- Pencarian dan filter --}}
        <form method="GET" action="{{ route('antrian.riwayat') }}" class="lp-filter" id="formRiwayat">
            <div class="lp-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label for="q" class="visually-hidden">Cari antrian</label>
                <input type="search" id="q" name="q" value="{{ $filter['q'] }}" maxlength="50" placeholder="Cari nama, nomor antrian, NIK, atau no HP" autocomplete="off" enterkeyhint="search">
                <span class="lp-search-spin" aria-hidden="true"></span>
            </div>
            <button class="btn btn-outline-secondary lp-filter-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#panelFilter" aria-expanded="false" aria-controls="panelFilter">
                <span><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter</span><i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="collapse d-md-block mt-3 mt-md-0" id="panelFilter">
                <div class="row g-3 align-items-end">
                    <div class="col-6 col-lg-2"><label for="dari" class="form-label">Dari</label><input type="date" id="dari" name="dari" value="{{ $filter['dari']->toDateString() }}" class="form-control"></div>
                    <div class="col-6 col-lg-2"><label for="sampai" class="form-label">Sampai</label><input type="date" id="sampai" name="sampai" value="{{ $filter['sampai']->toDateString() }}" class="form-control"></div>
                    <div class="col-6 col-lg-2">
                        <label for="poli_id" class="form-label">Poli</label>
                        <select id="poli_id" name="poli_id" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($polis as $poli)
                                <option value="{{ $poli->id }}" @selected($filter['poli_id'] === $poli->id)>{{ $poli->nama_poli }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-lg-2">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua</option>
                            @foreach ($daftarStatus as $status)
                                <option value="{{ $status }}" @selected($filter['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-lg-2 d-grid"><button type="submit" class="btn btn-primary" style="min-height: 2.9rem">Terapkan</button></div>
                </div>
            </div>
        </form>

        <div id="hasilRiwayat">
        @if ($adaFilter)
            <ul class="lp-chips" aria-label="Filter aktif">
                @if ($poliTerpilih)<li class="lp-chip">Poli: {{ $poliTerpilih->nama_poli }}<a href="{{ route('antrian.riwayat', $tanpa(['poli_id'])) }}" aria-label="Hapus filter poli"><i class="bi bi-x-lg" aria-hidden="true"></i></a></li>@endif
                @if ($filter['status'])<li class="lp-chip">Status: {{ ucfirst(str_replace('_', ' ', $filter['status'])) }}<a href="{{ route('antrian.riwayat', $tanpa(['status'])) }}" aria-label="Hapus filter status"><i class="bi bi-x-lg" aria-hidden="true"></i></a></li>@endif
                @if ($filter['q'])<li class="lp-chip">Cari: {{ $filter['q'] }}<a href="{{ route('antrian.riwayat', $tanpa(['q'])) }}" aria-label="Hapus pencarian"><i class="bi bi-x-lg" aria-hidden="true"></i></a></li>@endif
            </ul>
        @endif

        {{-- Ringkasan --}}
        <div class="lp-stats mb-3" aria-label="Ringkasan hasil">
            <div class="lp-stat lp-c0"><span class="lp-stat-ico"><i class="bi bi-list-ul"></i></span><div><div class="lp-stat-n">{{ $total }}</div><div class="lp-stat-l">Total antrian</div></div></div>
            <div class="lp-stat lp-c5"><span class="lp-stat-ico"><i class="bi bi-check-circle"></i></span><div><div class="lp-stat-n">{{ $ringkasan['selesai'] ?? 0 }}</div><div class="lp-stat-l">Selesai</div></div></div>
            <div class="lp-stat lp-c2"><span class="lp-stat-ico"><i class="bi bi-person-x"></i></span><div><div class="lp-stat-n">{{ $ringkasan['tidak_hadir'] ?? 0 }}</div><div class="lp-stat-l">Tidak hadir</div></div></div>
            <div class="lp-stat lp-c1"><span class="lp-stat-ico"><i class="bi bi-hourglass-split"></i></span><div><div class="lp-stat-n">{{ $belum }}</div><div class="lp-stat-l">Belum selesai</div></div></div>
        </div>

        {{-- Tabel --}}
        <div class="lp-tabel">
            <div class="lp-scroll" tabindex="0" role="region" aria-label="Tabel riwayat antrian">
                <table class="table lp-table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Tanggal</th><th scope="col">Nomor</th><th scope="col">Poli</th><th scope="col">Pasien</th>
                            <th scope="col">Dokter</th><th scope="col">Status</th><th scope="col" class="text-end">Tunggu</th><th scope="col" class="text-end">Layanan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $a)
                            @php
                                $tunggu = $a->lama_tunggu_detik !== null ? (int) round($a->lama_tunggu_detik / 60) : null;
                                $layanan = $a->lama_layanan_detik !== null ? (int) round($a->lama_layanan_detik / 60) : null;
                            @endphp
                            <tr>
                                <td data-label="Tanggal" class="lp-td-tgl text-nowrap">{{ $a->tanggal->translatedFormat('j M Y') }}<div class="small text-body-secondary">{{ $a->waktu_daftar?->format('H:i') }}</div></td>
                                <td data-label="Nomor" class="lp-td-no text-nowrap"><span class="lp-no">{{ $a->nomor_antrian }}</span>@if ($a->limpahan)<span class="lp-lim">Limpahan</span>@endif</td>
                                <td data-label="Poli" class="lp-td-poli"><span class="lp-poli {{ $warna($a->poli?->kode_poli) }}"><span class="lp-kode" aria-hidden="true">{{ $a->poli?->kode_poli }}</span>{{ $a->poli?->nama_poli }}</span></td>
                                <td data-label="Pasien" class="lp-td-pasien">{{ $a->pasien?->nama }}<div class="small text-body-secondary">{{ $a->pasien?->no_rm }} &middot; HP {{ $a->pasien?->hp_sensor }}</div></td>
                                <td data-label="Dokter" class="lp-td-dokter">{{ $a->dokter?->nama ?? '-' }}</td>
                                <td data-label="Status" class="lp-td-status">@include('partials.status', ['status' => $a->status])</td>
                                <td data-label="Tunggu" class="lp-td-tunggu text-end text-nowrap">@if ($tunggu !== null)<span class="lp-dur {{ $tunggu > 30 ? 'bad' : ($tunggu > 15 ? 'warn' : '') }}">{{ $tunggu }} mnt</span>@else - @endif</td>
                                <td data-label="Layanan" class="lp-td-layanan text-end text-nowrap">@if ($layanan !== null)<span class="lp-dur">{{ $layanan }} mnt</span>@else - @endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="p-0">
                                <div class="lp-kosong">
                                    <i class="bi bi-search" aria-hidden="true"></i>
                                    <strong>Tidak ada antrian pada filter ini</strong>
                                    <span>Coba perlebar rentang tanggal atau hapus filter.</span>
                                    @if ($adaFilter)<a href="{{ route('antrian.riwayat', request()->only(['dari', 'sampai'])) }}" class="btn btn-outline-primary btn-sm">Hapus semua filter</a>@endif
                                </div>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lp-pager">
            <span class="lp-pager-info">Halaman {{ $data->currentPage() }}, {{ $data->count() }} data ditampilkan.</span>
            {{ $data->links('pagination::simple-bootstrap-5') }}
        </div>
        </div>
    </div>

    <script>
    (function () {
        // Pencarian langsung: hasil diganti di tempat sambil mengetik, tanpa memuat ulang halaman
        var form = document.getElementById('formRiwayat'), q = document.getElementById('q'), hasil = document.getElementById('hasilRiwayat');
        if (!form || !q || !hasil || !window.fetch) return;
        var tunda = null, batal = null;

        function cari() {
            var p = new URLSearchParams();
            new FormData(form).forEach(function (v, k) { if (String(v).trim() !== '') p.append(k, v); });
            var url = form.action + (p.toString() ? '?' + p.toString() : '');
            if (batal) batal.abort();
            batal = new AbortController();
            form.classList.add('is-memuat');
            fetch(url, { signal: batal.signal, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) {
                    var baru = new DOMParser().parseFromString(html, 'text/html').getElementById('hasilRiwayat');
                    if (baru) { hasil.innerHTML = baru.innerHTML; history.replaceState(null, '', url); }
                    form.classList.remove('is-memuat');
                })
                .catch(function (e) { if (e.name !== 'AbortError') form.classList.remove('is-memuat'); });
        }

        q.addEventListener('input', function () { clearTimeout(tunda); tunda = setTimeout(cari, 280); });
        form.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(tunda); cari(); });
    })();
    </script>
</x-app-layout>
