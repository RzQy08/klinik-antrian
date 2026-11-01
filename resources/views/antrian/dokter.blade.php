<x-app-layout>
    @php
        $jam = (int) now()->format('G');
        $sapaan = $jam < 11 ? 'pagi' : ($jam < 15 ? 'siang' : ($jam < 18 ? 'sore' : 'malam'));
    @endphp

    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <p class="ad-date mb-1"><i class="bi bi-calendar3 me-2"></i>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</p>
                <h1 class="h2 mb-1">Selamat {{ $sapaan }}, {{ $dokter->nama }}</h1>
                <p class="text-body-secondary mb-0"><i class="bi bi-hospital me-1"></i>{{ $dokter->poli?->nama_poli }}</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if (\App\Services\JamOperasional::aktif() && ! \App\Services\JamOperasional::layananBerjalan())
                    <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Jam layanan sudah selesai</span>
                @endif
                @unless ($dokter->aktif)
                    <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Status Anda nonaktif</span>
                @endunless
            </div>
        </div>
    </x-slot>

    @include('partials.flash')

    <livewire:panel-dokter />
</x-app-layout>
