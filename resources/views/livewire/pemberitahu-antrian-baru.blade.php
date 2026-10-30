<div wire:poll.5s.visible>
    @if ($berubah)
        <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2" role="status">
            <span>
                <i class="bi bi-bell-fill me-1"></i>
                @if ($selisih > 0)
                    Ada {{ $selisih }} pasien baru mendaftar. Sekarang {{ $sekarang }} pasien menunggu.
                @else
                    Daftar antrian berubah. Sekarang {{ $sekarang }} pasien menunggu.
                @endif
            </span>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-primary">Muat ulang</a>
        </div>
    @endif
</div>
