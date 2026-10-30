<x-public-layout :title="'Tiket '.$antrian->nomor_antrian">
    @include('partials.flash')

    <div class="mx-auto" style="max-width: 24rem">
        <livewire:tiket-antrian :antrian-id="$antrian->id" />

        <a href="{{ route('antrian.display', $antrian->poli_id) }}" class="btn btn-outline-primary w-100 mt-4"><i class="bi bi-tv me-2"></i>Lihat layar antrian</a>
    </div>
</x-public-layout>
