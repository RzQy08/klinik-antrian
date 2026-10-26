<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('admin.dokter.index') }}" class="small"><i class="bi bi-arrow-left me-1"></i>Kembali ke daftar dokter</a>
        <h1 class="h2 mt-3 mb-1">Jadwal praktik</h1>
        <p class="text-body-secondary mb-0">{{ $dokter->nama }}</p>
    </x-slot>

    @include('partials.flash')
    @include('partials.errors')

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h2 class="card-title-sm">Tambah jadwal</h2></div>
                <form method="POST" action="{{ route('admin.dokter.jadwal.store', $dokter) }}" class="card-body d-grid gap-3">
                    @csrf
                    <div>
                        <label for="hari" class="form-label">Hari</label>
                        <select id="hari" name="hari" required class="form-select">
                            @foreach (['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'] as $hari)
                                <option value="{{ $hari }}" @selected(old('hari') === $hari)>{{ ucfirst($hari) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="jam_mulai" class="form-label">Jam mulai</label>
                            <input id="jam_mulai" name="jam_mulai" type="time" required class="form-control" value="{{ old('jam_mulai', '08:00') }}">
                        </div>
                        <div class="col-6">
                            <label for="jam_selesai" class="form-label">Jam selesai</label>
                            <input id="jam_selesai" name="jam_selesai" type="time" required class="form-control" value="{{ old('jam_selesai', '16:00') }}">
                        </div>
                    </div>
                    <div>
                        <label for="kuota" class="form-label">Kuota pasien per sesi</label>
                        <input id="kuota" name="kuota" type="number" min="1" max="200" required class="form-control" value="{{ old('kuota', 40) }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Tambah jadwal</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <table class="table table-stack mb-0">
                    <thead><tr><th>Hari</th><th>Jam</th><th>Kuota</th><th class="text-end">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($jadwals as $jadwal)
                            <tr>
                                <td data-label="Hari" class="fw-semibold text-capitalize">{{ $jadwal->hari }}</td>
                                <td data-label="Jam">{{ substr($jadwal->jam_mulai, 0, 5) }} sampai {{ substr($jadwal->jam_selesai, 0, 5) }}</td>
                                <td data-label="Kuota">{{ $jadwal->kuota }} pasien</td>
                                <td data-label="Aksi">
                                    <div class="d-flex justify-content-end">
                                        <form method="POST" action="{{ route('admin.jadwal.destroy', $jadwal) }}" onsubmit="return confirm('Hapus jadwal ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-body-secondary">Belum ada jadwal. Tambahkan lewat form di samping.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
