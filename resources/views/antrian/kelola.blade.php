<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('antrian.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar poli</a>
        <div class="d-flex align-items-center gap-3 mt-3">
            <span class="kode kode-lg">{{ $poli->kode_poli }}</span>
            <div>
                <h1 class="h2 mb-1">{{ $poli->nama_poli }}</h1>
                <p class="text-body-secondary mb-0">Panggil pasien, panggil ulang yang belum datang, dan selesaikan pelayanan.</p>
            </div>
        </div>
    </x-slot>

    @include('partials.flash')

    @unless ($layananBerjalan)
        <div class="alert alert-danger" role="alert"><i class="bi bi-clock-history me-1"></i> {{ \App\Services\JamOperasional::pesanLayananSelesai() }}</div>
    @endunless

    @php $berikutnya = $daftarMenunggu->first(); @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'people', 'label' => 'Menunggu', 'nilai' => $daftarMenunggu->count(), 'warna' => 'primary'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'megaphone', 'label' => 'Sedang ditangani', 'nilai' => $sedangDilayani->count(), 'warna' => 'warning'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'person-x', 'label' => 'Tidak hadir', 'nilai' => $tidakHadir->count(), 'warna' => 'danger'])</div>
        <div class="col-6 col-lg-3">@include('partials.stat', ['ikon' => 'person-badge', 'label' => 'Dokter bertugas', 'nilai' => $dokters->count(), 'warna' => 'success'])</div>
    </div>

    <div class="row g-4">

        {{-- Aksi utama petugas ditaruh paling atas di layar kecil --}}
        <div class="col-lg-4 order-first order-lg-last">
            <div class="card sticky-lg-top" style="top: 5.5rem">
                <div class="card-header"><h2 class="card-title-sm">Panggil berikutnya</h2></div>
                <div class="card-body">
                    @if (! $berikutnya)
                        <p class="text-body-secondary mb-0">Belum ada pasien yang menunggu.</p>
                    @elseif ($dokters->isEmpty())
                        <p class="mb-0">Belum ada dokter aktif di poli ini. Tambahkan atau aktifkan dokter lewat menu Dokter.</p>
                    @else
                        <div class="board board-pattern p-4 mb-3">
                            <div class="small board-muted">
                                Nomor berikutnya
                                @if ($berikutnya->limpahan)
                                    <span class="badge text-bg-warning ms-1">Limpahan {{ $berikutnya->nomor_lama }}</span>
                                @endif
                            </div>
                            <div class="numeral mt-1" style="font-size: 4.5rem">{{ $berikutnya->nomor_antrian }}</div>
                            <div class="small board-muted mt-2">{{ $berikutnya->pasien->nama }}</div>
                        </div>

                        <form method="POST" action="{{ route('antrian.panggil', $poli) }}" class="d-grid gap-3">
                            @csrf
                            <div>
                                <label for="dokter_id" class="form-label">Dokter yang bertugas</label>
                                <select name="dokter_id" id="dokter_id" required class="form-select">
                                    @foreach ($dokters as $dokter)
                                        <option value="{{ $dokter->id }}">{{ $dokter->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-warning btn-lg" @disabled(! $layananBerjalan)><i class="bi bi-megaphone-fill me-2"></i>Panggil {{ $berikutnya->nomor_antrian }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8 d-grid gap-4 align-content-start">

            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Sedang ditangani</h2></div>
                @if ($sedangDilayani->isEmpty())
                    <div class="card-body text-body-secondary">Belum ada pasien yang dipanggil.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($sedangDilayani as $antrian)
                            <li class="list-group-item p-3 p-md-4">
                                <div class="d-flex flex-wrap align-items-center gap-3">
                                    <span class="numeral fs-2 text-navy" style="min-width: 5.5rem">{{ $antrian->nomor_antrian }}</span>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium">{{ $antrian->pasien->nama }}</div>
                                        <div class="small text-body-secondary">{{ $antrian->dokter?->nama ?? '-' }}@if ($antrian->jumlah_panggil > 1), panggilan ke-{{ $antrian->jumlah_panggil }}@endif</div>
                                    </div>
                                    @include('partials.status', ['status' => $antrian->status])
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    @if ($antrian->status === 'dipanggil')
                                        <form method="POST" action="{{ route('antrian.mulai-layani', $antrian) }}">@csrf<button type="submit" class="btn btn-primary btn-sm">Mulai layani</button></form>
                                        <form method="POST" action="{{ route('antrian.panggil-ulang', $antrian) }}">@csrf<button type="submit" class="btn btn-outline-secondary btn-sm" @disabled(! $layananBerjalan)><i class="bi bi-arrow-repeat me-1"></i>Panggil ulang</button></form>
                                        <form method="POST" action="{{ route('antrian.tidak-hadir', $antrian) }}">@csrf<button type="submit" class="btn btn-outline-danger btn-sm">Tidak hadir</button></form>
                                    @elseif ($antrian->status === 'dilayani')
                                        <form method="POST" action="{{ route('antrian.selesai', $antrian) }}">@csrf<button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i>Selesai</button></form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Tidak hadir ({{ $tidakHadir->count() }})</h2></div>
                @if ($tidakHadir->isEmpty())
                    <div class="card-body text-body-secondary small">Tidak ada pasien yang terlewat. Pasien yang tidak hadir saat dipanggil muncul di sini dan bisa dipanggil ulang selama jam layanan.</div>
                @else
                    <ul class="list-group list-group-flush">
                        @foreach ($tidakHadir as $antrian)
                            <li class="list-group-item d-flex flex-wrap align-items-center gap-3 px-3 px-md-4 py-3">
                                <span class="numeral fs-3 text-body-secondary" style="min-width: 5.5rem">{{ $antrian->nomor_antrian }}</span>
                                <div class="flex-grow-1">
                                    <div class="fw-medium">{{ $antrian->pasien->nama }}</div>
                                    <div class="small text-body-secondary">Dipanggil {{ $antrian->jumlah_panggil }} kali, terakhir pukul {{ $antrian->waktu_dipanggil?->format('H.i') }}</div>
                                </div>
                                <form method="POST" action="{{ route('antrian.panggil-ulang', $antrian) }}">@csrf<button type="submit" class="btn btn-outline-secondary btn-sm" @disabled(! $layananBerjalan)><i class="bi bi-arrow-repeat me-1"></i>Panggil ulang</button></form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Menunggu ({{ $daftarMenunggu->count() }})</h2></div>
                @if ($daftarMenunggu->isEmpty())
                    <div class="card-body text-body-secondary">Tidak ada pasien yang menunggu.</div>
                @else
                    <ol class="list-group list-group-flush">
                        @foreach ($daftarMenunggu as $antrian)
                            <li class="list-group-item d-flex flex-wrap align-items-center gap-3 px-3 px-md-4 py-3">
                                <span class="numeral fs-3 text-body-secondary" style="min-width: 5.5rem">{{ $antrian->nomor_antrian }}</span>
                                <span class="flex-grow-1">
                                    {{ $antrian->pasien->nama }}
                                    @if ($antrian->limpahan)
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-1">Limpahan {{ $antrian->nomor_lama }}</span>
                                    @endif
                                </span>
                                <span class="small text-body-secondary">
                                    @if ($antrian->limpahan)
                                        dari {{ $antrian->tanggal_asal->locale('id')->translatedFormat('j M') }}
                                    @else
                                        menunggu {{ $antrian->waktu_daftar->locale('id')->diffForHumans(null, true) }}
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
