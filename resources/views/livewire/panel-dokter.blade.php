<div class="dp" wire:poll.5s.visible="segarkan"
     x-data="{
        suara: localStorage.getItem('dpSuara') === '1', ctx: null,
        ubah() { this.suara = ! this.suara; localStorage.setItem('dpSuara', this.suara ? '1' : '0'); if (this.suara) this.bunyi(); },
        bunyi() {
            if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
            if (! this.suara) return;
            try {
                this.ctx = this.ctx || new (window.AudioContext || window.webkitAudioContext)();
                [0, 0.24].forEach(t => {
                    const o = this.ctx.createOscillator(), g = this.ctx.createGain(), w = this.ctx.currentTime + t;
                    o.frequency.value = 880; g.gain.setValueAtTime(0.18, w); g.gain.exponentialRampToValueAtTime(0.001, w + 0.2);
                    o.connect(g); g.connect(this.ctx.destination); o.start(w); o.stop(w + 0.22);
                });
            } catch (e) {}
        }
     }"
     @ada-panggilan.window="bunyi()">

    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- pembaca layar: ringkasan keadaan yang diumumkan setiap berubah --}}
    <p class="visually-hidden" role="status" aria-live="polite">
        @if ($fokusDilayani) Sedang melayani nomor {{ $fokus->nomor_antrian }}. @endif
        {{ $lainnya->count() + (($fokus && ! $fokusDilayani) ? 1 : 0) }} pasien dipanggil menunggu dimulai.
    </p>

    <div class="dp-bar">
        <span class="dp-live"><span class="dp-live-dot"></span>Langsung</span>
        <button type="button" class="dp-sound" @click="ubah()" :aria-pressed="suara ? 'true' : 'false'">
            <i class="bi" :class="suara ? 'bi-volume-up-fill' : 'bi-volume-mute'"></i>
            <span x-text="suara ? 'Bunyi panggilan aktif' : 'Bunyi panggilan mati'"></span>
        </button>
    </div>

    <div class="dp-grid">

        {{-- ============ FOKUS: HAL YANG HARUS DIKERJAKAN SEKARANG ============ --}}
        <section class="dp-main" aria-label="Pasien saat ini">
            @if ($fokus)
                @php
                    $umur = $fokus->pasien?->tanggal_lahir?->age;
                    $jk = ['L' => 'Laki-laki', 'P' => 'Perempuan'][$fokus->pasien?->jenis_kelamin] ?? null;
                    $detikBerjalan = $fokusDilayani ? max(0, (int) now()->diffInSeconds($fokus->waktu_mulai_dilayani)) : 0;
                @endphp

                <article class="dp-now {{ $fokusDilayani ? 'is-serving' : 'is-called' }}" wire:key="fokus-{{ $fokus->id }}-{{ $fokus->status }}">
                    <header class="dp-now-top">
                        <span class="dp-chip">
                            <i class="bi bi-{{ $fokusDilayani ? 'activity' : 'megaphone-fill' }}"></i>
                            {{ $fokusDilayani ? 'Sedang dilayani' : 'Pasien berikutnya, sudah dipanggil' }}
                        </span>

                        @if ($fokusDilayani)
                            <span class="dp-timer" x-data="{ detik: {{ $detikBerjalan }} }" x-init="setInterval(() => detik++, 1000)" role="timer" aria-label="Lama pelayanan">
                                <i class="bi bi-stopwatch"></i>
                                <span class="numeral" x-text="String(Math.floor(detik / 3600)).padStart(2, '0') + ':' + String(Math.floor(detik % 3600 / 60)).padStart(2, '0') + ':' + String(detik % 60).padStart(2, '0')">00:00:00</span>
                            </span>
                        @else
                            <span class="dp-timer"><i class="bi bi-clock"></i>Dipanggil {{ $fokus->waktu_dipanggil?->format('H.i') }}@if ($fokus->jumlah_panggil > 1) &middot; panggilan ke-{{ $fokus->jumlah_panggil }}@endif</span>
                        @endif
                    </header>

                    <div class="dp-now-no numeral">{{ $fokus->nomor_antrian }}</div>
                    <h2 class="dp-now-name">{{ $fokus->pasien?->nama }}</h2>

                    <ul class="dp-now-meta">
                        @if ($umur !== null)<li><i class="bi bi-person"></i>{{ $umur }} tahun</li>@endif
                        @if ($jk)<li><i class="bi bi-gender-ambiguous"></i>{{ $jk }}</li>@endif
                        @if ($fokus->pasien?->no_rm)<li><i class="bi bi-folder2-open"></i>{{ $fokus->pasien->no_rm }}</li>@endif
                        @if ($fokus->limpahan)<li><i class="bi bi-arrow-return-right"></i>Limpahan {{ $fokus->nomor_lama }}</li>@endif
                    </ul>

                    <div class="dp-now-act">
                        @if ($fokusDilayani)
                            <button type="button" class="dp-btn dp-btn-done" wire:click="selesai({{ $fokus->id }})" wire:loading.attr="disabled" wire:target="selesai">
                                <i class="bi bi-check2-circle"></i>Selesaikan pelayanan
                            </button>
                        @else
                            <button type="button" class="dp-btn dp-btn-go" wire:click="mulai({{ $fokus->id }})" wire:loading.attr="disabled" wire:target="mulai">
                                <i class="bi bi-play-circle-fill"></i>Mulai layani
                            </button>
                            <div class="dp-skip" x-data="{ yakin: false }">
                                <button type="button" class="dp-btn dp-btn-ghost" x-show="! yakin" @click="yakin = true"><i class="bi bi-person-x"></i>Tidak hadir</button>
                                <div class="dp-confirm" x-show="yakin" x-cloak>
                                    <span>Tandai tidak hadir?</span>
                                    <button type="button" class="dp-btn dp-btn-warn" wire:click="tidakHadir({{ $fokus->id }})">Ya</button>
                                    <button type="button" class="dp-btn dp-btn-ghost" @click="yakin = false">Batal</button>
                                </div>
                            </div>
                        @endif
                    </div>
                </article>
            @else
                <article class="dp-idle">
                    <span class="dp-idle-ico"><i class="bi bi-cup-hot"></i></span>
                    <h2>Belum ada pasien untuk Anda</h2>
                    <span class="dp-idle-count"><b class="numeral">{{ $menungguDiPoli }}</b> pasien menunggu di {{ $dokter->poli?->nama_poli }}</span>
                </article>
            @endif

            {{-- pasien telat sementara --}}
            @if ($telat->isNotEmpty())
                <section class="dp-card dp-card-telat">
                    <header class="dp-card-head"><h3>Telat</h3><span class="dp-count">{{ $telat->count() }}</span></header>
                    <ul class="dp-list">
                        @foreach ($telat as $a)
                            <li wire:key="telat-{{ $a->id }}" x-data="{ yakin: false }">
                                <span class="numeral dp-list-no">{{ $a->nomor_antrian }}</span>
                                <div class="dp-list-body">
                                    <div class="fw-semibold text-truncate">{{ $a->pasien?->nama }}</div>
                                    <div class="small text-body-secondary">Telat sejak {{ $a->waktu_telat?->format('H.i') }}</div>
                                </div>
                                <div class="dp-list-act">
                                    <button type="button" class="btn btn-primary" wire:click="mulai({{ $a->id }})" @disabled($sedangSekarang)>Mulai</button>
                                    <button type="button" class="btn btn-outline-secondary" x-show="! yakin" @click="yakin = true" aria-label="Tandai {{ $a->nomor_antrian }} tidak hadir"><i class="bi bi-person-x"></i></button>
                                    <button type="button" class="btn btn-warning" x-show="yakin" x-cloak wire:click="tidakHadir({{ $a->id }})">Yakin tidak hadir</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- pasien lain yang sudah dipanggil --}}
            @if ($lainnya->isNotEmpty())
                <section class="dp-card">
                    <header class="dp-card-head"><h3>Sudah dipanggil, menunggu dimulai</h3><span class="dp-count">{{ $lainnya->count() }}</span></header>
                    <ul class="dp-list">
                        @foreach ($lainnya as $a)
                            <li wire:key="dipanggil-{{ $a->id }}" x-data="{ yakin: false }">
                                <span class="numeral dp-list-no">{{ $a->nomor_antrian }}</span>
                                <div class="dp-list-body">
                                    <div class="fw-semibold text-truncate">{{ $a->pasien?->nama }}</div>
                                    <div class="small text-body-secondary">Dipanggil {{ $a->waktu_dipanggil?->format('H.i') }}@if ($a->jumlah_panggil > 1) &middot; ke-{{ $a->jumlah_panggil }}@endif</div>
                                </div>
                                <div class="dp-list-act">
                                    <button type="button" class="btn btn-primary" wire:click="mulai({{ $a->id }})" @disabled($sedangSekarang) @if ($sedangSekarang) title="Selesaikan pasien yang sedang dilayani dulu" @endif>Mulai</button>
                                    <button type="button" class="btn btn-outline-secondary" x-show="! yakin" @click="yakin = true" aria-label="Tandai {{ $a->nomor_antrian }} tidak hadir"><i class="bi bi-person-x"></i></button>
                                    <button type="button" class="btn btn-warning" x-show="yakin" x-cloak wire:click="tidakHadir({{ $a->id }})">Yakin tidak hadir</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </section>

        {{-- ============ RINGKASAN ============ --}}
        <aside class="dp-side" aria-label="Ringkasan hari ini">

            <section class="dp-card">
                <header class="dp-card-head"><h3>Jadwal hari ini</h3></header>
                @if ($jadwal)
                    <div class="dp-quota">
                        <div class="dp-quota-nums"><span class="numeral">{{ $selesaiHariIni }}</span><small>dari kuota {{ $jadwal->kuota }} pasien</small></div>
                        <div class="bar" role="progressbar" aria-valuenow="{{ $selesaiHariIni }}" aria-valuemin="0" aria-valuemax="{{ $jadwal->kuota }}" aria-label="Pasien terlayani terhadap kuota"><span class="bg-primary" style="width: {{ $kuotaPersen }}%"></span></div>
                        <div class="dp-quota-time"><i class="bi bi-clock me-1"></i>Praktik {{ substr($jadwal->jam_mulai, 0, 5) }} sampai {{ substr($jadwal->jam_selesai, 0, 5) }}</div>
                    </div>
                @else
                    <p class="dp-muted mb-2">Tidak ada jadwal praktik hari ini.</p>
                    <div class="dp-quota-nums"><span class="numeral">{{ $selesaiHariIni }}</span><small>pasien selesai hari ini</small></div>
                @endif
            </section>

            <section class="dp-stats">
                <div><span class="numeral">{{ $rataMenit ?? '-' }}</span><small>{{ $rataMenit !== null ? 'menit rata-rata' : 'rata-rata' }}<br>per pasien</small></div>
                <div><span class="numeral">{{ $menungguDiPoli }}</span><small>menunggu<br>di poli</small></div>
                <div><span class="numeral">{{ $tidakHadirHariIni }}</span><small>tidak<br>hadir</small></div>
            </section>

            <section class="dp-card">
                <header class="dp-card-head"><h3>Antrian berikutnya di poli</h3></header>
                @forelse ($berikutnya as $a)
                    <div class="dp-next" wire:key="next-{{ $a->id }}">
                        <span class="numeral">{{ $a->nomor_antrian }}</span>
                        <span class="text-truncate">{{ $a->pasien?->nama }}</span>
                        @if ($a->tanggal_asal)<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Limpahan</span>@endif
                    </div>
                @empty
                    <p class="dp-muted mb-0">&mdash;</p>
                @endforelse
                @if ($menungguDiPoli > $berikutnya->count())
                    <p class="dp-muted small mt-2 mb-0">dan {{ $menungguDiPoli - $berikutnya->count() }} lainnya</p>
                @endif
            </section>

            {{-- ============ JADWAL SAYA: lihat jadwal mingguan, ajukan perubahan ============ --}}
            <section class="dp-card dp-jadwalku" x-data="{ ajukan: null }">
                <header class="dp-card-head"><h3>Jadwal saya</h3></header>

                <ul class="dp-jk-minggu">
                    @foreach ($hariLabel as $kunci => $label)
                        @php $j = $jadwalMingguan->get($kunci); $p = $permintaanMenunggu->get($kunci); @endphp
                        <li class="{{ $kunci === $namaHariIni ? 'is-today' : '' }}">
                            <span class="dp-jk-hari">{{ mb_substr($label, 0, 3) }}</span>
                            @if ($j)
                                <span class="dp-jk-jam numeral">{{ substr($j->jam_mulai, 0, 5) }}&ndash;{{ substr($j->jam_selesai, 0, 5) }}</span>
                                <span class="dp-jk-kuota">{{ $j->kuota }} psn</span>
                            @else
                                <span class="dp-jk-libur">Libur</span>
                            @endif

                            @if ($p)
                                <span class="dp-jk-pending" title="Menunggu persetujuan admin: {{ substr($p->jam_mulai_baru, 0, 5) }}&ndash;{{ substr($p->jam_selesai_baru, 0, 5) }}">
                                    <i class="bi bi-hourglass-split"></i>Menunggu
                                    <button type="button" class="dp-jk-batal" @click="if (confirm('Batalkan permintaan untuk {{ $label }}?')) document.getElementById('batal-{{ $p->id }}').submit()" aria-label="Batalkan permintaan {{ $label }}"><i class="bi bi-x"></i></button>
                                </span>
                                <form id="batal-{{ $p->id }}" method="POST" action="{{ route('permintaan-jadwal.batalkan', $p) }}" class="visually-hidden">@csrf @method('DELETE')</form>
                            @else
                                <button type="button" class="dp-jk-ajukan" @click="ajukan = ajukan === '{{ $kunci }}' ? null : '{{ $kunci }}'" :aria-expanded="ajukan === '{{ $kunci }}'">
                                    <i class="bi bi-pencil-square"></i>Ajukan ganti
                                </button>
                            @endif
                        </li>

                        <li class="dp-jk-form" x-show="ajukan === '{{ $kunci }}'" x-collapse x-cloak>
                            <form method="POST" action="{{ route('permintaan-jadwal.store') }}">
                                @csrf
                                <input type="hidden" name="hari" value="{{ $kunci }}">
                                <p class="dp-jk-form-t">Ajukan jadwal {{ $label }}. Jadwal lama tetap berlaku sampai admin menyetujui.</p>
                                <div class="dp-jk-form-row">
                                    <label>Mulai<input type="time" name="jam_mulai_baru" value="{{ $j?->jam_mulai ? substr($j->jam_mulai, 0, 5) : '08:00' }}" required></label>
                                    <label>Selesai<input type="time" name="jam_selesai_baru" value="{{ $j?->jam_selesai ? substr($j->jam_selesai, 0, 5) : '16:00' }}" required></label>
                                    <label>Kuota<input type="number" name="kuota_baru" min="1" max="200" value="{{ $j?->kuota ?? 20 }}" required></label>
                                </div>
                                <label class="dp-jk-alasan">Alasan (opsional)<input type="text" name="alasan" maxlength="180" placeholder="Contoh: jadwal bentrok dengan praktik di tempat lain"></label>
                                <div class="dp-jk-form-act">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Kirim ke admin</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" @click="ajukan = null">Batal</button>
                                </div>
                            </form>
                        </li>
                    @endforeach
                </ul>

                @if ($permintaanRiwayat->isNotEmpty())
                    <details class="dp-jk-riwayat">
                        <summary>Riwayat permintaan</summary>
                        <ul>
                            @foreach ($permintaanRiwayat as $r)
                                <li>
                                    <span>{{ $hariLabel[$r->hari] ?? $r->hari }}, {{ substr($r->jam_mulai_baru, 0, 5) }}&ndash;{{ substr($r->jam_selesai_baru, 0, 5) }}</span>
                                    <span class="dp-jk-status is-{{ $r->status }}">{{ $r->status === 'disetujui' ? 'Disetujui' : 'Ditolak' }}</span>
                                    @if ($r->catatan_admin)<small>{{ $r->catatan_admin }}</small>@endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </section>

            <section class="dp-card">
                <header class="dp-card-head"><h3>Selesai hari ini</h3></header>
                @forelse ($selesaiTerakhir as $a)
                    <div class="dp-done" wire:key="selesai-{{ $a->id }}">
                        <span class="numeral">{{ $a->nomor_antrian }}</span>
                        <span class="text-truncate">{{ $a->pasien?->nama }}</span>
                        <small>{{ $a->lama_layanan_detik !== null ? max(1, round($a->lama_layanan_detik / 60)).' mnt' : '-' }}</small>
                    </div>
                @empty
                    <p class="dp-muted mb-0">&mdash;</p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
