<x-public-layout :bare="true" :title="'Tiket '.$antrian->nomor_antrian">
    <div class="tk-shell">
        <header class="tk-top">
            <a href="{{ route('beranda') }}" class="tk-back"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Beranda</span></a>
            <span class="tk-rs">{{ config('rumahsakit.nama') }}</span>
        </header>

        <div class="tk-wrap">
            @if (session('success') || session('error'))
                <div class="tk-flash {{ session('error') ? 'is-error' : '' }}" role="status">
                    <i class="bi bi-{{ session('error') ? 'exclamation-triangle-fill' : 'check-circle-fill' }}" aria-hidden="true"></i>{{ session('error') ?? session('success') }}
                </div>
            @endif

            <livewire:tiket-antrian :antrian-id="$antrian->id" />
        </div>
    </div>
</x-public-layout>
