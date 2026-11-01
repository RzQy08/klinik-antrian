<div class="ap" wire:poll.20s.visible>

    {{-- indikator memuat (pencarian, saringan, urutan) --}}
    <div class="ap-loading" wire:loading.delay wire:target="cari,status,urut,resetFilter" aria-hidden="true"></div>

    {{-- pemberitahuan singkat --}}
    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- ============ RINGKASAN ============ --}}
    <section class="ap-kpis" aria-label="Ringkasan poli">
        <div class="ad-kpi"><span class="ad-ico ad-ico-navy"><i class="bi bi-hospital"></i></span><div><div class="numeral">{{ $ringkasan['total'] }}</div><div class="ad-kpi-label">Total poli</div></div></div>
        <div class="ad-kpi"><span class="ad-ico ad-ico-success"><i class="bi bi-toggle2-on"></i></span><div><div class="numeral">{{ $ringkasan['aktif'] }}</div><div class="ad-kpi-label">Aktif untuk pasien</div></div></div>
        <div class="ad-kpi"><span class="ad-ico ad-ico-warning"><i class="bi bi-people"></i></span><div><div class="numeral">{{ $ringkasan['menunggu'] }}</div><div class="ad-kpi-label">Pasien menunggu</div></div></div>
        <div class="ad-kpi {{ $ringkasan['tanpa_dokter'] > 0 ? 'ap-kpi-alert' : '' }}"><span class="ad-ico {{ $ringkasan['tanpa_dokter'] > 0 ? 'ad-ico-danger' : 'ad-ico-primary' }}"><i class="bi bi-person-exclamation"></i></span><div><div class="numeral">{{ $ringkasan['tanpa_dokter'] }}</div><div class="ad-kpi-label">Poli tanpa dokter</div></div></div>
    </section>

    {{-- ============ PERKAKAS ============ --}}
    <section class="ap-toolbar" aria-label="Cari dan saring poli">
        <div class="ap-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" wire:model.live.debounce.250ms="cari" placeholder="Cari nama atau kode poli" aria-label="Cari poli" autocomplete="off">
            @if ($cari !== '')
                <button type="button" wire:click="$set('cari', '')" aria-label="Hapus pencarian"><i class="bi bi-x-circle-fill"></i></button>
            @endif
        </div>

        <div class="ap-seg" role="group" aria-label="Saring status">
            <button type="button" wire:click="$set('status', 'semua')" class="{{ $status === 'semua' ? 'is-on' : '' }}" aria-pressed="{{ $status === 'semua' ? 'true' : 'false' }}">Semua <span>{{ $ringkasan['total'] }}</span></button>
            <button type="button" wire:click="$set('status', 'aktif')" class="{{ $status === 'aktif' ? 'is-on' : '' }}" aria-pressed="{{ $status === 'aktif' ? 'true' : 'false' }}">Aktif <span>{{ $ringkasan['aktif'] }}</span></button>
            <button type="button" wire:click="$set('status', 'nonaktif')" class="{{ $status === 'nonaktif' ? 'is-on' : '' }}" aria-pressed="{{ $status === 'nonaktif' ? 'true' : 'false' }}">Nonaktif <span>{{ $ringkasan['nonaktif'] }}</span></button>
        </div>

        <label class="ap-sort">
            <i class="bi bi-sort-down" aria-hidden="true"></i>
            <span class="visually-hidden">Urutkan</span>
            <select wire:model.live="urut" aria-label="Urutkan poli">
                <option value="kode">Menurut kode</option>
                <option value="nama">Menurut nama</option>
                <option value="ramai">Paling ramai</option>
            </select>
        </label>

        <a href="{{ route('admin.poli.create') }}" class="btn btn-primary ap-add"><i class="bi bi-plus-lg me-1"></i>Tambah poli</a>
    </section>

    {{-- ============ DAFTAR POLI ============ --}}
    <div class="ap-grid">
        @foreach ($polis as $poli)
            <article wire:key="poli-{{ $poli->id }}" class="ap-card {{ $poli->aktif ? '' : 'is-off' }}">
                <header class="ap-card-top">
                    <span class="kode kode-lg">{{ $poli->kode_poli }}</span>
                    <div class="ap-card-title">
                        <h3>{{ $poli->nama_poli }}</h3>
                        <span class="ap-state {{ $poli->aktif ? 'is-on' : '' }}">{{ $poli->aktif ? 'Aktif' : 'Nonaktif' }}</span>
                    </div>
                    <button type="button" class="ap-switch {{ $poli->aktif ? 'is-on' : '' }}" role="switch" aria-checked="{{ $poli->aktif ? 'true' : 'false' }}"
                            aria-label="{{ $poli->aktif ? 'Nonaktifkan' : 'Aktifkan' }} {{ $poli->nama_poli }}"
                            wire:click="toggleAktif({{ $poli->id }})" wire:loading.attr="disabled" wire:target="toggleAktif({{ $poli->id }})"><span></span></button>
                </header>

                @if ($poli->aktif && $poli->dokter_aktif_count === 0)
                    <p class="ap-warn"><i class="bi bi-exclamation-triangle-fill"></i>Belum ada dokter aktif. Pasien belum bisa dipanggil.</p>
                @elseif ($poli->menunggu_count > 10)
                    <p class="ap-warn ap-warn-busy"><i class="bi bi-lightning-charge-fill"></i>Sedang ramai, {{ $poli->menunggu_count }} pasien menunggu.</p>
                @endif

                <dl class="ap-stats">
                    <div><dt>Dokter</dt><dd><span class="numeral">{{ $poli->dokter_aktif_count }}</span><small>/{{ $poli->dokters_count }}</small></dd></div>
                    <div><dt>Menunggu</dt><dd><span class="numeral">{{ $poli->menunggu_count }}</span></dd></div>
                    <div><dt>Selesai</dt><dd><span class="numeral">{{ $poli->selesai_count }}</span></dd></div>
                </dl>

                <footer class="ap-actions">
                    <a href="{{ route('admin.poli.edit', $poli) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil-square me-1"></i>Ubah</a>
                    <a href="{{ route('antrian.display', $poli) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" aria-label="Buka layar TV {{ $poli->nama_poli }}" title="Layar TV"><i class="bi bi-tv"></i></a>
                    @if ($poli->antrians_count > 0)
                        <button type="button" class="btn btn-outline-secondary btn-sm ap-del is-locked" disabled title="Punya riwayat antrian, nonaktifkan saja" aria-label="Tidak bisa dihapus, punya riwayat antrian"><i class="bi bi-lock"></i></button>
                    @else
                        <button type="button" class="btn btn-outline-danger btn-sm ap-del" wire:click="konfirmasiHapus({{ $poli->id }})" aria-label="Hapus {{ $poli->nama_poli }}"><i class="bi bi-trash3"></i></button>
                    @endif
                </footer>
            </article>
        @endforeach

        @unless ($adaFilter)
            <a wire:key="poli-tambah" href="{{ route('admin.poli.create') }}" class="ap-card ap-card-add">
                <span class="ap-add-ico"><i class="bi bi-plus-lg"></i></span>
                <span class="fw-semibold">Tambah poli baru</span>
                <span class="small text-body-secondary">Kode poli jadi awalan nomor antrian</span>
            </a>
        @endunless
    </div>

    {{-- keadaan kosong --}}
    @if ($polis->isEmpty())
        <div class="ap-empty" wire:key="kosong">
            <span class="ap-empty-ico"><i class="bi bi-{{ $adaFilter ? 'search' : 'hospital' }}"></i></span>
            @if ($adaFilter)
                <h3>Tidak ada poli yang cocok</h3>
                <p>Coba kata kunci lain atau tampilkan semua poli.</p>
                <button type="button" class="btn btn-primary" wire:click="resetFilter"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset pencarian</button>
            @else
                <h3>Belum ada poli</h3>
                <p>Tambahkan poli pertama supaya pasien bisa mengambil nomor antrian.</p>
            @endif
        </div>
    @endif

    {{-- ============ KONFIRMASI HAPUS ============ --}}
    @if ($hapus)
        <div class="ap-modal" wire:key="modal-hapus" wire:keydown.escape.window="batalHapus">
            <div class="ap-modal-backdrop" wire:click="batalHapus"></div>
            <div class="ap-modal-card" role="alertdialog" aria-modal="true" aria-labelledby="ap-hapus-judul">
                <span class="ap-modal-ico"><i class="bi bi-trash3"></i></span>
                <h3 id="ap-hapus-judul">Hapus {{ $hapus->nama_poli }}?</h3>
                <p>
                    @if ($hapus->dokters_count > 0)
                        <strong>{{ $hapus->dokters_count }} dokter</strong> yang terdaftar di poli ini ikut terhapus.
                    @else
                        Poli ini belum punya dokter.
                    @endif
                    Tindakan ini tidak bisa dibatalkan.
                </p>
                <div class="ap-modal-act">
                    <button type="button" class="btn btn-outline-secondary" wire:click="batalHapus">Batal</button>
                    <button type="button" class="btn btn-danger" wire:click="hapus" wire:loading.attr="disabled" wire:target="hapus"><i class="bi bi-trash3 me-1"></i>Ya, hapus</button>
                </div>
            </div>
        </div>
    @endif
</div>
