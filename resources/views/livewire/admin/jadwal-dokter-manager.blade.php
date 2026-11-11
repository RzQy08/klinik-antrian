<div class="ap jp">

    <div class="ap-loading" wire:loading.delay wire:target="tambahCuti,hapusCuti" aria-hidden="true"></div>

    @if ($pesan)
        <div wire:key="toast-{{ $pesanId }}" x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 5000)" x-show="tampil" x-transition.opacity.duration.250ms
             class="ap-toast ap-toast-{{ $tipePesan }}" role="status" aria-live="polite">
            <i class="bi bi-{{ $tipePesan === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' }}"></i>
            <span>{{ $pesan }}</span>
            <button type="button" @click="tampil = false" aria-label="Tutup pemberitahuan"><i class="bi bi-x-lg"></i></button>
        </div>
    @endif

    {{-- ============ TABEL MINGGUAN ============ --}}
    <section class="jp-card" aria-labelledby="judulMatriks">
        <header class="jp-card-head">
            <div>
                <h2 id="judulMatriks">Jadwal praktik mingguan</h2>
                <p>Pilih dokter untuk mengubah hari, jam, dan kuotanya. Hari ini ditandai.</p>
            </div>
        </header>

        <div class="jp-scroll">
            <table class="jp-matrix">
                <thead>
                    <tr>
                        <th scope="col">Dokter</th>
                        @foreach (\App\Models\JadwalRumahSakit::HARI as $k => $label)
                            <th scope="col" class="{{ $k === $hariIni ? 'is-today' : '' }}">{{ mb_substr($label, 0, 3) }}</th>
                        @endforeach
                        <th scope="col"><span class="visually-hidden">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dokters as $d)
                        <tr wire:key="dok-{{ $d->id }}" class="{{ $d->aktif ? '' : 'is-off' }}">
                            <th scope="row">
                                <a href="{{ route('admin.dokter.jadwal.index', $d) }}">{{ $d->nama }}</a>
                                <small>{{ $d->poli?->nama_poli }}@unless ($d->aktif) &middot; nonaktif @endunless
                                    @if (in_array($d->id, $sedangCuti)) &middot; <span class="jp-chip-cuti">cuti</span>@endif</small>
                            </th>
                            @foreach (\App\Models\JadwalRumahSakit::HARI as $k => $label)
                                @php $j = $jadwal->get($d->id)?->get($k); @endphp
                                <td class="{{ $k === $hariIni ? 'is-today' : '' }}" data-label="{{ $label }}">
                                    @if ($j)
                                        <span class="jp-slot"><b>{{ substr($j->jam_mulai, 0, 5) }}</b><i>{{ substr($j->jam_selesai, 0, 5) }}</i><em>{{ $j->kuota }} pasien</em></span>
                                    @else
                                        <span class="jp-none" aria-label="Tidak praktik">&ndash;</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="jp-act"><a href="{{ route('admin.dokter.jadwal.index', $d) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Atur</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="jp-empty">Belum ada dokter. Tambahkan dokter lebih dulu di menu Dokter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- ============ CUTI DOKTER ============ --}}
    <section class="jp-card" aria-labelledby="judulCuti">
        <header class="jp-card-head">
            <div>
                <h2 id="judulCuti">Cuti dan dokter berhalangan</h2>
                <p>Selama rentang tanggal ini, dokter tidak tampil sebagai dokter bertugas dan tidak bisa dipilih saat memanggil pasien.</p>
            </div>
        </header>

        <form class="jp-form" wire:submit="tambahCuti">
            <label class="jp-grow">
                <span>Dokter</span>
                <select wire:model="dokterId" class="@error('dokterId') is-invalid @enderror">
                    <option value="">Pilih dokter</option>
                    @foreach ($dokters->where('aktif', true) as $d)
                        <option value="{{ $d->id }}">{{ $d->nama }} ({{ $d->poli?->nama_poli }})</option>
                    @endforeach
                </select>
                @error('dokterId')<small class="jp-err">{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Mulai</span>
                <input type="date" wire:model="mulai" min="{{ now()->toDateString() }}" class="@error('mulai') is-invalid @enderror">
                @error('mulai')<small class="jp-err">{{ $message }}</small>@enderror
            </label>
            <label>
                <span>Sampai</span>
                <input type="date" wire:model="selesai" min="{{ now()->toDateString() }}" class="@error('selesai') is-invalid @enderror">
                @error('selesai')<small class="jp-err">{{ $message }}</small>@enderror
            </label>
            <label class="jp-grow">
                <span>Alasan (opsional)</span>
                <input type="text" wire:model="alasan" maxlength="120" placeholder="Contoh: Seminar, cuti tahunan">
            </label>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="tambahCuti"><i class="bi bi-plus-lg me-1"></i>Catat cuti</button>
        </form>

        @if ($cuti->isEmpty())
            <div class="jp-empty"><i class="bi bi-person-check"></i>Tidak ada cuti yang tercatat.</div>
        @else
            <ul class="jp-list">
                @foreach ($cuti as $c)
                    <li wire:key="cuti-{{ $c->id }}">
                        <span class="jp-date"><b class="numeral">{{ $c->tanggal_mulai->format('d') }}</b><small>{{ $c->tanggal_mulai->locale('id')->translatedFormat('M Y') }}</small></span>
                        <div class="jp-item-t">
                            <strong>{{ $c->dokter?->nama }}</strong>
                            <small>
                                @if ($c->tanggal_mulai->isSameDay($c->tanggal_selesai))
                                    {{ $c->tanggal_mulai->locale('id')->translatedFormat('l, j F Y') }}
                                @else
                                    {{ $c->tanggal_mulai->locale('id')->translatedFormat('j F') }} sampai {{ $c->tanggal_selesai->locale('id')->translatedFormat('j F Y') }}
                                @endif
                                @if ($c->alasan) &middot; {{ $c->alasan }} @endif
                                @if ($c->tanggal_mulai->isPast() || $c->tanggal_mulai->isToday()) &middot; <span class="jp-chip-cuti">sedang cuti</span>@endif
                            </small>
                        </div>
                        <button type="button" class="jp-del" wire:click="hapusCuti({{ $c->id }})" wire:confirm="Hapus cuti {{ $c->dokter?->nama }}?" aria-label="Hapus cuti {{ $c->dokter?->nama }}"><i class="bi bi-trash3"></i></button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>
