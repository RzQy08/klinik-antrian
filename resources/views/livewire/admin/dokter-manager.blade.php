@php
    $huruf = ['senin' => 'S', 'selasa' => 'S', 'rabu' => 'R', 'kamis' => 'K', 'jumat' => 'J', 'sabtu' => 'S', 'minggu' => 'M'];
    $namaHari = ['senin' => 'Senin', 'selasa' => 'Selasa', 'rabu' => 'Rabu', 'kamis' => 'Kamis', 'jumat' => 'Jumat', 'sabtu' => 'Sabtu', 'minggu' => 'Minggu'];
    $targetMuat = 'cari,poliId,status,akun,urut,perHalaman,gotoPage,nextPage,previousPage,resetFilter';
@endphp
<div class="ap dk" wire:poll.30s.visible>

    <div class="ap-loading" wire:loading.delay wire:target="{{ $targetMuat }}" aria-hidden="true"></div>

    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- ============ RINGKASAN ============ --}}
    <section class="ap-kpis" aria-label="Ringkasan dokter">
        <div class="ad-kpi"><span class="ad-ico ad-ico-navy"><i class="bi bi-person-badge"></i></span><div><div class="numeral">{{ $ringkasan['total'] }}</div><div class="ad-kpi-label">Total dokter</div></div></div>
        <div class="ad-kpi"><span class="ad-ico ad-ico-success"><i class="bi bi-toggle2-on"></i></span><div><div class="numeral">{{ $ringkasan['aktif'] }}</div><div class="ad-kpi-label">Aktif bertugas</div></div></div>
        <button type="button" wire:click="$set('akun', 'tanpa')" class="ad-kpi dk-kpi-btn {{ $ringkasan['tanpa_akun'] > 0 ? 'ap-kpi-alert' : '' }}"><span class="ad-ico {{ $ringkasan['tanpa_akun'] > 0 ? 'ad-ico-warning' : 'ad-ico-primary' }}"><i class="bi bi-person-lock"></i></span><div class="text-start"><div class="numeral">{{ $ringkasan['tanpa_akun'] }}</div><div class="ad-kpi-label">Belum punya akun</div></div></button>
        <div class="ad-kpi"><span class="ad-ico ad-ico-info"><i class="bi bi-calendar-x"></i></span><div><div class="numeral">{{ $ringkasan['tanpa_jadwal'] }}</div><div class="ad-kpi-label">Belum ada jadwal</div></div></div>
    </section>

    {{-- ============ PERKAKAS ============ --}}
    <section class="dk-toolbar" aria-label="Cari dan saring dokter">
        <div class="ap-search dk-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.300ms="cari" placeholder="Cari nama dokter atau nomor SIP" aria-label="Cari dokter" autocomplete="off">
            @if ($cari !== '')
                <button type="button" wire:click="$set('cari', '')" aria-label="Hapus pencarian"><i class="bi bi-x-circle-fill"></i></button>
            @endif
        </div>

        <label class="ap-sort dk-filter">
            <i class="bi bi-hospital" aria-hidden="true"></i>
            <select wire:model.live="poliId" aria-label="Saring menurut poli">
                <option value="">Semua poli</option>
                @foreach ($polis as $p)<option value="{{ $p->id }}">{{ $p->nama_poli }}</option>@endforeach
            </select>
        </label>

        <label class="ap-sort dk-filter">
            <i class="bi bi-toggles" aria-hidden="true"></i>
            <select wire:model.live="status" aria-label="Saring menurut status">
                <option value="semua">Semua status</option>
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </label>

        <label class="ap-sort dk-filter">
            <i class="bi bi-person-lock" aria-hidden="true"></i>
            <select wire:model.live="akun" aria-label="Saring menurut akun login">
                <option value="semua">Semua akun</option>
                <option value="ada">Punya akun</option>
                <option value="tanpa">Belum punya akun</option>
            </select>
        </label>

        <label class="ap-sort dk-filter">
            <i class="bi bi-sort-down" aria-hidden="true"></i>
            <select wire:model.live="urut" aria-label="Urutkan dokter">
                <option value="nama">Urut nama</option>
                <option value="poli">Urut poli</option>
                <option value="terbaru">Terbaru</option>
            </select>
        </label>

        <a href="{{ route('admin.dokter.create') }}" class="btn btn-primary ap-add"><i class="bi bi-plus-lg me-1"></i>Tambah dokter</a>
    </section>

    {{-- ============ TABEL ============ --}}
    <section class="dk-panel" wire:loading.class="is-loading" wire:target="{{ $targetMuat }}">
        <table class="dk-table">
            <thead>
                <tr>
                    <th scope="col">Dokter</th>
                    <th scope="col">Poli</th>
                    <th scope="col">Akun login</th>
                    <th scope="col">Jadwal praktik</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-end"><span class="visually-hidden">Aksi</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dokters as $d)
                    @php $hariAda = $d->jadwalPraktiks->pluck('hari')->all(); @endphp
                    <tr wire:key="dokter-{{ $d->id }}" class="{{ $d->aktif ? '' : 'is-off' }}">
                        <td class="dk-who">
                            <div class="dk-who-inner">
                            <span class="avatar">{{ mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', $d->nama)), 0, 1)) }}</span>
                            <span class="dk-who-text">
                                <span class="dk-name">{{ $d->nama }}</span>
                                <span class="dk-sub">{{ $d->no_sip ?: 'No. SIP belum diisi' }}</span>
                            </span>
                            </div>
                        </td>
                        <td data-label="Poli"><span class="dk-poli"><span class="dk-kode">{{ $d->poli?->kode_poli }}</span>{{ $d->poli?->nama_poli }}</span></td>
                        <td data-label="Akun login">
                            @if ($d->user)
                                <span class="dk-akun" title="{{ $d->user->email }}"><i class="bi bi-check-circle-fill"></i>{{ $d->user->email }}</span>
                            @else
                                <span class="dk-akun is-none"><i class="bi bi-dash-circle"></i>Belum ada</span>
                            @endif
                        </td>
                        <td data-label="Jadwal">
                            @if ($hariAda)
                                <a href="{{ route('admin.dokter.jadwal.index', $d->id) }}" class="dk-days" aria-label="Jadwal praktik: {{ collect($hariAda)->map(fn ($h) => $namaHari[$h] ?? $h)->implode(', ') }}">
                                    @foreach ($huruf as $kunci => $h)<span class="{{ in_array($kunci, $hariAda, true) ? 'is-on' : '' }}" title="{{ $namaHari[$kunci] }}">{{ $h }}</span>@endforeach
                                </a>
                            @else
                                <a href="{{ route('admin.dokter.jadwal.index', $d->id) }}" class="dk-nojadwal"><i class="bi bi-plus-circle"></i>Atur jadwal</a>
                            @endif
                        </td>
                        <td data-label="Status">
                            <button type="button" class="ap-switch {{ $d->aktif ? 'is-on' : '' }}" role="switch" aria-checked="{{ $d->aktif ? 'true' : 'false' }}"
                                    aria-label="{{ $d->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $d->nama }}"
                                    wire:click="toggleAktif({{ $d->id }})" wire:loading.attr="disabled" wire:target="toggleAktif({{ $d->id }})"><span></span></button>
                            <span class="ap-state {{ $d->aktif ? 'is-on' : '' }} ms-2">{{ $d->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                        </td>
                        <td class="dk-act">
                            <div class="dk-act-in">
                            <a href="{{ route('admin.dokter.jadwal.index', $d->id) }}" class="btn btn-outline-secondary btn-sm" title="Jadwal praktik"><i class="bi bi-calendar3"></i><span class="dk-act-text">Jadwal</span></a>
                            <a href="{{ route('admin.dokter.edit', $d->id) }}" class="btn btn-outline-secondary btn-sm" title="Ubah data"><i class="bi bi-pencil-square"></i><span class="dk-act-text">Ubah</span></a>
                            @if ($d->antrians_exists)
                                <button type="button" class="btn btn-outline-secondary btn-sm ap-del is-locked" disabled title="Punya riwayat antrian, nonaktifkan saja" aria-label="Tidak bisa dihapus, punya riwayat antrian"><i class="bi bi-lock"></i></button>
                            @else
                                <button type="button" class="btn btn-outline-danger btn-sm ap-del" wire:click="konfirmasiHapus({{ $d->id }})" aria-label="Hapus {{ $d->nama }}"><i class="bi bi-trash3"></i></button>
                            @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($dokters->isEmpty())
            <div class="ap-empty" wire:key="kosong">
                <span class="ap-empty-ico"><i class="bi bi-{{ $adaFilter ? 'search' : 'person-badge' }}"></i></span>
                @if ($adaFilter)
                    <h3>Tidak ada dokter yang cocok</h3>
                    <p>Coba kata kunci atau saringan lain.</p>
                    <button type="button" class="btn btn-primary" wire:click="resetFilter"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset saringan</button>
                @else
                    <h3>Belum ada dokter</h3>
                    <p>Tambahkan dokter pertama supaya pasien bisa dipanggil.</p>
                    <a href="{{ route('admin.dokter.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah dokter</a>
                @endif
            </div>
        @endif

        {{-- ============ HALAMAN ============ --}}
        @if ($dokters->total() > 0)
            @php
                $sekarang = $dokters->currentPage();
                $terakhir = $dokters->lastPage();
                $awal = max(1, min($sekarang - 2, $terakhir - 4));
                $akhir = min($terakhir, $awal + 4);
            @endphp
            <footer class="dk-foot">
                <div class="dk-count">
                    Menampilkan <b>{{ $dokters->firstItem() }}</b> sampai <b>{{ $dokters->lastItem() }}</b> dari <b>{{ $dokters->total() }}</b> dokter
                </div>

                <div class="dk-foot-right">
                    <label class="dk-per">
                        <span>Per halaman</span>
                        <select wire:model.live="perHalaman" aria-label="Jumlah baris per halaman">
                            @foreach ([10, 25, 50] as $n)<option value="{{ $n }}">{{ $n }}</option>@endforeach
                        </select>
                    </label>

                    @if ($terakhir > 1)
                        <nav class="dk-pager" aria-label="Halaman">
                            <button type="button" wire:click="previousPage" @disabled($sekarang <= 1) aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                            @if ($awal > 1)
                                <button type="button" wire:click="gotoPage(1)">1</button>
                                @if ($awal > 2)<span class="dk-gap">…</span>@endif
                            @endif
                            @for ($i = $awal; $i <= $akhir; $i++)
                                <button type="button" wire:click="gotoPage({{ $i }})" class="{{ $i === $sekarang ? 'is-on' : '' }}" @if ($i === $sekarang) aria-current="page" @endif>{{ $i }}</button>
                            @endfor
                            @if ($akhir < $terakhir)
                                @if ($akhir < $terakhir - 1)<span class="dk-gap">…</span>@endif
                                <button type="button" wire:click="gotoPage({{ $terakhir }})">{{ $terakhir }}</button>
                            @endif
                            <button type="button" wire:click="nextPage" @disabled($sekarang >= $terakhir) aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                        </nav>
                    @endif
                </div>
            </footer>
        @endif
    </section>

    {{-- ============ KONFIRMASI HAPUS ============ --}}
    @if ($hapus)
        <div class="ap-modal" wire:key="modal-hapus" wire:keydown.escape.window="batalHapus">
            <div class="ap-modal-backdrop" wire:click="batalHapus"></div>
            <div class="ap-modal-card" role="alertdialog" aria-modal="true" aria-labelledby="dk-hapus-judul">
                <span class="ap-modal-ico"><i class="bi bi-trash3"></i></span>
                <h3 id="dk-hapus-judul">Hapus {{ $hapus->nama }}?</h3>
                <p>Data dokter dan jadwal praktiknya dihapus. Akun login, bila ada, tetap tersimpan tetapi tidak lagi terhubung ke dokter. Tindakan ini tidak bisa dibatalkan.</p>
                <div class="ap-modal-act">
                    <button type="button" class="btn btn-outline-secondary" wire:click="batalHapus">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="hapus" wire:loading.attr="disabled" wire:target="hapus"><i class="bi bi-trash3 me-1"></i>Ya, hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
