<x-app-layout>
    <x-slot name="header">
        <h1 class="h2 mt-2 mb-1">Jadwal</h1>
        <p class="text-body-secondary mb-0">Atur jam buka rumah sakit dan hari libur, serta lihat dan atur jadwal praktik seluruh dokter.</p>
    </x-slot>

    @include('partials.flash')

    <div class="jp-tabs" role="tablist" aria-label="Jenis jadwal">
        <a href="{{ route('admin.penjadwalan', 'rumah-sakit') }}" role="tab" aria-selected="{{ $tab === 'rumah-sakit' ? 'true' : 'false' }}" class="{{ $tab === 'rumah-sakit' ? 'is-on' : '' }}"><i class="bi bi-hospital"></i>Rumah sakit</a>
        <a href="{{ route('admin.penjadwalan', 'dokter') }}" role="tab" aria-selected="{{ $tab === 'dokter' ? 'true' : 'false' }}" class="{{ $tab === 'dokter' ? 'is-on' : '' }}"><i class="bi bi-person-badge"></i>Dokter</a>
    </div>

    @if ($tab === 'dokter')
        <livewire:admin.jadwal-dokter-manager />
    @else
        <livewire:admin.jadwal-rumah-sakit-manager />
    @endif
</x-app-layout>
