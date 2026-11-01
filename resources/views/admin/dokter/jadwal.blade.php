<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-2 mb-1">Jadwal praktik</h1>
        <p class="text-body-secondary mb-0">Atur hari, jam, dan kuota pasien dalam satu layar, lalu simpan sekali.</p>
    </x-slot>

    @include('partials.flash')

    <livewire:admin.jadwal-editor :dokter="$dokter" />
</x-app-layout>
