@php
    // Jumlah pasien menunggu per poli hari ini (satu query)
    $menungguPerPoli = \App\Models\Antrian::hariIni()->menunggu()
        ->selectRaw('poli_id, COUNT(*) as jumlah')->groupBy('poli_id')->pluck('jumlah', 'poli_id');

    $jamAktif = \App\Services\JamOperasional::aktif();
    $statusTeks = ! $jamAktif
        ? 'Antrian online aktif'
        : ($pendaftaranDibuka
            ? 'Dibuka sampai pukul '.\App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup'))
            : 'Pendaftaran ditutup');
@endphp
<x-public-layout title="Ambil nomor antrian">
    <div class="ab-page" data-buka="{{ $pendaftaranDibuka ? 1 : 0 }}">
        <form method="POST" action="{{ route('antrian.store') }}" id="formAmbil" class="ab-card">
            @csrf

            <fieldset class="ab-body" @disabled(! $pendaftaranDibuka)>
                <div class="ab-top">
                    <span class="ab-state {{ $pendaftaranDibuka ? '' : 'is-off' }}"><i aria-hidden="true"></i>{{ $statusTeks }}</span>
                    <a href="{{ route('beranda') }}" class="ab-close" aria-label="Tutup dan kembali ke beranda"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                </div>

                <h1 class="ab-title">Ambil nomor antrian</h1>

                @include('partials.errors')
                @if (session('error'))
                    <div class="alert alert-danger py-2" role="alert">{{ session('error') }}</div>
                @endif

                {{-- Poli --}}
                <div class="ab-chips @error('poli_id') is-invalid @enderror" role="radiogroup" aria-label="Poli tujuan">
                    @foreach ($polis as $poli)
                        <div class="ab-chip-wrap">
                            <input type="radio" class="ab-chip-input" name="poli_id" id="poli-{{ $poli->id }}" value="{{ $poli->id }}"
                                   data-nama="{{ $poli->nama_poli }}" data-menunggu="{{ (int) ($menungguPerPoli[$poli->id] ?? 0) }}"
                                   required @checked(old('poli_id') == $poli->id)>
                            <label class="ab-chip" for="poli-{{ $poli->id }}">
                                <span class="ab-chip-k"><b>{{ $poli->kode_poli }}</b><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                {{ $poli->nama_poli }}
                            </label>
                        </div>
                    @endforeach
                    @if ($polis->isEmpty())
                        <p class="ab-empty">Belum ada poli aktif. Hubungi petugas loket.</p>
                    @endif
                </div>

                {{-- Data pasien --}}
                <div class="ab-grid">
                    <label class="ab-field @if (old('nik')) is-float @endif @error('nik') is-invalid @enderror">
                        <span class="ab-lab">NIK*</span>
                        <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" autocomplete="off" required
                               value="{{ old('nik') }}" placeholder="16 digit angka">
                        <span class="ab-count" id="abCount" aria-live="polite">0/16</span>
                    </label>

                    <label class="ab-field @if (old('nama')) is-float @endif @error('nama') is-invalid @enderror">
                        <span class="ab-lab">Nama lengkap*</span>
                        <input id="nama" name="nama" type="text" autocomplete="name" maxlength="255" required value="{{ old('nama') }}" placeholder="Sesuai KTP">
                    </label>

                    <label class="ab-field @if (old('no_hp')) is-float @endif @error('no_hp') is-invalid @enderror">
                        <span class="ab-lab">No. WhatsApp</span>
                        <input id="no_hp" name="no_hp" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" value="{{ old('no_hp') }}" placeholder="081234567890">
                    </label>

                    <label class="ab-field is-float @error('tanggal_lahir') is-invalid @enderror">
                        <span class="ab-lab">Tanggal lahir</span>
                        <input id="tanggal_lahir" name="tanggal_lahir" type="date" max="{{ now()->toDateString() }}" value="{{ old('tanggal_lahir') }}">
                    </label>

                    <div class="ab-field ab-seg-field is-float ab-full @error('jenis_kelamin') is-invalid @enderror" role="radiogroup" aria-labelledby="abJkLab">
                        <span class="ab-lab" id="abJkLab">Jenis kelamin</span>
                        <div class="ab-seg">
                            @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $nilai => $teks)
                                <label class="ab-opt">
                                    <input type="radio" name="jenis_kelamin" value="{{ $nilai }}" @checked(old('jenis_kelamin') === $nilai)>
                                    <span>{{ $teks }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </fieldset>

            <footer class="ab-foot">
                <div class="ab-foot-info">
                    @if ($pendaftaranDibuka)
                        <div class="ab-foot-main" id="abFootMain" aria-live="polite">Pilih poli untuk mulai</div>
                        <div class="ab-foot-sub">Isi WhatsApp bila ingin dikabari saat nomor dipanggil.</div>
                    @else
                        <div class="ab-foot-main">Pendaftaran ditutup</div>
                        <div class="ab-foot-sub">{{ $pesanTutup }}</div>
                    @endif
                </div>
                <button type="submit" id="abSubmit" class="ab-submit" @disabled(! $pendaftaranDibuka)>
                    <span>Ambil nomor</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </footer>
        </form>
    </div>

    <script>
    (function () {
        var form = document.getElementById('formAmbil');
        if (!form) return;

        var $$ = function (s) { return Array.prototype.slice.call(form.querySelectorAll(s)); };
        var nik = document.getElementById('nik'), nama = document.getElementById('nama');
        var submit = document.getElementById('abSubmit'), main = document.getElementById('abFootMain');
        var buka = form.closest('.ab-page').dataset.buka === '1';
        var poli = function () { return form.querySelector('input[name=poli_id]:checked'); };

        function melayang(el) {
            var f = el.closest('.ab-field');
            if (f && el.type !== 'date') f.classList.toggle('is-float', el.value !== '');
        }

        function sync() {
            var p = poli(), n = nik.value.length, c = document.getElementById('abCount');
            c.textContent = n + '/16';
            c.classList.toggle('is-ok', n === 16);

            if (main) {
                if (!p) {
                    main.textContent = 'Pilih poli untuk mulai';
                } else {
                    var m = parseInt(p.dataset.menunggu, 10) || 0;
                    main.textContent = p.dataset.nama + ' \u00b7 ' + (m === 0 ? 'belum ada antrian' : m + ' orang menunggu');
                }
            }

            submit.disabled = !(buka && p && n === 16 && nama.value.trim() !== '');
        }

        $$('.ab-field input').forEach(function (el) {
            melayang(el);
            el.addEventListener('input', function () { melayang(el); el.closest('.ab-field').classList.remove('is-invalid'); sync(); });
            el.addEventListener('change', function () { melayang(el); sync(); });
        });

        nik.addEventListener('input', function () {
            var bersih = nik.value.replace(/\D/g, '').slice(0, 16);
            if (bersih !== nik.value) { nik.value = bersih; melayang(nik); }
            sync();
        });
        nik.addEventListener('blur', function () {
            if (nik.value.length > 0 && nik.value.length < 16) nik.closest('.ab-field').classList.add('is-invalid');
        });

        $$('input[name=poli_id]').forEach(function (r) {
            r.addEventListener('change', function () {
                form.querySelector('.ab-chips').classList.remove('is-invalid');
                sync();
                if (!nik.value) nik.focus({ preventScroll: true });
            });
        });

        // Jenis kelamin opsional: klik pilihan yang sama sekali lagi untuk mengosongkan
        var jk = $$('input[name=jenis_kelamin]');
        jk.forEach(function (r) {
            r.dataset.was = r.checked ? '1' : '0';
            r.addEventListener('click', function () {
                if (r.dataset.was === '1') { r.checked = false; r.dataset.was = '0'; return; }
                jk.forEach(function (x) { x.dataset.was = '0'; });
                r.dataset.was = '1';
            });
        });

        // ?poli=ID memilih poli langsung; kalau hanya ada satu poli, otomatis terpilih
        var semua = $$('input[name=poli_id]');
        if (!poli()) {
            var q = (new URLSearchParams(location.search).get('poli') || '').replace(/\D/g, '');
            var pilih = (q && form.querySelector('input[name=poli_id][value="' + q + '"]')) || (semua.length === 1 ? semua[0] : null);
            if (pilih) pilih.checked = true;
        }

        form.addEventListener('submit', function () {
            setTimeout(function () {
                submit.disabled = true;
                submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Memproses</span>';
            }, 0);
        });

        sync();
    })();
    </script>
</x-public-layout>
