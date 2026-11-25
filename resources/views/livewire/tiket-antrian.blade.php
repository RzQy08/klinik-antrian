@php
    $st = $antrian->status;
    $langkah = ['Terdaftar', 'Menunggu', 'Dipanggil', 'Dilayani', 'Selesai'];
    $posisi = ['menunggu' => 1, 'dipanggil' => 2, 'telat' => 2, 'dilayani' => 3, 'selesai' => 4, 'tidak_hadir' => 1][$st] ?? 1;
    $label = ['menunggu' => 'Menunggu giliran', 'dipanggil' => 'Giliran Anda', 'telat' => 'Terlambat', 'dilayani' => 'Sedang dilayani', 'selesai' => 'Selesai', 'tidak_hadir' => 'Tidak hadir'][$st] ?? $st;
    $tanggal = $antrian->waktu_daftar->locale('id')->translatedFormat('l, j F Y');
@endphp
<div class="tk" data-status="{{ $st }}" @if ($masihBerjalan) wire:poll.10s.visible @endif>
    <section class="tk-main">
        <div class="tk-state is-{{ $st }}" role="status">
            <span class="tk-state-dot" aria-hidden="true"></span>
            <div>
                @if ($st === 'dipanggil')
                    <b>Giliran Anda! Silakan menuju {{ $antrian->poli->nama_poli }}@if ($antrian->dokter), {{ $antrian->dokter->nama }}@endif.</b>
                    @if ($antrian->waktu_dipanggil)<small>Harap hadir sebelum pukul {{ $antrian->waktu_dipanggil->copy()->addMinutes((int) config('antrian.batas_telat_menit'))->format('H.i') }}.</small>@endif
                @elseif ($st === 'telat')
                    <b>Anda terlambat. Segera menuju {{ $antrian->poli->nama_poli }} dan temui petugas.</b>
                    @if ($antrian->waktu_telat)<small>Tanpa kabar sampai pukul {{ $antrian->waktu_telat->copy()->addMinutes((int) config('antrian.batas_tidak_hadir_menit'))->format('H.i') }}, nomor ini ditandai tidak hadir.</small>@endif
                @elseif ($st === 'dilayani')
                    <b>Anda sedang dilayani.</b>
                @elseif ($st === 'selesai')
                    <b>Pelayanan selesai. Terima kasih.</b>
                @elseif ($st === 'tidak_hadir')
                    <b>Anda ditandai tidak hadir.</b><small>Anda boleh mendaftar lagi dengan NIK yang sama.</small>
                @else
                    <b>Menunggu giliran</b><small>Halaman ini memperbarui diri otomatis.</small>
                @endif
            </div>
            @if ($st === 'tidak_hadir')<a href="{{ route('antrian.create') }}" class="tk-state-btn">Daftar lagi</a>@endif
        </div>

        <article class="tk-card">
            <div class="tk-card-head">
                <span class="tk-chip">{{ $label }}</span>
                <p>Tiket antrian {{ $antrian->poli->nama_poli }}</p>
                <div class="tk-no numeral" aria-label="Nomor antrian {{ $antrian->nomor_antrian }}">{{ $antrian->nomor_antrian }}</div>
                <small>Simpan nomor ini sampai dipanggil</small>
            </div>
            <div class="tk-cut" aria-hidden="true"><span></span><span></span></div>
            <dl class="tk-rows">
                <div><dt>Atas nama</dt><dd>{{ $antrian->pasien->nama_samar }}</dd></div>
                <div><dt>Tanggal daftar</dt><dd>{{ $tanggal }}</dd></div>
                <div><dt>Jam daftar</dt><dd>{{ $antrian->waktu_daftar->format('H.i') }} WIB</dd></div>
            </dl>
        </article>
    </section>

    <aside class="tk-side">
        <div class="tk-panel">
            <h2>Perjalanan antrian</h2>
            <ol class="tk-steps" @if ($st === 'tidak_hadir') data-gagal @endif>
                @foreach ($langkah as $i => $nama)
                    <li @class(['is-done' => $i < $posisi, 'is-now' => $i === $posisi])><span class="tk-step-dot">@if ($i < $posisi)<i class="bi bi-check-lg" aria-hidden="true"></i>@endif</span><small>{{ $nama }}</small></li>
                @endforeach
            </ol>

            @if ($st === 'menunggu')
                <div class="tk-stats">
                    <div><small>Di depan Anda</small><b class="numeral">{{ $posisiDidepan }}</b><span>orang</span></div>
                    <div><small>Perkiraan tunggu</small>
                        @if ($antrian->estimasi_tunggu_detik !== null)
                            <b class="numeral">&plusmn;{{ max(1, round($antrian->estimasi_tunggu_detik / 60)) }}</b><span>menit</span>
                        @else
                            <b class="numeral">-</b><span>belum tersedia</span>
                        @endif
                    </div>
                </div>
                @if ($antrian->estimasi_tunggu_detik === null)
                    <p class="tk-note">Perkiraan belum bisa dihitung karena poli sedang sangat padat. Petugas tetap memanggil sesuai urutan nomor.</p>
                @endif
            @endif
        </div>

        <div class="tk-panel">
            <h2>Simpan kartu</h2>
            <div class="tk-aksi">
                <button type="button" class="tk-btn tk-btn-utama" id="tiketUnduh"><i class="bi bi-download" aria-hidden="true"></i>Unduh kartu antrian</button>
                <button type="button" class="tk-btn" id="tiketSalin"><i class="bi bi-link-45deg" aria-hidden="true"></i>Salin tautan tiket</button>
            </div>
            <p class="tk-note"><i class="bi bi-phone" aria-hidden="true"></i>Tiket tersimpan di perangkat ini sampai antrian selesai. Kalau ponsel bermasalah, buka lagi lewat <a href="{{ route('antrian.cek') }}">Cek tiket</a> dengan tautan di kartu.</p>
            <a href="{{ route('antrian.display', $antrian->poli_id) }}" class="tk-link"><i class="bi bi-tv" aria-hidden="true"></i>Lihat layar antrian {{ $antrian->poli->nama_poli }}<i class="bi bi-arrow-right-short" aria-hidden="true"></i></a>
        </div>
    </aside>

    <div id="tiketData" hidden
         data-kode="{{ $antrian->kode_tiket }}"
         data-nomor="{{ $antrian->nomor_antrian }}"
         data-poli="{{ $antrian->poli->nama_poli }}"
         data-nama="{{ $antrian->pasien->nama_samar }}"
         data-tanggal="{{ $tanggal }}"
         data-tgl="{{ $antrian->tanggal->toDateString() }}"
         data-jam="{{ $antrian->waktu_daftar->format('H.i') }}"
         data-rs="{{ config('rumahsakit.nama_lengkap') }}"
         data-url="{{ route('antrian.tiket', $antrian->kode_tiket) }}"
         data-status-url="{{ route('antrian.status-tiket', $antrian->kode_tiket) }}"
         data-selesai="{{ in_array($st, ['selesai', 'tidak_hadir'], true) ? '1' : '0' }}"></div>
</div>
