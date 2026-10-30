<div @if ($masihBerjalan) wire:poll.10s.visible @endif>
    @if ($antrian->status === 'dipanggil')
        <div class="alert alert-warning text-center fs-5" role="alert">
            <strong>Giliran Anda!</strong> Silakan menuju {{ $antrian->poli->nama_poli }}@if ($antrian->dokter), {{ $antrian->dokter->nama }}@endif.
        </div>
    @elseif ($antrian->status === 'dilayani')
        <div class="alert alert-info text-center" role="alert">Anda sedang dilayani.</div>
    @elseif ($antrian->status === 'selesai')
        <div class="alert alert-success text-center" role="alert">Pelayanan selesai. Terima kasih.</div>
    @elseif ($antrian->status === 'tidak_hadir')
        <div class="alert alert-danger text-center" role="alert">Anda ditandai tidak hadir. Silakan hubungi petugas loket.</div>
    @endif

    <article class="card overflow-hidden">
        <div class="board board-pattern text-center px-4 pt-4 pb-5 rounded-0">
            <div class="d-flex justify-content-center mb-4">@include('partials.brand', ['terang' => true, 'sub' => false])</div>
            <div class="small board-muted">Tiket antrian {{ $antrian->poli->nama_poli }}</div>
            <div class="numeral mt-2" style="font-size: 6.5rem">{{ $antrian->nomor_antrian }}</div>
            <div class="small board-muted mt-3">Simpan nomor ini sampai dipanggil</div>
        </div>

        <div class="ticket-cut" aria-hidden="true"><span class="hole hole-l"></span><span class="hole hole-r"></span></div>

        <ul class="list-group list-group-flush">
            @if ($antrian->status === 'menunggu')
                <li class="list-group-item d-flex justify-content-between align-items-baseline px-4 py-3">
                    <span class="text-body-secondary">Perkiraan tunggu</span>
                    <span class="fs-5 fw-bold display-font">
                        @if ($antrian->estimasi_tunggu_detik !== null)
                            &plusmn; {{ max(1, round($antrian->estimasi_tunggu_detik / 60)) }} menit
                        @else
                            Belum tersedia
                        @endif
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between px-4 py-3"><span class="text-body-secondary">Di depan Anda</span><span class="fw-semibold">{{ $posisiDidepan }} orang</span></li>
            @endif
            <li class="list-group-item d-flex justify-content-between px-4 py-3"><span class="text-body-secondary">Atas nama</span><span class="fw-semibold">{{ $antrian->pasien->nama }}</span></li>
            <li class="list-group-item d-flex justify-content-between px-4 py-3"><span class="text-body-secondary">Terdaftar pukul</span><span class="fw-semibold">{{ $antrian->waktu_daftar->format('H:i') }}</span></li>
        </ul>
    </article>

    @if ($antrian->status === 'menunggu' && $antrian->estimasi_tunggu_detik === null)
        <p class="small text-body-secondary mt-3">Perkiraan belum bisa dihitung karena poli sedang sangat padat. Petugas tetap memanggil sesuai urutan nomor.</p>
    @endif

    @if ($masihBerjalan)
        <p class="small text-center text-body-secondary mt-3 mb-0">Halaman ini memperbarui diri otomatis.</p>
    @endif
</div>
