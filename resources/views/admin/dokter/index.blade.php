<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dashboard') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Dashboard admin</a>
        <h1 class="h2 mt-2 mb-1">Kelola dokter</h1>
        <p class="text-body-secondary mb-0">Atur dokter, jadwal praktik, dan akun login. Daftar dibagi per halaman supaya tetap cepat.</p>
    </x-slot>

    @include('partials.flash')

    <livewire:admin.dokter-manager />
</x-app-layout>
