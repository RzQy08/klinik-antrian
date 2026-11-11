<x-public-layout :full="true" :fit="true" title="Ambil antrian">
    @php
        $jamAktif = \App\Services\JamOperasional::aktif();
        $tutupHari = $jamAktif ? \App\Services\JamOperasional::alasanTutup() : null;
        $jamDaftar = $jamAktif && ! $tutupHari
            ? \App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam('pendaftaran_buka')).' - '.\App\Services\JamOperasional::tampil(\App\Services\JamOperasional::jam('pendaftaran_tutup'))
            : null;
        $pesanError = session('error');
        $errNik = $errors->first('nik');
        $errLain = collect($errors->all())->reject(fn ($e) => $e === $errNik)->values();

        // [judul, ikon]
        $langkah = [['Pilih poli', 'hospital'], ['Data diri', 'person-vcard'], ['Periksa', 'check2-all']];
    @endphp

    <div class="am-stage" id="amStage">
        <header class="am-top">
            <h1>Ambil nomor antrian</h1>
            <p>Selesai dalam 3 langkah</p>
        </header>

        {{-- Pemberitahuan: ditutup, NIK ditolak, isian belum lengkap --}}
        @if (! $pendaftaranDibuka || ($pesanError && $pesanError !== $pesanTutup) || $errNik || $errLain->isNotEmpty() || session('success'))
            <div class="am-alerts" id="amAlerts">
                @unless ($pendaftaranDibuka)
                    <div class="am-alert am-alert-warn" role="alert"><i class="bi bi-clock-history" aria-hidden="true"></i><div><b>Pendaftaran ditutup</b><span>{{ $pesanTutup }}</span></div></div>
                @endunless
                @if ($pesanError && $pesanError !== $pesanTutup)
                    <div class="am-alert am-alert-stop" role="alert"><i class="bi bi-person-lock" aria-hidden="true"></i><div><b>Nomor belum bisa dibuat</b><span>{{ $pesanError }}</span></div></div>
                @endif
                @if ($errNik)
                    <div class="am-alert am-alert-stop" role="alert"><i class="bi bi-person-lock" aria-hidden="true"></i><div><b>Periksa NIK</b><span>{{ $errNik }}</span></div></div>
                @endif
                @if ($errLain->isNotEmpty())
                    <div class="am-alert am-alert-stop" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i><div><b>Data belum lengkap</b><span>{{ $errLain->implode(' ') }}</span></div></div>
                @endif
                @if (session('success'))
                    <div class="am-alert am-alert-ok" role="status"><i class="bi bi-check2-circle" aria-hidden="true"></i><div><b>{{ session('success') }}</b></div></div>
                @endif
            </div>
        @endif

        <div class="am-console">
            {{-- Rel kiri: langkah, tiket mini, jam --}}
            <aside class="am-rail" aria-label="Langkah">
                <ol class="am-steps" id="amSteps">
                    @foreach ($langkah as $i => [$nama, $ikon])
                        <li class="am-step {{ $i === 0 ? 'is-active' : '' }}">
                            <button type="button" class="am-step-btn">
                                <span class="am-step-ico"><i class="bi bi-{{ $ikon }}" aria-hidden="true"></i><i class="bi bi-check-lg am-ck" aria-hidden="true"></i></span>
                                <span class="am-step-txt"><b>{{ $nama }}</b><small id="amHint{{ $i + 1 }}"></small></span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <article class="am-mini" aria-label="Pratinjau tiket">
                    <div class="am-mini-top">
                        <span class="am-mini-poli" id="tkPoli">Belum dipilih</span>
                        <span class="numeral am-mini-no"><span id="tkKode">?</span><span class="am-mini-dots" id="tkDots">&bull;&bull;&bull;</span></span>
                    </div>
                    <div class="am-mini-foot"><span>Di depan Anda</span><b id="tkDepan">-</b></div>
                </article>

                <div class="am-jam {{ $pendaftaranDibuka ? '' : 'is-off' }}">
                    <span class="am-dot"></span>
                    <span class="am-jam-t">
                        @if ($jamDaftar)
                            <b class="numeral">{{ $jamDaftar }}</b><small>jam daftar hari ini</small>
                        @elseif ($tutupHari)
                            <b>Tutup</b><small>{{ $tutupHari }}</small>
                        @else
                            <b>Buka</b><small>tanpa batas jam</small>
                        @endif
                    </span>
                </div>
            </aside>

            {{-- Kartu form --}}
            <form method="POST" action="{{ route('antrian.store') }}" class="am-card" id="formAmbil" novalidate>
                @csrf

                <div class="am-prog" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="33" id="amProg">
                    <span class="am-prog-bar"><i id="amBar"></i></span>
                    <em id="amPct">33%</em>
                </div>

                <fieldset class="am-body" @disabled(! $pendaftaranDibuka)>
                    {{-- Langkah 1 --}}
                    <section class="am-panel is-active" aria-labelledby="amT1">
                        <h2 class="am-panel-title" id="amT1">Pilih poli</h2>

                        <div class="am-poli-grid @error('poli_id') is-invalid @enderror" role="radiogroup" aria-label="Poli tujuan">
                            @foreach ($polis as $poli)
                                @php $menunggu = (int) ($poli->menunggu_count ?? 0); @endphp
                                <div class="am-poli-wrap">
                                    <input type="radio" class="am-poli-input" name="poli_id" id="poli-{{ $poli->id }}" value="{{ $poli->id }}"
                                           data-kode="{{ $poli->kode_poli }}" data-nama="{{ $poli->nama_poli }}" data-menunggu="{{ $menunggu }}"
                                           required @checked(old('poli_id') == $poli->id)>
                                    <label class="am-poli" for="poli-{{ $poli->id }}">
                                        <span class="kode">{{ $poli->kode_poli }}</span>
                                        <span class="am-poli-name">{{ $poli->nama_poli }}</span>
                                        <span class="am-poli-q {{ $menunggu > 10 ? 'is-busy' : '' }}" title="{{ $menunggu }} menunggu"><i class="bi bi-people" aria-hidden="true"></i>{{ $menunggu }}</span>
                                        <i class="bi bi-check-circle-fill am-poli-check" aria-hidden="true"></i>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('poli_id')<div class="am-err">{{ $message }}</div>@enderror
                        <div class="am-err" id="amPoliErr" role="alert" hidden>Pilih salah satu poli.</div>
                        @if ($polis->isEmpty())
                            <p class="am-empty">Belum ada poli aktif. Hubungi petugas loket.</p>
                        @endif
                    </section>

                    {{-- Langkah 2 --}}
                    <section class="am-panel" aria-labelledby="amT2">
                        <h2 class="am-panel-title" id="amT2">Data diri</h2>

                        <div class="am-fields">
                            <div class="am-f">
                                <label for="nik">NIK</label>
                                <div class="am-nik-wrap">
                                    <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" autocomplete="off" required
                                           class="am-in am-nik @error('nik') is-invalid @enderror" value="{{ old('nik') }}" placeholder="16 digit">
                                    <span class="am-count" id="amCount" aria-live="polite">0/16</span>
                                </div>
                                <span class="am-meter" aria-hidden="true"><i id="amMeter"></i></span>
                            </div>

                            <div class="am-f">
                                <label for="nama">Nama lengkap</label>
                                <input id="nama" name="nama" type="text" autocomplete="name" required maxlength="255" class="am-in @error('nama') is-invalid @enderror" value="{{ old('nama') }}">
                            </div>

                            <div class="am-f">
                                <label for="no_hp">WhatsApp <span class="am-opt">opsional</span></label>
                                <div class="am-hp">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                    <input id="no_hp" name="no_hp" type="tel" inputmode="tel" autocomplete="tel" maxlength="20" class="am-in @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            <div class="am-f">
                                <label for="tanggal_lahir">Tanggal lahir <span class="am-opt">opsional</span></label>
                                <input id="tanggal_lahir" name="tanggal_lahir" type="date" max="{{ now()->toDateString() }}" class="am-in @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir') }}">
                            </div>

                            <div class="am-f am-f-jk">
                                <span class="am-lbl">Jenis kelamin <span class="am-opt">opsional</span></span>
                                <div class="am-seg">
                                    @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $nilai => $teks)
                                        <input type="radio" name="jenis_kelamin" id="jk-{{ $nilai }}" value="{{ $nilai }}" autocomplete="off" @checked(old('jenis_kelamin') === $nilai)>
                                        <label for="jk-{{ $nilai }}">{{ $teks }}</label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- Langkah 3 --}}
                    <section class="am-panel" aria-labelledby="amT3">
                        <h2 class="am-panel-title" id="amT3">Periksa data</h2>
                        <dl class="am-review">
                            <div><dt><i class="bi bi-hospital" aria-hidden="true"></i>Poli</dt><dd id="rvPoli">-</dd></div>
                            <div><dt><i class="bi bi-person-vcard" aria-hidden="true"></i>NIK</dt><dd id="rvNik" class="numeral">-</dd></div>
                            <div><dt><i class="bi bi-person" aria-hidden="true"></i>Nama</dt><dd id="rvNama">-</dd></div>
                            <div><dt><i class="bi bi-whatsapp" aria-hidden="true"></i>WhatsApp</dt><dd id="rvHp">-</dd></div>
                        </dl>
                    </section>
                </fieldset>

                <div class="am-actions">
                    <button type="button" class="am-round" id="amBack" aria-label="Kembali" disabled><i class="bi bi-chevron-left" aria-hidden="true"></i></button>
                    <button type="button" class="am-main" id="amNext">Lanjut<i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                    <button type="submit" class="am-main am-main-go" id="amSubmit" hidden @disabled(! $pendaftaranDibuka)><i class="bi bi-ticket-perforated" aria-hidden="true"></i>Ambil nomor</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var form = document.getElementById('formAmbil');
        if (!form) return;

        var $ = function (s, c) { return (c || document).querySelector(s); };
        var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };
        var panels = $$('.am-panel', form), steps = $$('.am-step'), stepsEl = $('#amSteps');
        var back = $('#amBack'), next = $('#amNext'), submit = $('#amSubmit');
        var nik = $('#nik'), nama = $('#nama'), hp = $('#no_hp'), poliErr = $('#amPoliErr');
        var cur = 1;

        var txt = function (id, v) { var e = document.getElementById(id); if (e) e.textContent = v; };
        var poli = function () { return $('input[name=poli_id]:checked', form); };

        function sync() {
            var p = poli(), k = $('#tkKode'), kode = p ? p.dataset.kode : '?';
            txt('tkPoli', p ? p.dataset.nama : 'Belum dipilih');
            if (k.textContent !== kode) {
                k.textContent = kode;
                k.classList.remove('am-pop'); void k.offsetWidth; k.classList.add('am-pop');
            }
            $('#tkDots').classList.toggle('is-idle', !p);
            txt('tkDepan', p ? (p.dataset.menunggu === '0' ? 'Belum ada' : p.dataset.menunggu + ' orang') : '-');

            // ringkasan kecil di bawah judul langkah
            txt('amHint1', p ? p.dataset.nama : '');
            txt('amHint2', nama.value.trim());
            txt('amHint3', '');

            var n = nik.value.length;
            txt('amCount', n + '/16');
            $('#amCount').classList.toggle('is-ok', n === 16);
            $('#amMeter').style.width = (n / 16 * 100) + '%';
            $('#amMeter').classList.toggle('is-ok', n === 16);
        }

        function fill() {
            var p = poli(), n = nik.value;
            txt('rvPoli', p ? p.dataset.nama + ' (' + p.dataset.kode + ')' : '-');
            txt('rvNik', n.length === 16 ? n.slice(0, 4) + ' •••• •••• ' + n.slice(-4) : (n || '-'));
            txt('rvNama', nama.value.trim() || '-');
            txt('rvHp', hp.value.trim() || 'Tidak diisi');
        }

        function valid(n) {
            if (n === 1) { var ok = !!poli(); poliErr.hidden = ok; return ok; }
            nik.setCustomValidity(nik.value.length === 16 ? '' : 'NIK harus 16 digit angka.');
            var els = $$('input:not([type=radio])', panels[n - 1]);
            for (var i = 0; i < els.length; i++) {
                if (!els[i].checkValidity()) { els[i].reportValidity(); return false; }
            }
            return true;
        }

        function go(n, user) {
            cur = Math.max(1, Math.min(3, n));
            panels.forEach(function (p, i) { p.classList.toggle('is-active', i === cur - 1); });
            steps.forEach(function (s, i) {
                s.classList.toggle('is-active', i === cur - 1);
                s.classList.toggle('is-done', i < cur - 1);
                var b = $('button', s);
                if (i === cur - 1) b.setAttribute('aria-current', 'step'); else b.removeAttribute('aria-current');
            });
            stepsEl.style.setProperty('--am-p', (cur - 1) / 2);
            var pct = Math.round(cur / 3 * 100);
            $('#amBar').style.width = pct + '%';
            txt('amPct', pct + '%');
            $('#amProg').setAttribute('aria-valuenow', pct);
            back.disabled = cur === 1;
            next.hidden = cur === 3;
            submit.hidden = cur !== 3;
            if (cur === 3) fill();
            if (user && cur === 2) nik.focus({ preventScroll: true });
        }

        steps.forEach(function (s, i) {
            $('button', s).addEventListener('click', function () {
                var target = i + 1;
                if (target <= cur) return go(target, true);
                for (var k = cur; k < target; k++) { if (!valid(k)) return go(k, true); }
                go(target, true);
            });
        });

        next.addEventListener('click', function () { if (valid(cur)) go(cur + 1, true); });
        back.addEventListener('click', function () { go(cur - 1, true); });

        form.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && cur < 3 && e.target.tagName !== 'BUTTON') { e.preventDefault(); next.click(); }
        });

        nik.addEventListener('input', function () {
            nik.value = nik.value.replace(/\D/g, '').slice(0, 16);
            nik.setCustomValidity('');
            sync();
        });
        [nama, hp].forEach(function (el) { el.addEventListener('input', sync); });
        $$('input[name=poli_id]', form).forEach(function (r) {
            r.addEventListener('change', function () { poliErr.hidden = true; sync(); });
        });

        form.addEventListener('submit', function (e) {
            for (var k = 1; k <= 2; k++) {
                if (!valid(k)) { e.preventDefault(); go(k, true); return; }
            }
            submit.disabled = true;
            submit.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>Memproses';
        });

        // Tautan langsung ?poli=ID
        if (!poli()) {
            var q = new URLSearchParams(location.search).get('poli');
            var r = q && $('input[name=poli_id][value="' + q.replace(/\D/g, '') + '"]', form);
            if (r) r.checked = true;
        }

        sync();

        // Kalau server menolak isian, buka langkah yang bermasalah
        var start = 1, bad = $('.is-invalid', form);
        if (bad) { panels.forEach(function (p, i) { if (p.contains(bad)) start = i + 1; }); }
        go(start, false);
    })();
    </script>
</x-public-layout>
