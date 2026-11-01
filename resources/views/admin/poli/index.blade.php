<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Dashboard admin</a>
                <h1 class="h2 mt-2 mb-1">Kelola poli</h1>
                <p class="text-body-secondary mb-0">Atur poli yang bisa dipilih pasien. Kode poli menjadi awalan nomor antrian, misalnya A001.</p>
            </div>
        </div>
    </x-slot>

    @include('partials.flash')

    <livewire:admin.poli-manager />
</x-app-layout>
