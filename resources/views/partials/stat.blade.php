<div class="card h-100">
    <div class="card-body d-flex align-items-center gap-3">
        <span class="icon-chip icon-chip-{{ $warna ?? 'primary' }}"><i class="bi bi-{{ $ikon }}"></i></span>
        <div>
            <div class="numeral fs-1 text-navy">{{ $nilai }}</div>
            <div class="small text-body-secondary mt-1">{{ $label }}</div>
        </div>
    </div>
</div>
