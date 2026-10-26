<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Layar antrian '.$poli->nama_poli, 'js' => false])
    @livewireStyles
</head>
<body class="tv-body">
    {{-- Layar penuh untuk TV ruang tunggu: hanya CSS, tanpa Bootstrap JS (Livewire membawa Alpine sendiri) --}}
    <livewire:antrian-display :poli-id="$poli->id" />

    @livewireScripts
</body>
</html>
