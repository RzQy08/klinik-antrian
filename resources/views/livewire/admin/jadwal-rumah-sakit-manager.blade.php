<div class="ap aj jp">

    <div class="ap-loading" wire:loading.delay wire:target="simpan,preset,tambahLibur,hapusLibur" aria-hidden="true"></div>

    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- ============ SAKLAR PEMBATASAN ============ --}}
    <section class="jp-master" :class="{ 'is-on': $wire.aktif }">
        <div>
            <h2>Batasi pendaftaran dan layanan sesuai jam</h2>
            <p x-show="$wire.aktif">Pendaftaran antrian hanya bisa dilakukan pada jam yang diatur di bawah, dan tutup otomatis di hari libur.</p>
            <p x-show="! $wire.aktif" x-cloak>Sedang mati: pendaftaran dan pemanggilan bisa kapan saja. Cocok untuk uji coba. Nyalakan saat aplikasi dipakai sungguhan.</p>
        </div>
        <button type="button" class="ap-switch" :class="{ 'is-on': $wire.aktif }" role="switch" :aria-checked="$wire.aktif ? 'true' : 'false'" aria-label="Batasi sesuai jam operasional" @click="$wire.aktif = ! $wire.aktif"><span></span></button>
    </section>

    {{-- ============ ISI CEPAT ============ --}}
    <section class="aj-presets" aria-label="Isi cepat">
        <span class="aj-presets-label"><i class="bi bi-magic me-1"></i>Isi cepat</span>
        <button type="button" wire:click="preset('senin-sabtu')"><i class="bi bi-calendar-week"></i>Senin sampai Sabtu</button>
        <button type="button" wire:click="preset('senin-jumat')"><i class="bi bi-calendar-range"></i>Senin sampai Jumat</button>
        <button type="button" wire:click="preset('setiap-hari')"><i class="bi bi-calendar3"></i>Setiap hari</button>
        <button type="button" wire:click="preset('samakan')"><i class="bi bi-copy"></i>Samakan jam semua hari buka</button>
    </section>

    {{-- ============ KARTU HARI ============ --}}
    <div class="aj-grid jp-grid">
        @foreach (\App\Models\JadwalRumahSakit::HARI as $nama => $label)
            <article class="aj-day" :class="{ 'is-on': $wire.hari.{{ $nama }}.buka }" wire:key="hari-{{ $nama }}">
                <header>
                    <div>
                        <h3>{{ $label }}</h3>
                        <span class="aj-day-state" x-text="$wire.hari.{{ $nama }}.buka ? 'Buka' : 'Tutup'"></span>
                    </div>
                    <button type="button" class="ap-switch" :class="{ 'is-on': $wire.hari.{{ $nama }}.buka }" role="switch" :aria-checked="$wire.hari.{{ $nama }}.buka ? 'true' : 'false'"
                            aria-label="Rumah sakit buka hari {{ $label }}" @click="$wire.hari.{{ $nama }}.buka = ! $wire.hari.{{ $nama }}.buka"><span></span></button>
                </header>

                <div class="aj-fields" x-show="$wire.hari.{{ $nama }}.buka" x-collapse>
                    <div class="aj-times">
                        <label>
                            <span>Daftar buka</span>
                            <input type="time" x-model="$wire.hari.{{ $nama }}.pendaftaran_buka" class="@error('hari.'.$nama.'.pendaftaran_buka') is-invalid @enderror">
                        </label>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        <label>
                            <span>Daftar tutup</span>
                            <input type="time" x-model="$wire.hari.{{ $nama }}.pendaftaran_tutup" class="@error('hari.'.$nama.'.pendaftaran_tutup') is-invalid @enderror">
                        </label>
                    </div>
                    <label class="aj-kuota">
                        <span>Layanan selesai</span>
                        <input type="time" x-model="$wire.hari.{{ $nama }}.layanan_selesai" class="@error('hari.'.$nama.'.layanan_selesai') is-invalid @enderror">
                    </label>
                    @foreach (['pendaftaran_buka', 'pendaftaran_tutup', 'layanan_selesai'] as $kolom)
                        @error('hari.'.$nama.'.'.$kolom)<p class="aj-error"><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</p>@enderror
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    {{-- ============ HARI LIBUR ============ --}}
    <section class="jp-card" aria-labelledby="judulLibur">
        <header class="jp-card-head">
            <div>
                <h2 id="judulLibur">Hari libur dan penutupan khusus</h2>
                <p>Pada tanggal ini rumah sakit dianggap tutup: pendaftaran ditolak dengan pesan yang jelas, dan pemanggilan tidak dilakukan.</p>
            </div>
        </header>

        <form class="jp-form" wire:submit="tambahLibur">
            <label>
                <span>Tanggal</span>
                <input type="date" wire:model="liburTanggal" min="{{ now()->toDateString() }}" class="@error('liburTanggal') is-invalid @enderror">
                @error('liburTanggal')<small class="jp-err">{{ $message }}</small>@enderror
            </label>
            <label class="jp-grow">
                <span>Keterangan</span>
                <input type="text" wire:model="liburKeterangan" maxlength="120" placeholder="Contoh: Hari Natal" class="@error('liburKeterangan') is-invalid @enderror">
                @error('liburKeterangan')<small class="jp-err">{{ $message }}</small>@enderror
            </label>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="tambahLibur"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
        </form>

        @if ($libur->isEmpty())
            <div class="jp-empty"><i class="bi bi-calendar2-check"></i>Belum ada hari libur mendatang.</div>
        @else
            <ul class="jp-list">
                @foreach ($libur as $l)
                    <li wire:key="libur-{{ $l->id }}">
                        <span class="jp-date"><b class="numeral">{{ $l->tanggal->format('d') }}</b><small>{{ $l->tanggal->locale('id')->translatedFormat('M Y') }}</small></span>
                        <div class="jp-item-t">
                            <strong>{{ $l->keterangan }}</strong>
                            <small>{{ $l->tanggal->locale('id')->translatedFormat('l') }}@if ($l->tanggal->isToday()) &middot; <span class="text-danger fw-semibold">hari ini</span>@elseif ($l->tanggal->isTomorrow()) &middot; besok @else &middot; {{ (int) now()->startOfDay()->diffInDays($l->tanggal) }} hari lagi @endif</small>
                        </div>
                        <button type="button" class="jp-del" wire:click="hapusLibur({{ $l->id }})" wire:confirm="Hapus hari libur {{ $l->keterangan }}?" aria-label="Hapus {{ $l->keterangan }}"><i class="bi bi-trash3"></i></button>
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($liburLewat > 0)<p class="jp-foot">{{ $liburLewat }} hari libur yang sudah lewat disembunyikan.</p>@endif
    </section>

    {{-- ============ BAR SIMPAN ============ --}}
    <div class="aj-savebar">
        <span class="aj-savebar-text"><i class="bi bi-info-circle me-1"></i>Jam buka dan saklar baru berlaku setelah disimpan. Hari libur tersimpan langsung.</span>
        <button type="button" class="btn btn-primary" wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan">
            <span wire:loading.remove wire:target="simpan"><i class="bi bi-check2-circle me-1"></i>Simpan jadwal</span>
            <span wire:loading wire:target="simpan"><span class="spinner-dot"></span>Menyimpan</span>
        </button>
    </div>
</div>
