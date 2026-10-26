@props(['light' => false, 'sub' => true])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <span @class(['mark', 'mark-onnavy' => $light]) aria-hidden="true"></span>
    <span class="leading-tight">
        <span class="display block text-lg font-extrabold">{{ config('rumahsakit.nama', 'RS Bersama') }}</span>
        @if ($sub)
            <span @class(['block text-xs', 'brand-sub' => ! $light, 'board-muted' => $light])>{{ config('rumahsakit.sub', 'Sistem Antrian Poliklinik') }}</span>
        @endif
    </span>
</span>
