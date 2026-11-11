// Beranda: panel "Antrian hari ini" di hero.
// Membaca data dari endpoint JSON (route antrian.hari-ini), berganti poli otomatis dengan
// transisi halus, dan ikut memperbarui angka ringkasan. Tanpa dependensi tambahan.
(function () {
    const akar = document.querySelector('[data-hero-antrian]');

    if (! akar) {
        return;
    }

    const stage = akar.querySelector('[data-stage]');
    const tabs = akar.querySelector('[data-tabs]');
    const elTanggal = akar.querySelector('[data-tanggal]');
    const elMode = akar.querySelector('[data-mode]');
    const elUpdate = akar.querySelector('[data-diperbarui]');
    const tombolJeda = akar.querySelector('[data-jeda]');

    const endpoint = akar.dataset.endpoint;
    const jedaPoll = parseInt(akar.dataset.poll, 10) || 5000;
    const durasiPutar = parseInt(akar.dataset.putar, 10) || 6000;
    const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    akar.style.setProperty('--putar', durasiPutar + 'ms');

    const PRAKTIK = {
        berlangsung: ['ok', 'Sedang praktik'],
        belum: ['warn', 'Belum mulai'],
        selesai: ['off', 'Praktik selesai'],
        libur: ['off', 'Tidak praktik'],
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const waktu = (d) => d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    let data = null;
    let cur = 0;

    try {
        const awal = akar.querySelector('script[data-awal]');
        data = awal ? JSON.parse(awal.textContent) : null;
    } catch (e) {
        data = null;
    }

    // ---------- bantu isi DOM ----------
    function isiTeks(el, kunci, nilai) {
        const n = el.querySelector('[data-k="' + kunci + '"]');
        nilai = String(nilai);

        if (n && n.textContent !== nilai) {
            n.textContent = nilai;
            return true;
        }

        return false;
    }

    function pasangMount(root, items, templat, perbarui) {
        const ids = items.map((p) => p.id).join('|');
        let dibangun = false;

        if (root.dataset.ids !== ids) {
            root.dataset.ids = ids;
            root.innerHTML = items.map(templat).join('');
            dibangun = true;
        }

        items.forEach((p) => {
            const el = root.querySelector('[data-id="' + p.id + '"]');

            if (el) {
                perbarui(el, p);
            }
        });

        return dibangun;
    }

    // ---------- slide poli ----------
    const templatSlide = (p) => `
<article class="hm-slide" data-id="${p.id}" aria-hidden="true">
  <div class="hm-tk">
    <div class="hm-tk-main">
      <div class="hm-slide-top">
        <span class="hm-kode">${esc(p.kode)}</span>
        <div class="hm-slide-title"><h3 data-k="nama"></h3><p data-k="dokter"></p></div>
        <span class="hm-badge" data-k="praktik"></span>
      </div>
      <div class="hm-tk-no">
        <small>Sedang dipanggil</small>
        <div class="hm-tk-row"><strong class="hm-call-no" data-k="nomor"></strong><span class="hm-call-state" data-k="state"></span></div>
      </div>
      <div class="hm-prog"><i data-k="prog"></i></div>
      <div class="hm-prog-label"><span data-k="progL"></span><span data-k="jam"></span></div>
    </div>
    <div class="hm-tk-stub">
      <div class="hm-mini">
        <div><b data-k="menunggu"></b><span>Menunggu</span></div>
        <div><b data-k="selesai"></b><span>Selesai</span></div>
        <div><b data-k="estimasi"></b><span>Perkiraan</span></div>
      </div>
      <div class="hm-next">
        <span>Berikutnya</span><div class="hm-chips" data-k="chips"></div>
        <a class="hm-slide-link" href="${esc(p.url_layar)}">Buka layar <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
      </div>
    </div>
  </div>
</article>`;

    function perbaruiSlide(el, p) {
        isiTeks(el, 'nama', p.nama);
        isiTeks(el, 'dokter', p.dokter || 'Jadwal dokter belum tersedia');
        isiTeks(el, 'jam', p.jam || '');

        const [kelas, teks] = PRAKTIK[p.praktik] || PRAKTIK.libur;
        const badge = el.querySelector('[data-k="praktik"]');
        badge.className = 'hm-badge hm-badge-' + kelas;
        badge.textContent = teks;

        const noEl = el.querySelector('[data-k="nomor"]');
        const berubah = isiTeks(el, 'nomor', p.nomor || '—');
        noEl.classList.toggle('is-kosong', ! p.nomor);

        if (berubah && el.dataset.siap && ! kurangiGerak) {
            noEl.classList.remove('hm-bump');
            void noEl.offsetWidth;
            noEl.classList.add('hm-bump');
        }

        isiTeks(el, 'state', p.status_panggil === 'dipanggil' ? 'Dipanggil' : (p.status_panggil === 'dilayani' ? 'Sedang dilayani' : 'Belum ada panggilan'));
        el.querySelector('[data-k="prog"]').style.width = p.persen + '%';
        isiTeks(el, 'progL', p.total + ' antrian hari ini');
        isiTeks(el, 'menunggu', p.menunggu);
        isiTeks(el, 'selesai', p.selesai);
        isiTeks(el, 'estimasi', p.menunggu > 0 ? '± ' + p.estimasi_menit + ' mnt' : '–');

        const chips = el.querySelector('[data-k="chips"]');
        const html = p.berikutnya.length
            ? p.berikutnya.map((n) => '<span class="hm-nochip">' + esc(n) + '</span>').join('')
            : '<span class="hm-nochip is-kosong">Tidak ada yang menunggu</span>';

        if (chips.dataset.v !== html) {
            chips.dataset.v = html;
            chips.innerHTML = html;
        }

        el.dataset.siap = '1';
    }

    // ---------- tab poli ----------
    const templatTab = (p, i) => `
<button class="hm-tab" type="button" data-id="${p.id}" data-i="${i}">
  <span class="t-nama">${esc(p.nama.replace(/^poli\s+/i, ''))}</span>
  <span class="t-no" data-k="nomor"></span><i class="t-bar"></i>
</button>`;
    const perbaruiTab = (el, p) => isiTeks(el, 'nomor', p.nomor || '—');

    function aktifkanTab(i) {
        tabs.querySelectorAll('.hm-tab').forEach((t, k) => {
            const hidup = k === i;
            t.classList.toggle('is-active', hidup);

            if (hidup) {
                t.setAttribute('aria-current', 'true');
                // geser daftar tab saja, jangan menggulir halaman
                const kiri = t.offsetLeft - (tabs.clientWidth - t.offsetWidth) / 2;
                tabs.scrollTo({ left: Math.max(0, kiri), behavior: kurangiGerak ? 'auto' : 'smooth' });
            } else {
                t.removeAttribute('aria-current');
            }
        });
    }

    // ---------- pindah slide ----------
    function pindah(i, arah) {
        const slides = Array.from(stage.querySelectorAll('.hm-slide'));

        if (! slides.length) {
            return;
        }

        i = (i + slides.length) % slides.length;

        if (i === cur && slides[i].classList.contains('is-active')) {
            return;
        }

        arah = arah || (i > cur ? 1 : -1);
        const keluar = slides[cur];
        const masuk = slides[i];

        if (keluar && keluar !== masuk) {
            keluar.style.setProperty('--dx', (-arah * 36) + 'px');
            keluar.classList.remove('is-active');
            keluar.setAttribute('aria-hidden', 'true');
        }

        // slide masuk mulai dari sisi lawan tanpa transisi, lalu meluncur ke tengah
        masuk.style.transition = 'none';
        masuk.style.setProperty('--dx', (arah * 36) + 'px');
        void masuk.offsetWidth;
        masuk.style.transition = '';
        masuk.style.setProperty('--dx', '0px');
        masuk.classList.add('is-active');
        masuk.removeAttribute('aria-hidden');

        cur = i;
        aktifkanTab(i);
    }

    const berikut = () => pindah(cur + 1, 1);
    const sebelum = () => pindah(cur - 1, -1);

    // Pergantian otomatis dipicu selesainya animasi progres di tab aktif, jadi ikut berhenti
    // saat kursor di atas panel atau saat tombol jeda ditekan.
    tabs.addEventListener('animationend', (e) => { if (e.target.classList.contains('t-bar')) berikut(); });
    tabs.addEventListener('click', (e) => {
        const t = e.target.closest('.hm-tab');
        if (t) pindah(parseInt(t.dataset.i, 10));
    });

    tombolJeda.addEventListener('click', () => {
        const jeda = tombolJeda.getAttribute('aria-pressed') !== 'true';
        tombolJeda.setAttribute('aria-pressed', String(jeda));
        tombolJeda.setAttribute('aria-label', jeda ? 'Lanjutkan pergantian poli otomatis' : 'Jeda pergantian poli otomatis');
        akar.classList.toggle('is-paused', jeda);
    });

    // geser kiri/kanan di layar sentuh
    let awalX = null;
    stage.addEventListener('pointerdown', (e) => { awalX = e.clientX; });
    stage.addEventListener('pointerup', (e) => {
        if (awalX === null) return;
        const dx = e.clientX - awalX;
        awalX = null;
        if (Math.abs(dx) > 50) { dx < 0 ? berikut() : sebelum(); }
    });
    stage.addEventListener('pointercancel', () => { awalX = null; });

    // ---------- angka ringkasan ----------
    function hitungKe(el, ke) {
        const dari = parseInt(el.dataset.v ?? el.textContent, 10) || 0;
        el.dataset.v = ke;

        if (dari === ke) {
            el.textContent = ke.toLocaleString('id-ID');
            return;
        }

        if (kurangiGerak) {
            el.textContent = ke.toLocaleString('id-ID');
            return;
        }

        const mulai = performance.now(), durasi = 700;
        const langkah = (t) => {
            const p = Math.min(1, (t - mulai) / durasi);
            el.textContent = Math.round(dari + (ke - dari) * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
            if (p < 1) requestAnimationFrame(langkah);
        };
        requestAnimationFrame(langkah);
    }

    function perbaruiRingkasan(r) {
        document.querySelectorAll('[data-stat]').forEach((el) => {
            const nilai = r[el.dataset.stat];

            if (typeof nilai === 'number') {
                hitungKe(el, nilai);
            }
        });
    }

    // ---------- gambar ulang ----------
    function gambar() {
        if (! data) {
            return;
        }

        const items = Array.isArray(data.poli) ? data.poli : [];

        if (! items.length) {
            stage.dataset.ids = '';
            tabs.dataset.ids = '';
            stage.innerHTML = '<div class="hm-stage-note">Belum ada poli aktif hari ini.</div>';
            tabs.innerHTML = '';
        } else {
            const dibangun = pasangMount(stage, items, templatSlide, perbaruiSlide);
            pasangMount(tabs, items, templatTab, perbaruiTab);
            tabs.classList.toggle('is-single', items.length < 2);

            if (dibangun) {
                cur = Math.min(cur, items.length - 1);
                const s = stage.querySelectorAll('.hm-slide')[cur];
                s.style.setProperty('--dx', '0px');
                s.classList.add('is-active');
                s.removeAttribute('aria-hidden');
                aktifkanTab(cur);
            }
        }

        if (data.ringkasan) {
            perbaruiRingkasan(data.ringkasan);
        }

        elTanggal.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
    }

    function setTersambung(ok) {
        elMode.classList.toggle('is-off', ! ok);
        elMode.lastElementChild.textContent = ok ? 'Langsung' : 'Terputus';

        if (ok) {
            elUpdate.textContent = 'Diperbarui ' + waktu(new Date()) + ' · otomatis';
        } else {
            elUpdate.textContent = 'Koneksi terputus, mencoba lagi…';
        }
    }

    async function muat() {
        try {
            const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin' });

            if (! res.ok) {
                throw new Error(res.status);
            }

            data = await res.json();
            setTersambung(true);
            gambar();
        } catch (e) {
            setTersambung(false);
        }
    }

    // data awal ikut halaman, jadi panel langsung terisi tanpa menunggu jaringan
    if (data) {
        setTersambung(true);
        gambar();
    }

    setInterval(() => { if (! document.hidden) muat(); }, jedaPoll);
    document.addEventListener('visibilitychange', () => { if (! document.hidden) muat(); });

    // ---------- saringan poli di jadwal dokter ----------
    const jadwal = document.querySelector('[data-dk]');

    if (jadwal) {
        const tombol = [...jadwal.querySelectorAll('[data-dk-tab]')];
        const kartu = [...jadwal.querySelectorAll('[data-dk-kartu]')];

        const tampilkan = (poli) => {
            let urut = 0;

            kartu.forEach((k) => {
                const cocok = poli === '*' || k.dataset.poli === poli;
                k.hidden = ! cocok;
                k.classList.remove('is-masuk');

                if (cocok && ! kurangiGerak) {
                    k.style.setProperty('--i', urut++);
                    void k.offsetWidth; // mulai ulang animasi
                    k.classList.add('is-masuk');
                }
            });

            tombol.forEach((t) => t.setAttribute('aria-pressed', String(t.dataset.dkTab === poli)));
        };

        tombol.forEach((t) => t.addEventListener('click', () => {
            tampilkan(t.dataset.dkTab);
            t.scrollIntoView({ block: 'nearest', inline: 'center', behavior: kurangiGerak ? 'auto' : 'smooth' });
        }));

        jadwal.classList.add('is-js');
    }
})();
