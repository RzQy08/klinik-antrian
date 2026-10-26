@props(['status'])

@php
    $peta = [
        'menunggu' => ['neutral', 'Menunggu'],
        'dipanggil' => ['warn', 'Dipanggil'],
        'dilayani' => ['info', 'Dilayani'],
        'selesai' => ['ok', 'Selesai'],
        'batal' => ['bad', 'Batal'],
        'tidak_hadir' => ['bad', 'Tidak hadir'],
    ];
    [$tone, $label] = $peta[$status] ?? ['neutral', ucfirst(str_replace('_', ' ', $status))];
@endphp

<x-ui.badge :tone="$tone" {{ $attributes }}>{{ $label }}</x-ui.badge>
