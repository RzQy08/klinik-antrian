@php
    $peta = [
        'menunggu' => ['secondary', 'Menunggu'],
        'dipanggil' => ['warning', 'Dipanggil'],
        'dilayani' => ['info', 'Dilayani'],
        'selesai' => ['success', 'Selesai'],
        'batal' => ['danger', 'Batal'],
        'tidak_hadir' => ['danger', 'Tidak hadir'],
    ];
    [$warnaStatus, $labelStatus] = $peta[$status] ?? ['secondary', ucfirst(str_replace('_', ' ', $status))];
@endphp
<span class="badge rounded-pill bg-{{ $warnaStatus }}-subtle text-{{ $warnaStatus }}-emphasis">{{ $labelStatus }}</span>
