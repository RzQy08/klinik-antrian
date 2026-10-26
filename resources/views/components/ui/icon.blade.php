@props(['name'])

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('users')
            <circle cx="9" cy="8" r="3.2"/><path d="M3 19c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17" cy="9" r="2.4"/><path d="M16.5 14c2.8 0 4.5 1.7 4.5 4.2"/>
            @break
        @case('user')
            <circle cx="12" cy="8" r="3.6"/><path d="M5 20c0-3.7 3.1-6 7-6s7 2.3 7 6"/>
            @break
        @case('megaphone')
            <path d="M4 10v4h3l5 4V6L7 10H4z"/><path d="M16 9.5a3.5 3.5 0 010 5"/><path d="M18.5 7a7 7 0 010 10"/>
            @break
        @case('check')
            <circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.5"/>
            @break
        @case('clock')
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
            @break
        @case('building')
            <path d="M4 20V9l8-5 8 5v11"/><path d="M3 20h18"/><path d="M12 10v5M9.5 12.5h5"/>
            @break
        @case('chart')
            <path d="M5 20v-6M11 20V5M17 20v-9M3 20h18"/>
            @break
        @case('calendar')
            <rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>
            @break
        @case('home')
            <path d="M4 11l8-7 8 7"/><path d="M6 10v10h12V10"/>
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break
    @endswitch
</svg>
