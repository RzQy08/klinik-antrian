<div wire:poll.3s.visible="segarkan" class="bn-poli-grid">
    @forelse ($baris as $r)
        <article wire:key="poli-{{ $r['id'] }}" class="bn-poli {{ $r['status'] ? 'is-aktif' : '' }}">
            <header class="bn-poli-head">
                <span class="kode">{{ $r['kode'] }}</span>
                <h3>{{ $r['nama'] }}</h3>
            </header>

            <div class="bn-roll" role="status" aria-live="polite" aria-atomic="true" aria-label="Nomor sedang dipanggil di {{ $r['nama'] }}">
                @if ($r['bergerak'] && $r['sebelumnya'])
                    <span class="bn-roll-out" aria-hidden="true">{{ $r['sebelumnya'] }}</span>
                @endif
                <span wire:key="no-{{ $r['id'] }}-{{ $r['kunci'] }}" class="bn-roll-in {{ $r['bergerak'] ? 'is-masuk' : '' }} {{ $r['nomor'] ? '' : 'is-kosong' }}">{{ $r['nomor'] ?? '—' }}</span>
            </div>

            <div class="bn-poli-meta">
                @if ($r['status'])
                    <span class="bn-pill {{ $r['status'] === 'dipanggil' ? 'bn-pill-panggil' : 'bn-pill-layani' }}">{{ $r['status'] === 'dipanggil' ? 'Dipanggil' : 'Sedang dilayani' }}</span>
                    @if ($r['dokter'])<span class="bn-dokter"><i class="bi bi-person-badge"></i>{{ $r['dokter'] }}</span>@endif
                @else
                    <span class="text-body-secondary small">Belum ada panggilan</span>
                @endif
            </div>

            <footer class="bn-poli-foot">
                <div class="d-flex align-items-baseline justify-content-between mb-2">
                    <span><b class="numeral fs-4 text-navy">{{ $r['menunggu'] }}</b> <span class="small text-body-secondary">menunggu</span></span>
                    <a href="{{ route('antrian.display', $r['id']) }}" class="bn-link">Lihat layar <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="bar" aria-hidden="true"><span class="{{ $r['menunggu'] > 10 ? 'bg-warning' : 'bg-primary' }}" style="width: {{ $r['persen'] }}%"></span></div>
            </footer>
        </article>
    @empty
        <p class="bn-kosong">Belum ada poli aktif. Jalankan <code>php artisan db:seed</code> atau tambahkan poli lewat dashboard admin.</p>
    @endforelse
</div>
