<x-public-layout :title="'Tiket '.$antrian->nomor_antrian">
    @include('partials.flash')

    <div class="mx-auto" style="max-width: 24rem">
        <article class="card overflow-hidden">
            <div class="board board-pattern text-center px-4 pt-4 pb-5 rounded-0">
                <div class="d-flex justify-content-center mb-4">@include('partials.brand', ['terang' => true, 'sub' => false])</div>
                <div class="small board-muted">Tiket antrian {{ $antrian->poli->nama_poli }}</div>
                <div class="numeral mt-2" style="font-size: 6.5rem">{{ $antrian->nomor_antrian }}</div>
                <div class="small board-muted mt-3">Simpan nomor ini sampai dipanggil</div>
            </div>

            <div class="ticket-cut" aria-hidden="true"><span class="hole hole-l"></span><span class="hole hole-r"></span></div>

            <ul class="list-group list-group-flush">
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
                <li class="list-group-item d-flex justify-content-between px-4 py-3"><span class="text-body-secondary">Atas nama</span><span class="fw-semibold">{{ $antrian->pasien->nama }}</span></li>
                <li class="list-group-item d-flex justify-content-between px-4 py-3"><span class="text-body-secondary">Terdaftar pukul</span><span class="fw-semibold">{{ $antrian->waktu_daftar->format('H:i') }}</span></li>
            </ul>
        </article>

        @if ($antrian->estimasi_tunggu_detik === null)
            <p class="small text-body-secondary mt-3">Perkiraan belum bisa dihitung karena poli sedang sangat padat. Petugas tetap memanggil sesuai urutan nomor.</p>
        @endif

        <a href="{{ route('antrian.display', $antrian->poli_id) }}" class="btn btn-outline-primary w-100 mt-4"><i class="bi bi-tv me-2"></i>Lihat layar antrian</a>
        <p class="small text-center text-body-secondary mt-3 mb-0">Halaman ini memperbarui diri setiap 15 detik.</p>
    </div>

    <script>setTimeout(function () { window.location.reload(); }, 15000);</script>
</x-public-layout>
