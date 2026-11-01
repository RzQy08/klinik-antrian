<div wire:poll.5s.visible>
    @if ($berubah)
        <div class="pt-notice" role="status">
            <span>
                <i class="bi bi-bell-fill me-2" aria-hidden="true"></i>
                @if ($selisih > 0)
                    Ada {{ $selisih }} pasien baru mendaftar. Sekarang {{ $sekarang }} pasien menunggu.
                @else
                    Daftar antrian berubah. Sekarang {{ $sekarang }} pasien menunggu.
                @endif
            </span>
            <a href="{{ url()->current() }}" class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Muat ulang</a>
        </div>
    @endif
</div>
