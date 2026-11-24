<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0B7A8F">
<title>{{ config('rumahsakit.nama', 'RS Bersama') }}</title>

@if ($js ?? true)
    @vite(['resources/scss/rs.scss', 'resources/js/rs.js'])
@else
    @vite(['resources/scss/rs.scss'])
@endif

@php
    // Muat font ikon lebih awal supaya ikon tidak muncul belakangan setelah halaman tampil
    try { $fontIkon = \Illuminate\Support\Facades\Vite::asset('node_modules/bootstrap-icons/font/fonts/bootstrap-icons.woff2'); } catch (\Throwable) { $fontIkon = null; }
@endphp
@if ($fontIkon)
    <link rel="preload" href="{{ $fontIkon }}" as="font" type="font/woff2" crossorigin>
@endif
