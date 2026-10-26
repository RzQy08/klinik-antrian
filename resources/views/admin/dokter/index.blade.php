<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
            <div>
                <h1 class="h2 mb-1">Kelola dokter</h1>
                <p class="text-body-secondary mb-0">Atur dokter, jadwal praktik, dan akun login.</p>
            </div>
            <a href="{{ route('admin.dokter.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah dokter</a>
        </div>
    </x-slot>

    @include('partials.flash')

    <div class="card">
        <table class="table table-stack mb-0">
            <thead>
                <tr><th>Nama</th><th>Poli</th><th>Akun login</th><th>Status</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($dokters as $dokter)
                    <tr>
                        <td data-label="Nama">
                            <div class="d-flex align-items-center gap-3 text-start">
                                <span class="avatar">{{ mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', $dokter->nama)), 0, 1)) }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $dokter->nama }}</div>
                                    <div class="small text-body-secondary">{{ $dokter->no_sip ?? 'No. SIP belum diisi' }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Poli">{{ $dokter->poli->nama_poli }}</td>
                        <td data-label="Akun login">
                            @if ($dokter->user)
                                <span class="small">{{ $dokter->user->email }}</span>
                            @else
                                <span class="small text-body-tertiary">Belum ada</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            @if ($dokter->aktif)
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Aktif</span>
                            @else
                                <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">Nonaktif</span>
                            @endif
                        </td>
                        <td data-label="Aksi">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <a href="{{ route('admin.dokter.jadwal.index', $dokter) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-calendar3 me-1"></i>Jadwal</a>
                                <a href="{{ route('admin.dokter.edit', $dokter) }}" class="btn btn-outline-secondary btn-sm">Ubah</a>
                                <form method="POST" action="{{ route('admin.dokter.destroy', $dokter) }}" onsubmit="return confirm('Hapus dokter ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-body-secondary">Belum ada dokter. Tambahkan dokter pertama.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
