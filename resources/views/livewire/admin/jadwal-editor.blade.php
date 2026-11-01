<div class="ap aj" wire:key="jadwal-{{ $dokter->id }}">

    <div class="ap-loading" wire:loading.delay wire:target="simpan,preset" aria-hidden="true"></div>

    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- ============ PROFIL + RINGKASAN TERSIMPAN ============ --}}
    <section class="aj-top">
        <div class="aj-profile">
            <span class="avatar aj-avatar">{{ mb_strtoupper(mb_substr(trim(preg_replace('/^(dr|drg)\.?\s+/i', '', $dokter->nama)), 0, 1)) }}</span>
            <div class="min-w-0">
                <h2>{{ $dokter->nama }}</h2>
                <p><i class="bi bi-hospital me-1"></i>{{ $dokter->poli?->nama_poli }} &middot; {{ $dokter->aktif ? 'Aktif' : 'Nonaktif' }}</p>
            </div>
        </div>
        <div class="aj-sum" aria-label="Jadwal tersimpan">
            <div><span class="numeral">{{ $ringkasan['hari'] }}</span><small>hari praktik</small></div>
            <div><span class="numeral">{{ $ringkasan['jam'] }}</span><small>jam per minggu</small></div>
            <div><span class="numeral">{{ $ringkasan['kuota'] }}</span><small>kuota per minggu</small></div>
        </div>
    </section>

    {{-- ============ ISI CEPAT ============ --}}
    <section class="aj-presets" aria-label="Isi cepat">
        <span class="aj-presets-label"><i class="bi bi-magic me-1"></i>Isi cepat</span>
        <button type="button" wire:click="preset('senin-sabtu')"><i class="bi bi-calendar-week"></i>Senin sampai Sabtu</button>
        <button type="button" wire:click="preset('senin-jumat')"><i class="bi bi-calendar-range"></i>Senin sampai Jumat</button>
        <button type="button" wire:click="preset('samakan')"><i class="bi bi-copy"></i>Samakan jam semua hari aktif</button>
        <button type="button" wire:click="preset('kosong')" class="is-danger"><i class="bi bi-eraser"></i>Kosongkan</button>
    </section>

    {{-- ============ KARTU HARI ============ --}}
    <div class="aj-grid">
        @foreach (\App\Livewire\Admin\JadwalEditor::HARI as $hari => $label)
            <article class="aj-day" :class="{ 'is-on': $wire.jadwal.{{ $hari }}.aktif }" wire:key="hari-{{ $hari }}">
                <header>
                    <div>
                        <h3>{{ $label }}</h3>
                        <span class="aj-day-state" x-text="$wire.jadwal.{{ $hari }}.aktif ? 'Praktik' : 'Libur'"></span>
                    </div>
                    <button type="button" class="ap-switch" :class="{ 'is-on': $wire.jadwal.{{ $hari }}.aktif }" role="switch" :aria-checked="$wire.jadwal.{{ $hari }}.aktif ? 'true' : 'false'"
                            aria-label="Praktik hari {{ $label }}" @click="$wire.jadwal.{{ $hari }}.aktif = ! $wire.jadwal.{{ $hari }}.aktif"><span></span></button>
                </header>

                <div class="aj-fields" x-show="$wire.jadwal.{{ $hari }}.aktif" x-collapse>
                    <div class="aj-times">
                        <label>
                            <span>Mulai</span>
                            <input type="time" x-model="$wire.jadwal.{{ $hari }}.mulai" class="@error('jadwal.'.$hari.'.mulai') is-invalid @enderror">
                        </label>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        <label>
                            <span>Selesai</span>
                            <input type="time" x-model="$wire.jadwal.{{ $hari }}.selesai" class="@error('jadwal.'.$hari.'.selesai') is-invalid @enderror">
                        </label>
                    </div>
                    <label class="aj-kuota">
                        <span>Kuota pasien</span>
                        <input type="number" min="1" max="200" inputmode="numeric" x-model="$wire.jadwal.{{ $hari }}.kuota" class="@error('jadwal.'.$hari.'.kuota') is-invalid @enderror">
                    </label>
                    @foreach (['mulai', 'selesai', 'kuota'] as $kolom)
                        @error('jadwal.'.$hari.'.'.$kolom)<p class="aj-error"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</p>@enderror
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    {{-- ============ BAR SIMPAN ============ --}}
    <div class="aj-savebar">
        <span class="aj-savebar-text"><i class="bi bi-info-circle me-1"></i>Perubahan baru berlaku setelah disimpan.</span>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.dokter.index') }}" class="btn btn-outline-secondary">Kembali</a>
            <button type="button" class="btn btn-primary" wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan">
                <span wire:loading.remove wire:target="simpan"><i class="bi bi-check2-circle me-1"></i>Simpan jadwal</span>
                <span wire:loading wire:target="simpan"><span class="spinner-dot"></span>Menyimpan</span>
            </button>
        </div>
    </div>
</div>
