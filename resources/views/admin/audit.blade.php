<x-app-layout>
    <x-slot name="header">
        <div class="lp-head">
            <div>
                <h1 class="lp-title">Jejak audit</h1>
                <p class="lp-sub">Siapa membuka data pasien dan mengubah akun. Dihapus otomatis setelah 1 tahun.</p>
            </div>
            <form method="GET" class="lp-filter-inline">
                <label class="visually-hidden" for="fAksi">Jenis kegiatan</label>
                <select id="fAksi" name="aksi" class="form-select" onchange="this.form.submit()">
                    <option value="">Semua kegiatan</option>
                    @foreach (\App\Models\AuditLog::AKSI as $kunci => [$label])
                        <option value="{{ $kunci }}" @selected($aksi === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </x-slot>

    <div class="lp-page">
        <div class="lp-tabel">
            <div class="lp-scroll">
                <table class="table lp-table align-middle">
                    <thead>
                        <tr><th>Waktu</th><th>Pengguna</th><th>Kegiatan</th><th>Keterangan</th><th>Alamat IP</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $a)
                            <tr>
                                <td data-label="Waktu" class="lp-td-tgl text-nowrap">{{ $a->created_at->format('d M Y') }}<div class="small text-body-secondary">{{ $a->created_at->format('H.i.s') }}</div></td>
                                <td data-label="Pengguna" class="lp-td-pasien">{{ $a->nama_pengguna ?? 'Tamu' }}@if ($a->peran)<div class="small text-body-secondary">{{ ucfirst($a->peran) }}</div>@endif</td>
                                <td data-label="Kegiatan" class="lp-td-status"><span class="lp-dur {{ ['warn' => 'warn', 'bad' => 'bad'][$a->warna()] ?? '' }}">{{ $a->label() }}</span></td>
                                <td data-label="Keterangan" class="lp-td-dokter">{{ $a->keterangan ?? '-' }}</td>
                                <td data-label="IP" class="lp-td-poli text-body-secondary small">{{ $a->ip ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="lp-kosong"><i class="bi bi-shield-check" aria-hidden="true"></i><strong>Belum ada catatan</strong></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lp-pager">
            <span class="lp-pager-info">Halaman {{ $data->currentPage() }}, {{ $data->count() }} catatan ditampilkan.</span>
            {{ $data->links('pagination::simple-bootstrap-5') }}
        </div>
    </div>
</x-app-layout>
