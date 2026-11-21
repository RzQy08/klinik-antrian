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
            root.innerHTML = items.map((p, i) => templat(p, i)).join('');
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
    // ikon per poli, dicocokkan dari kata kunci pada nama
    const IKON = [
        [/anak|pediatri/i, 'balloon-heart'], [/kia|kandungan|ibu|obgyn|bidan/i, 'person-hearts'], [/gigi|mulut/i, 'emoji-smile'],
        [/mata/i, 'eye'], [/tht|telinga/i, 'ear'], [/dalam/i, 'activity'], [/paru/i, 'lungs'], [/kulit/i, 'bandaid'],
        [/umum/i, 'heart-pulse'], [/gizi|farmasi|obat/i, 'capsule'],
    ];
    const ikonPoli = (nama) => (IKON.find(([re]) => re.test(nama || '')) || [0, 'hospital'])[1];

    const templatSlide = (p, i) => `
<article class="hm-slide" data-id="${p.id}" data-w="${i % 6}" aria-hidden="true">
  <div class="hm-kc">
    <div class="hm-kc-top">
      <span class="hm-kc-ico" aria-hidden="true"><i class="bi bi-${ikonPoli(p.nama)}"></i></span>
      <h3 data-k="nama"></h3>
      <p data-k="dokter"></p>
      <span class="hm-badge" data-k="praktik"></span>
    </div>
    <div class="hm-kc-bottom">
      <i class="hm-wave hm-wave-b" aria-hidden="true"></i><i class="hm-wave hm-wave-a" aria-hidden="true"></i>
      <svg class="hm-kc-ecg" viewBox="0 0 300 50" preserveAspectRatio="none" aria-hidden="true"><path d="M0 28H92l10-16 12 34 11-26 8 8H300"/></svg>
      <div class="hm-kc-body">
        <div class="hm-tk-no">
          <small>Sedang dipanggil</small>
          <div class="hm-tk-row"><strong class="hm-call-no" data-k="nomor"></strong><span class="hm-call-state" data-k="state"></span></div>
        </div>
        <div class="hm-mini">
          <div><b data-k="menunggu"></b><span>Menunggu</span></div>
          <div><b data-k="selesai"></b><span>Selesai</span></div>
          <div><b data-k="estimasi"></b><span>Estimasi</span></div>
        </div>
        <div class="hm-next">
          <span class="hm-next-l">Berikutnya</span>
          <div class="hm-chips" data-k="chips"></div>
          <a class="hm-slide-link" href="${esc(p.url_layar)}" aria-label="Buka layar ${esc(p.nama)}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        </div>
      </div>
    </div>
  </div>
</article>`;

    function perbaruiSlide(el, p) {
        isiTeks(el, 'nama', p.nama);
        isiTeks(el, 'dokter', p.dokter || 'Jadwal dokter belum tersedia');

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

        isiTeks(el, 'state', p.status_panggil === 'dipanggil' ? 'Dipanggil' : (p.status_panggil === 'dilayani' ? 'Sedang dilayani' : 'Belum dipanggil'));
        isiTeks(el, 'menunggu', p.menunggu);
        isiTeks(el, 'selesai', p.selesai);
        isiTeks(el, 'estimasi', p.menunggu > 0 ? '± ' + p.estimasi_menit + ' mnt' : '–');

        const chips = el.querySelector('[data-k="chips"]');
        const html = p.berikutnya.length
            ? p.berikutnya.slice(0, 1).map((n) => '<span class="hm-nochip">' + esc(n) + '</span>').join('')
            : '<span class="hm-nochip is-kosong">Belum ada</span>';

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
    // posisi tiap kartu relatif terhadap yang aktif: 0 di tengah, ±1 mengintip di sisi, sisanya disembunyikan
    function susun() {
        const slides = Array.from(stage.querySelectorAll('.hm-slide'));
        const n = slides.length;

        slides.forEach((el, k) => {
            let o = k - cur;

            if (o > n / 2) { o -= n; } else if (o < -n / 2) { o += n; }

            const a = Math.abs(o);
            const sisi = a === 1 && n > 1;
            el.style.setProperty('--o', Math.max(-2, Math.min(2, o)));
            el.style.setProperty('--a', Math.min(a, 2));
            el.classList.toggle('is-active', o === 0);
            el.classList.toggle('is-sisi', sisi && n > 2 || (n === 2 && a === 1));
            el.tabIndex = o === 0 ? -1 : (sisi ? 0 : -1);
            el.toggleAttribute('aria-hidden', o !== 0);
        });
    }

    function pindah(i) {
        const n = stage.querySelectorAll('.hm-slide').length;

        if (! n) {
            return;
        }

        i = (i % n + n) % n;
        cur = i;
        susun();
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

    // klik atau Enter pada kartu yang mengintip di sisi untuk memilihnya
    const pilihSisi = (e) => {
        const el = e.target.closest('.hm-slide.is-sisi');
        if (el && (e.type === 'click' || e.key === 'Enter' || e.key === ' ')) {
            e.preventDefault();
            pindah(Array.from(stage.children).indexOf(el));
        }
    };
    stage.addEventListener('click', pilihSisi);
    stage.addEventListener('keydown', pilihSisi);

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
                susun();
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

    // Tanda isi data tanpa cap waktunya: kalau sama dengan yang terakhir, layar tidak perlu digambar ulang.
    const tanda = (d) => { const { diperbarui, ...sisa } = d; return JSON.stringify(sisa); };
    let tandaAkhir = data ? tanda(data) : '';
    let gagal = 0;
    let sedangMuat = false;
    let timer = null;

    async function muat() {
        if (sedangMuat) {
            return;
        }

        sedangMuat = true;
        const batas = new AbortController();
        const habis = setTimeout(() => batas.abort(), 8000);

        try {
            const res = await fetch(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store', credentials: 'same-origin', signal: batas.signal });

            if (! res.ok) {
                throw new Error(res.status);
            }

            const baru = await res.json();
            gagal = 0;
            setTersambung(true);

            const t = tanda(baru);

            if (t !== tandaAkhir) {
                tandaAkhir = t;
                data = baru;
                gambar();
                langsung(baru);
            }
        } catch (e) {
            gagal++;
            setTersambung(false);
        } finally {
            clearTimeout(habis);
            sedangMuat = false;
        }
    }

    // Satu permintaan pada satu waktu. Gagal berturut-turut memperlambat sendiri (maksimal 30 detik).
    function jadwalkan() {
        clearTimeout(timer);
        const jeda = gagal ? Math.min(30000, jedaPoll * 2 ** gagal) : jedaPoll;

        timer = setTimeout(async () => {
            if (! document.hidden) {
                await muat();
            }

            jadwalkan();
        }, jeda + Math.random() * 400);
    }

    // data awal ikut halaman, jadi panel langsung terisi tanpa menunggu jaringan
    if (data) {
        setTersambung(true);
        gambar();
    }

    jadwalkan();
    document.addEventListener('visibilitychange', () => {
        if (! document.hidden) {
            muat().then(jadwalkan);
        }
    });
    window.addEventListener('online', () => { gagal = 0; muat().then(jadwalkan); });

    // ---------- status pendaftaran, jam layanan, dan jadwal dokter ----------
    // Hanya menyentuh DOM bila nilainya berubah, jadi tidak ada kedipan atau kerja berlebih.
    const setTeks = (el, t) => { if (el && el.textContent !== t) el.textContent = t; };
    const setHidden = (el, h) => { if (el && el.hidden !== h) el.hidden = h; };

    function langsungLayanan(l) {
        if (! l) {
            return;
        }

        const tutup = l.aktif && ! l.buka;

        document.querySelectorAll('[data-live-status]').forEach((el) => setTeks(el, l.teks));
        document.querySelectorAll('[data-live-dot]').forEach((el) => el.classList.toggle('off', tutup));
        document.querySelectorAll('[data-live-sheet]').forEach((el) => el.classList.toggle('off', tutup));
        document.querySelectorAll('[data-live-layanan]').forEach((el) => setHidden(el, ! l.aktif));
        document.querySelectorAll('[data-live-selesai]').forEach((el) => setTeks(el, l.layanan_selesai || ''));
        document.querySelectorAll('[data-live-jamlayanan]').forEach((el) => { if (l.layanan_selesai) setTeks(el, 'Sampai pukul ' + l.layanan_selesai); });

        const pill = document.querySelector('[data-live-pill]');

        if (pill) {
            pill.classList.toggle('hm-pill-off', tutup);
            setTeks(pill.querySelector('[data-live-pill-teks]'), l.pill);
        }
    }

    const dokterRoot = document.querySelector('[data-dokter-live]');
    const dkGrid = dokterRoot && dokterRoot.querySelector('[data-dk-grid]');
    const dkTabs = dokterRoot && dokterRoot.querySelector('[data-dk-tabs]');
    const dkSig = (daftar) => daftar.map((d) => d.id + ':' + d.nama + ':' + d.poli).join('|');
    let dkSigAkhir = data && Array.isArray(data.dokter) ? dkSig(data.dokter) : '';
    let dkFilter = '*';

    function terapkanSaringan(poli, animasi) {
        dkFilter = poli;
        let urut = 0;

        dkGrid.querySelectorAll('[data-dk-kartu]').forEach((k) => {
            const cocok = poli === '*' || k.dataset.poli === poli;
            k.hidden = ! cocok;
            k.classList.remove('is-masuk');

            if (cocok && animasi && ! kurangiGerak) {
                k.style.setProperty('--i', urut++);
                void k.offsetWidth; // mulai ulang animasi
                k.classList.add('is-masuk');
            }
        });

        dkTabs.querySelectorAll('[data-dk-tab]').forEach((t) => t.setAttribute('aria-pressed', String(t.dataset.dkTab === poli)));
    }

    function bangunDokter(daftar, semuaPoli) {
        const urutPoli = [...new Set(daftar.map((d) => d.poli))];
        const warna = (nama) => Math.max(0, semuaPoli.indexOf(nama)) % 6;

        dkGrid.innerHTML = daftar.map((d) => {
            const [kelas, teks] = PRAKTIK[d.praktik] || PRAKTIK.libur;

            return `<article class="hm-dkc is-${kelas}" data-dk-kartu data-id="${d.id}" data-poli="${esc(d.poli_slug)}" data-w="${warna(d.poli)}">
  <span class="hm-dkc-av" aria-hidden="true">${esc(d.inisial)}</span>
  <div class="hm-dkc-info"><h3>${esc(d.nama)}</h3><p>${esc(d.poli)}</p></div>
  <span class="hm-badge hm-badge-${kelas}" data-k="badge"${d.praktik === 'berlangsung' ? ' hidden' : ''}>${teks}</span>
  <p class="hm-dkc-jam" data-k="jam"${d.jam ? '' : ' hidden'}><i class="bi bi-clock" aria-hidden="true"></i><span>${esc(d.jam || '')}</span></p>
</article>`;
        }).join('');

        dkTabs.innerHTML = '<button type="button" class="hm-dk-tab" data-dk-tab="*" aria-pressed="true">Semua<b></b></button>'
            + urutPoli.map((nama) => {
                const slug = daftar.find((d) => d.poli === nama).poli_slug;

                return `<button type="button" class="hm-dk-tab" data-w="${warna(nama)}" data-dk-tab="${esc(slug)}" aria-pressed="false"><i aria-hidden="true"></i>${esc(nama)}<b></b></button>`;
            }).join('');

        // pilihan saringan tetap dipertahankan bila poli-nya masih ada
        const masih = dkFilter === '*' || daftar.some((d) => d.poli_slug === dkFilter);
        terapkanSaringan(masih ? dkFilter : '*', false);
    }

    function langsungDokter(d) {
        if (! dokterRoot) {
            return;
        }

        const daftar = Array.isArray(d.dokter) ? d.dokter : [];
        const semuaPoli = (d.poli || []).map((p) => p.nama);

        setTeks(dokterRoot.querySelector('[data-live-tanggal]'), d.tanggal_label || '');

        const ada = daftar.length > 0;
        setHidden(dokterRoot.querySelector('[data-dk]'), ! ada);
        setHidden(dokterRoot.querySelector('[data-dk-sum]'), ! ada);
        setHidden(dokterRoot.querySelector('[data-dk-kosong]'), ada);

        if (! ada) {
            dkSigAkhir = '';
            dkGrid.innerHTML = '';
            return;
        }

        const sig = dkSig(daftar);

        if (sig !== dkSigAkhir) {
            dkSigAkhir = sig;
            bangunDokter(daftar, semuaPoli);
        } else {
            daftar.forEach((x) => {
                const k = dkGrid.querySelector('[data-id="' + x.id + '"]');

                if (! k) {
                    return;
                }

                const [kelas, teks] = PRAKTIK[x.praktik] || PRAKTIK.libur;
                k.classList.remove('is-ok', 'is-warn', 'is-off');
                k.classList.add('is-' + kelas);

                const badge = k.querySelector('[data-k="badge"]');
                badge.className = 'hm-badge hm-badge-' + kelas;
                setTeks(badge, teks);
                setHidden(badge, x.praktik === 'berlangsung');

                const jam = k.querySelector('[data-k="jam"]');
                setTeks(jam.querySelector('span'), x.jam || '');
                setHidden(jam, ! x.jam);
            });
        }

        // angka di ringkasan dan di tab poli
        setTeks(dokterRoot.querySelector('[data-dk-total]'), String(daftar.length));
        setTeks(dokterRoot.querySelector('[data-dk-aktif]'), String(daftar.filter((x) => x.praktik === 'berlangsung').length));
        setHidden(dkTabs, new Set(daftar.map((x) => x.poli)).size < 2);

        dkTabs.querySelectorAll('[data-dk-tab]').forEach((t) => {
            const kunci = t.dataset.dkTab;
            setTeks(t.querySelector('b'), String(kunci === '*' ? daftar.length : daftar.filter((x) => x.poli_slug === kunci).length));
        });
    }

    function langsung(d) {
        langsungLayanan(d.layanan);
        langsungDokter(d);
    }

    if (dokterRoot) {
        dokterRoot.querySelector('[data-dk]').classList.add('is-js');

        // delegasi klik: tab ikut bekerja setelah dibangun ulang
        dkTabs.addEventListener('click', (e) => {
            const t = e.target.closest('[data-dk-tab]');

            if (t) {
                terapkanSaringan(t.dataset.dkTab, true);
                t.scrollIntoView({ block: 'nearest', inline: 'center', behavior: kurangiGerak ? 'auto' : 'smooth' });
            }
        });
    }
})();
