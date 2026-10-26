@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button'])

@php
    $varian = ['primary' => 'btn-primary', 'accent' => 'btn-accent', 'secondary' => 'btn-outline', 'danger' => 'btn-danger'];
    $ukuran = ['sm' => 'btn-sm', 'md' => '', 'lg' => 'btn-lg'];
    $kelas = trim('btn '.($varian[$variant] ?? 'btn-primary').' '.($ukuran[$size] ?? ''));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $kelas]) }}>{{ $slot }}</button>
@endif
