<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Papan antrian', 'js' => false])
    @livewireStyles
</head>
<body class="tv-body">
    {{-- Papan seluruh poli untuk TV ruang tunggu utama --}}
    <livewire:antrian-board />

    @livewireScripts
</body>
</html>
