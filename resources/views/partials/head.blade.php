<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0B7A8F">
<title>{{ ($title ?? null) ? $title.' - ' : '' }}{{ config('rumahsakit.nama', 'RS Bersama') }}</title>

@if ($js ?? true)
    @vite(['resources/scss/rs.scss', 'resources/js/rs.js'])
@else
    @vite(['resources/scss/rs.scss'])
@endif
