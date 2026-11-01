<x-public-layout title="Ambil nomor antrian" :wide="true">
    <div class="am-page">
        <script>document.currentScript.parentElement.classList.add('am-js');</script>

        <header class="am-head">
            <div>
                <h1 class="am-title">Ambil nomor antrian</h1>
                <p class="am-lead">Tiga langkah singkat. Tiket di samping ikut terisi saat Anda mengetik.</p>
            </div>

            @if (\App\Services\JamOperasional::aktif())
                @if ($pendaftaranDibuka)
                    <span class="am-pill am-pill-ok"><span class="am-dot"></span>Dibuka sampai pukul {{ \App\Services\JamOperasional::tampil(config('operasional.pendaftaran_tutup')) }}</span>
                @else
                    <span class="am-pill am-pill-off"><span class="am-dot"></span>Pendaftaran ditutup</span>
                @endif
            @else
                <span class="am-pill am-pill-ok"><span class="am-dot"></span>Antrian online aktif</span>
            @endif
        </header>

        @include('partials.errors')

        @unless ($pendaftaranDibuka)
            <div class="alert alert-danger d-flex gap-2" role="alert">
                <i class="bi bi-clock-history mt-1"></i>
                <div><div class="fw-semibold">Pendaftaran sedang ditutup</div>{{ $pesanTutup }}</div>
            </div>
        @endunless

        <div class="row g-4 g-xl-5 align-items-start">

            {{-- Formulir --}}
            <div class="col-lg-7 col-xl-8">
                <form method="POST" action="{{ route('antrian.store') }}" id="formAmbil" class="am-card">
                    @csrf

                    <div class="am-card-head">
                        <ol class="am-steps" id="amSteps">
                            @foreach (['Pilih poli', 'Data diri', 'Tinjau'] as $i => $nama)
                                <li class="am-step {{ $i === 0 ? 'is-active' : '' }}">
                                    <button type="button" class="am-step-btn">
                                        <span class="am-step-no"><b>{{ $i + 1 }}</b><i class="bi bi-check-lg"></i></span>
                                        <span class="am-step-txt">{{ $nama }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </div>

                    <fieldset class="am-card-body" @disabled(! $pendaftaranDibuka)>

                        {{-- Langkah 1 --}}
                        <section class="am-panel is-active" aria-labelledby="amT1">
                            <h2 class="am-panel-title" id="amT1">Mau berobat ke poli mana?</h2>
                            <p class="am-panel-sub">Angka di kartu adalah jumlah pasien yang sedang menunggu hari ini.</p>

                            <div class="am-poli-grid @error('poli_id') is-invalid @enderror" id="amPoliGrid" role="radiogroup" aria-label="Poli tujuan">
                                @foreach ($polis as $poli)
                                    @php $menunggu = (int) ($poli->menunggu_count ?? 0); @endphp
                                    <div class="am-poli-wrap">
                                        <input type="radio" class="am-poli-input" name="poli_id" id="poli-{{ $poli->id }}" value="{{ $poli->id }}"
                                               data-kode="{{ $poli->kode_poli }}" data-nama="{{ $poli->nama_poli }}" data-menunggu="{{ $menunggu }}"
                                               required @checked(old('poli_id') == $poli->id)>
                                        <label class="am-poli" for="poli-{{ $poli->id }}">
                                            <span class="kode">{{ $poli->kode_poli }}</span>
                                            <span class="am-poli-body">
                                                <span class="am-poli-name">{{ $poli->nama_poli }}</span>
                                                <span class="am-poli-meta">
                                                    @if ($menunggu === 0)
                                                        Belum ada antrian
                                                    @else
                                                        {{ $menunggu }} menunggu
                                                        @if ($menunggu > 10)<span class="am-busy">Ramai</span>@endif
                                                    @endif
                                                </span>
                                            </span>
                                            <i class="bi bi-check-circle-fill am-poli-check" aria-hidden="true"></i>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('poli_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            <div class="am-inline-err" id="amPoliErr" role="alert" hidden><i class="bi bi-exclamation-circle-fill me-1"></i>Pilih salah satu poli untuk melanjutkan.</div>

                            @if ($polis->isEmpty())
                                <p class="text-body-secondary mb-0">Belum ada poli aktif. Hubungi petugas loket.</p>
                            @endif
                        </section>

                        {{-- Langkah 2 --}}
                        <section class="am-panel" aria-labelledby="amT2">
                            <h2 class="am-panel-title" id="amT2">Isi data singkat</h2>
                            <p class="am-panel-sub">Pasien lama dikenali dari NIK, jadi data tidak perlu diisi dua kali.</p>

                            <div class="am-fields">
                                <div class="am-full">
                                    <label for="nik" class="form-label">NIK</label>
                                    <div class="am-nik-wrap">
                                        <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" autocomplete="off" required
                                               class="form-control am-nik @error('nik') is-invalid @enderror" value="{{ old('nik') }}" placeholder="16 digit angka">
                                        <span class="am-count" id="amCount" aria-live="polite">0/16</span>
                                    </div>
                                    <div class="am-meter" aria-hidden="true"><span id="amMeter"></span></div>
                                    @error('nik')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                <div class="am-full">
                                    <label for="nama" class="form-label">Nama lengkap</label>
                                    <input id="nama" name="nama" type="text" autocomplete="name" required maxlength="255"
                                           class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}">
                                    @error('nama')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                <div class="am-full">
                                    <label for="no_hp" class="form-label">No. WhatsApp <span class="am-opt">opsional</span></label>
                                    <div class="input-group am-hp">
                                        <span class="input-group-text"><i class="bi bi-whatsapp"></i></span>
                                        <input id="no_hp" name="no_hp" type="tel" inputmode="tel" autocomplete="tel" maxlength="20"
                                               class="form-control @error('no_hp') is-invalid @enderror" value="{{ old('no_hp') }}" placeholder="081234567890">
                                    </div>
                                    <div class="form-text">Isi agar Anda dikabari lewat WhatsApp saat nomor dipanggil.</div>
                                    @error('no_hp')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                <div>
                                    <label for="tanggal_lahir" class="form-label">Tanggal lahir <span class="am-opt">opsional</span></label>
                                    <input id="tanggal_lahir" name="tanggal_lahir" type="date" max="{{ now()->toDateString() }}"
                                           class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ old('tanggal_lahir') }}">
                                    @error('tanggal_lahir')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                <div>
                                    <div class="form-label">Jenis kelamin <span class="am-opt">opsional</span></div>
                                    <div class="am-seg">
                                        @foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $nilai => $teks)
                                            <div>
                                                <input type="radio" class="btn-check" name="jenis_kelamin" id="jk-{{ $nilai }}" value="{{ $nilai }}" autocomplete="off" @checked(old('jenis_kelamin') === $nilai)>
                                                <label class="choice-card choice-pill" for="jk-{{ $nilai }}">{{ $teks }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                        {{-- Langkah 3 --}}
                        <section class="am-panel" aria-labelledby="amT3">
                            <h2 class="am-panel-title" id="amT3">Periksa lagi sebelum mengambil nomor</h2>
                            <p class="am-panel-sub">Kalau ada yang keliru, pilih langkah di atas untuk memperbaikinya.</p>

                            <dl class="am-review">
                                <div><dt>Poli</dt><dd id="rvPoli">-</dd></div>
                                <div><dt>NIK</dt><dd id="rvNik">-</dd></div>
                                <div><dt>Nama</dt><dd id="rvNama">-</dd></div>
                                <div><dt>WhatsApp</dt><dd id="rvHp">-</dd></div>
                            </dl>

                            <p class="am-note"><i class="bi bi-info-circle me-2"></i>Jika Anda masih punya antrian aktif di poli yang sama hari ini, kami tampilkan tiket yang sudah ada, bukan membuat nomor baru.</p>
                        </section>

                        <div class="am-actions">
                            <button type="button" class="btn btn-outline-secondary am-js-only" id="amBack" hidden><i class="bi bi-arrow-left me-1"></i>Kembali</button>
                            <button type="button" class="btn btn-primary btn-lg am-js-only" id="amNext">Lanjut<i class="bi bi-arrow-right ms-2"></i></button>
                            <button type="submit" class="btn btn-primary btn-lg" id="amSubmit" @disabled(! $pendaftaranDibuka)>
                                <i class="bi bi-ticket-perforated me-2"></i>Ambil nomor antrian
                            </button>
                        </div>
                    </fieldset>
                </form>
            </div>

            {{-- Tiket langsung --}}
            <aside class="col-lg-5 col-xl-4 order-first order-lg-last" aria-label="Pratinjau tiket">
                <div class="am-aside">
                    <article class="am-ticket">
                        <div class="board board-pattern rounded-0 am-ticket-top">
                            <div class="am-ticket-label">Pratinjau tiket</div>
                            <div class="am-ticket-poli" id="tkPoli">Pilih poli terlebih dahulu</div>
                            <div class="numeral am-ticket-no"><span id="tkKode">?</span><span class="am-ticket-dots is-idle" id="tkDots">•••</span></div>
                        </div>
                        <div class="ticket-cut" aria-hidden="true"><span class="hole hole-l"></span><span class="hole hole-r"></span></div>
                        <ul class="am-ticket-meta">
                            <li><span>Di depan Anda</span><b id="tkDepan">-</b></li>
                            <li><span>Atas nama</span><b id="tkNama">-</b></li>
                            <li><span>Kabar WhatsApp</span><b id="tkWa">Tidak diisi</b></li>
                        </ul>
                    </article>

                    <ul class="am-tips d-none d-lg-block">
                        <li><i class="bi bi-hourglass-split"></i><span>Perkiraan waktu tunggu muncul di tiket setelah nomor diambil.</span></li>
                        <li><i class="bi bi-arrow-repeat"></i><span>Tiket memperbarui diri otomatis, tidak perlu dimuat ulang.</span></li>
                        <li><i class="bi bi-tv"></i><span>Nomor Anda juga tampil di layar ruang tunggu saat dipanggil.</span></li>
                    </ul>
                </div>
            </aside>
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
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            var txt = function (id, v) { var e = document.getElementById(id); if (e) e.textContent = v; };
            var poli = function () { return $('input[name=poli_id]:checked', form); };

            function sync() {
                var p = poli(), k = $('#tkKode'), kode = p ? p.dataset.kode : '?';
                txt('tkPoli', p ? p.dataset.nama : 'Pilih poli terlebih dahulu');
                if (k.textContent !== kode) {
                    k.textContent = kode;
                    k.classList.remove('am-pop'); void k.offsetWidth; k.classList.add('am-pop');
                }
                $('#tkDots').classList.toggle('is-idle', !p);
                txt('tkDepan', p ? (p.dataset.menunggu === '0' ? 'Belum ada' : p.dataset.menunggu + ' orang') : '-');
                txt('tkNama', nama.value.trim() || '-');
                txt('tkWa', hp.value.trim() ? 'Aktif' : 'Tidak diisi');

                var n = nik.value.length;
                txt('amCount', n + '/16');
                $('#amCount').classList.toggle('is-ok', n === 16);
                $('#amMeter').style.width = (n / 16 * 100) + '%';
                $('#amMeter').classList.toggle('is-ok', n === 16);
            }

            function fill() {
                var p = poli(), n = nik.value;
                txt('rvPoli', p ? p.dataset.nama + ' (' + p.dataset.kode + ')' : '-');
                txt('rvNik', n.length === 16 ? n.slice(0, 4) + '••••••••' + n.slice(-4) : (n || '-'));
                txt('rvNama', nama.value.trim() || '-');
                txt('rvHp', hp.value.trim() || 'Tidak diisi');
            }

            function valid(n) {
                var panel = panels[n - 1];
                if (n === 1) {
                    var ok = !!poli();
                    poliErr.hidden = ok;
                    return ok;
                }
                nik.setCustomValidity(nik.value.length === 16 ? '' : 'NIK harus 16 digit angka.');
                var els = $$('input:not([type=radio]), select', panel);
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
                back.hidden = cur === 1;
                next.hidden = cur === 3;
                submit.hidden = cur !== 3;
                if (cur === 3) fill();

                if (user) {
                    var card = $('.am-card');
                    if (card.getBoundingClientRect().top < 0) card.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
                    if (cur === 2) { var f = $('#nik'); if (f) f.focus({ preventScroll: true }); }
                }
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
                submit.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memproses';
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
    </div>
</x-public-layout>
