<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['js' => false])
    @livewireStyles
</head>
<body class="pa-body">
    {{-- Papan seluruh poli untuk TV ruang tunggu utama --}}
    <livewire:antrian-board />

    @livewireScripts
    @include('antrian._layar-js')
</body>
</html>
