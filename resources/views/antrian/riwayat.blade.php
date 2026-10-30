<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1">Riwayat antrian</h1>
                <p class="text-body-secondary mb-0">{{ $filter['dari']->locale('id')->translatedFormat('j M Y') }} sampai {{ $filter['sampai']->locale('id')->translatedFormat('j M Y') }}</p>
            </div>
            <a href="{{ route('antrian.riwayat.csv', request()->query()) }}" class="btn btn-outline-primary"><i class="bi bi-download me-1"></i>Unduh CSV</a>
        </div>
    </x-slot>

    @include('partials.errors')

    <form method="GET" action="{{ route('antrian.riwayat') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="dari" class="form-label">Dari</label>
                    <input type="date" id="dari" name="dari" value="{{ $filter['dari']->toDateString() }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="sampai" class="form-label">Sampai</label>
                    <input type="date" id="sampai" name="sampai" value="{{ $filter['sampai']->toDateString() }}" class="form-control">
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="poli_id" class="form-label">Poli</label>
                    <select id="poli_id" name="poli_id" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($polis as $poli)
                            <option value="{{ $poli->id }}" @selected($filter['poli_id'] === $poli->id)>{{ $poli->nama_poli }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">Semua</option>
                        @foreach ($daftarStatus as $status)
                            <option value="{{ $status }}" @selected($filter['status'] === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2">
                    <label for="q" class="form-label">Cari</label>
                    <input type="search" id="q" name="q" value="{{ $filter['q'] }}" maxlength="50" placeholder="Nama, nomor, NIK" class="form-control">
                </div>
                <div class="col-12 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'list-ul', 'label' => 'Total antrian', 'nilai' => $total, 'warna' => 'primary'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'check-circle', 'label' => 'Selesai', 'nilai' => $ringkasan['selesai'] ?? 0, 'warna' => 'success'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'person-x', 'label' => 'Tidak hadir', 'nilai' => $ringkasan['tidak_hadir'] ?? 0, 'warna' => 'danger'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'hourglass-split', 'label' => 'Belum selesai', 'nilai' => ($ringkasan['menunggu'] ?? 0) + ($ringkasan['dipanggil'] ?? 0) + ($ringkasan['dilayani'] ?? 0), 'warna' => 'warning'])</div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th><th>Nomor</th><th>Poli</th><th>Pasien</th><th>Dokter</th><th>Status</th>
                        <th class="text-end">Tunggu</th><th class="text-end">Layanan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($data as $a)
                        <tr>
                            <td class="text-nowrap">{{ $a->tanggal->locale('id')->translatedFormat('j M Y') }}<div class="small text-body-secondary">{{ $a->waktu_daftar?->format('H:i') }}</div></td>
                            <td class="fw-semibold">{{ $a->nomor_antrian }}@if ($a->limpahan)<span class="badge text-bg-warning ms-1">Limpahan</span>@endif</td>
                            <td>{{ $a->poli?->nama_poli }}</td>
                            <td>{{ $a->pasien?->nama }}<div class="small text-body-secondary">{{ \App\Http\Controllers\RiwayatController::samarkanNik($a->pasien?->nik) }}</div></td>
                            <td>{{ $a->dokter?->nama ?? '-' }}</td>
                            <td>@include('partials.status', ['status' => $a->status])</td>
                            <td class="text-end text-nowrap">{{ $a->lama_tunggu_detik !== null ? round($a->lama_tunggu_detik / 60).' mnt' : '-' }}</td>
                            <td class="text-end text-nowrap">{{ $a->lama_layanan_detik !== null ? round($a->lama_layanan_detik / 60).' mnt' : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary py-5">Tidak ada antrian pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $data->links('pagination::bootstrap-5') }}</div>

    <p class="small text-body-secondary mt-3" style="max-width: 44rem">NIK ditampilkan sebagian demi privasi pasien. Berkas CSV memakai pemisah titik koma dan bisa langsung dibuka di Excel.</p>
</x-app-layout>
