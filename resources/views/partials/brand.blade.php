@php
    $terang = $terang ?? false;
    $sub = $sub ?? true;
@endphp
<span class="brand d-inline-flex align-items-center gap-2 {{ $terang ? 'brand-light' : '' }}">
    <span class="brand-mark {{ $terang ? 'brand-mark-light' : '' }}" aria-hidden="true"></span>
    <span class="lh-1">
        <span class="brand-name d-block">{{ config('rumahsakit.nama', 'RS Bersama') }}</span>
        @if ($sub)
            <span class="brand-sub d-block mt-1">{{ config('rumahsakit.sub', 'Sistem Antrian Poliklinik') }}</span>
        @endif
    </span>
</span>
