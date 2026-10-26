<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1">Kelola poli</h1>
                <p class="text-body-secondary mb-0">Kode poli dipakai sebagai awalan nomor antrian.</p>
            </div>
            <a href="{{ route('admin.poli.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah poli</a>
        </div>
    </x-slot>

    @include('partials.flash')

    <div class="card">
        <table class="table table-stack mb-0">
            <thead>
                <tr><th>Kode</th><th>Nama poli</th><th>Status</th><th>Dokter</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($polis as $poli)
                    <tr>
                        <td data-label="Kode"><span class="kode">{{ $poli->kode_poli }}</span></td>
                        <td data-label="Nama poli" class="fw-semibold">{{ $poli->nama_poli }}</td>
                        <td data-label="Status">
                            @if ($poli->aktif)
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Aktif</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>
                            @endif
                        </td>
                        <td data-label="Dokter">{{ $poli->dokters_count }}</td>
                        <td data-label="Aksi">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('admin.poli.edit', $poli) }}" class="btn btn-outline-secondary btn-sm">Ubah</a>
                                <form method="POST" action="{{ route('admin.poli.destroy', $poli) }}" onsubmit="return confirm('Hapus poli ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-body-secondary">Belum ada poli. Tambahkan poli pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
