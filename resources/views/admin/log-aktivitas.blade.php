@php
    $ikon = [
        'login' => 'box-arrow-in-right', 'login_demo' => 'lightning-charge', 'logout' => 'box-arrow-right', 'login_gagal' => 'shield-exclamation', 'login_terkunci' => 'lock',
        'password_reset' => 'key', 'password_diubah' => 'key', 'akun_dibuat' => 'person-plus', 'peran_diubah' => 'person-gear',
        'akun_dihapus' => 'person-x', 'akun_nonaktif' => 'person-slash', 'akun_aktif' => 'person-check',
        'lihat_riwayat' => 'eye', 'ekspor_csv' => 'download',
    ];
    $disaring = $aksi || $peran || $cari !== '' || $per !== 25;
@endphp
<x-app-layout>
    <div class="lp-page">
        <form method="GET" class="dt-bar" role="search">
            <div class="dt-search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <label class="visually-hidden" for="fCari">Cari pengguna, keterangan, atau IP</label>
                <input id="fCari" type="search" name="cari" value="{{ $cari }}" maxlength="80" placeholder="Cari pengguna, keterangan, IP" autocomplete="off">
            </div>
            <div class="dt-filters">
                <label class="visually-hidden" for="fAksi">Kegiatan</label>
                <select id="fAksi" name="aksi" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">Semua kegiatan</option>
                    @foreach (\App\Models\LogAktivitas::AKSI as $kunci => [$label])
                        <option value="{{ $kunci }}" @selected($aksi === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="visually-hidden" for="fPeran">Peran</label>
                <select id="fPeran" name="peran" class="form-select" onchange="this.form.requestSubmit()">
                    <option value="">Semua peran</option>
                    @foreach (['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'] as $k => $l)
                        <option value="{{ $k }}" @selected($peran === $k)>{{ $l }}</option>
                    @endforeach
                </select>
                <label class="visually-hidden" for="fPer">Baris per halaman</label>
                <select id="fPer" name="per" class="form-select" onchange="this.form.requestSubmit()">
                    @foreach ($perHalaman as $n)
                        <option value="{{ $n }}" @selected($per === $n)>{{ $n }} baris</option>
                    @endforeach
                </select>
                @if ($disaring)
                    <a href="{{ route('admin.log-aktivitas') }}" class="dt-reset"><i class="bi bi-x-lg" aria-hidden="true"></i>Atur ulang</a>
                @endif
            </div>
        </form>

        <section class="dt" aria-label="Log aktivitas">
            <div class="dt-scroll">
                <table class="dt-table">
                    <thead>
                        <tr>
                            <th scope="col" class="dt-c-waktu">Waktu</th>
                            <th scope="col" class="dt-c-user">Pengguna</th>
                            <th scope="col" class="dt-c-aksi">Kegiatan</th>
                            <th scope="col" class="dt-c-ket">Keterangan</th>
                            <th scope="col" class="dt-c-ip">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $a)
                            @php $nama = $a->nama_pengguna ?? 'Tamu'; @endphp
                            <tr>
                                <td class="dt-c-waktu" data-label="Waktu">
                                    <time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->locale('id')->diffForHumans() }}">
                                        <b>{{ $a->created_at->format('d M Y') }}</b><small>{{ $a->created_at->format('H.i.s') }}</small>
                                    </time>
                                </td>
                                <td class="dt-c-user" data-label="Pengguna">
                                    <span class="dt-user">
                                        <span class="dt-av" aria-hidden="true">{{ mb_strtoupper(mb_substr($nama, 0, 1)) }}</span>
                                        <span class="dt-user-t"><b>{{ $nama }}</b>@if ($a->peran)<small>{{ ucfirst($a->peran) }}</small>@endif</span>
                                    </span>
                                </td>
                                <td class="dt-c-aksi" data-label="Kegiatan">
                                    <span class="dt-tag dt-tag-{{ $a->warna() }}"><i class="bi bi-{{ $ikon[$a->aksi] ?? 'dot' }}" aria-hidden="true"></i>{{ $a->label() }}</span>
                                </td>
                                <td class="dt-c-ket" data-label="Keterangan">{{ $a->keterangan ?: '—' }}</td>
                                <td class="dt-c-ip" data-label="IP"><code>{{ $a->ip ?? '—' }}</code></td>
                            </tr>
                        @empty
                            <tr class="dt-empty"><td colspan="5"><div class="lp-kosong"><i class="bi bi-inbox" aria-hidden="true"></i><strong>Tidak ada catatan</strong></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="dt-foot">
                <span class="dt-info">
                    @if ($data->total() > 0)
                        <b>{{ $data->firstItem() }}&ndash;{{ $data->lastItem() }}</b> dari <b>{{ number_format($data->total(), 0, ',', '.') }}</b> catatan
                    @else
                        0 catatan
                    @endif
                </span>
                {{ $data->links('pagination.dt') }}
            </footer>
        </section>
    </div>
</x-app-layout>
