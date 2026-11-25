// Bootstrap JS: dropdown, offcanvas, collapse, modal bekerja lewat atribut data-bs-*
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Panel antrian hari ini di beranda (hero) dan angka ringkasan
import './beranda-antrian';

// Tiket antrian tersimpan di perangkat, chip "Tiket saya", unduh kartu
import './tiket-saya';

// Navbar melayang: bayangan saat halaman digulir, dan indikator yang bergeser ke tautan yang disorot
(function () {
    const header = document.querySelector('[data-nb]');

    if (! header) {
        return;
    }

    const saatGulir = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    saatGulir();
    window.addEventListener('scroll', saatGulir, { passive: true });

    const daftar = header.querySelector('[data-nb-links]');
    const penanda = daftar ? daftar.querySelector('.nb-ind') : null;

    if (! daftar || ! penanda) {
        return;
    }

    const layarLebar = window.matchMedia('(min-width: 1200px)');
    const aktif = () => daftar.querySelector('.nb-link.active');

    function pindah(el) {
        if (! el || ! layarLebar.matches) {
            penanda.style.opacity = 0;

            return;
        }

        penanda.style.opacity = 1;
        penanda.style.width = el.offsetWidth + 'px';
        penanda.style.height = el.offsetHeight + 'px';
        penanda.style.transform = 'translate(' + el.offsetLeft + 'px, ' + el.offsetTop + 'px)';
    }

    header.classList.add('nb-js');

    daftar.querySelectorAll('.nb-link').forEach(function (a) {
        a.addEventListener('mouseenter', function () { pindah(a); });
        a.addEventListener('focus', function () { pindah(a); });
    });
    daftar.addEventListener('mouseleave', function () { pindah(aktif()); });
    daftar.addEventListener('focusout', function () { pindah(aktif()); });
    window.addEventListener('resize', function () { pindah(aktif()); });
    window.addEventListener('load', function () { pindah(aktif()); });
    layarLebar.addEventListener('change', function () { pindah(aktif()); });

    pindah(aktif());
})();


// Angka statistik beranda berhitung dari 0 saat terlihat
(function () {
    const daftar = document.querySelectorAll('.bn-stat .numeral');

    if (! daftar.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const pengamat = new IntersectionObserver(function (entri) {
        entri.forEach(function (e) {
            if (! e.isIntersecting) return;

            const el = e.target;
            const target = parseInt(el.textContent, 10);
            pengamat.unobserve(el);

            if (isNaN(target) || target === 0) return;

            const mulai = performance.now(), durasi = 900;
            const langkah = function (t) {
                const p = Math.min(1, (t - mulai) / durasi);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(langkah);
            };
            requestAnimationFrame(langkah);
        });
    }, { threshold: 0.4 });

    daftar.forEach(function (el) { pengamat.observe(el); });
})();
// Indikator memuat: garis tipis saat berpindah halaman, layar penuh saat masuk atau keluar
(function () {
    const layar = document.querySelector('[data-pm]');
    const garis = document.querySelector('[data-pm-bar]');

    if (! layar || ! garis) {
        return;
    }

    let tunda = null;

    const reset = () => {
        clearTimeout(tunda);
        garis.classList.remove('is-on');
        layar.classList.remove('is-on');
        layar.hidden = true;
    };

    const tampilLayar = (teks) => {
        clearTimeout(tunda);
        layar.querySelector('[data-pm-text]').textContent = teks || 'Memuat';
        layar.hidden = false;
        requestAnimationFrame(() => requestAnimationFrame(() => layar.classList.add('is-on')));
    };

    const tampilGaris = () => {
        clearTimeout(tunda);
        tunda = setTimeout(() => garis.classList.add('is-on'), 120);
    };

    document.addEventListener('submit', (e) => {
        const f = e.target;

        if (! (f instanceof HTMLFormElement) || e.defaultPrevented) {
            return;
        }

        const aksi = f.getAttribute('action') || '';

        if (f.hasAttribute('data-pemuat')) {
            tampilLayar(f.getAttribute('data-pemuat'));
        } else if (/\/demo\/masuk\//.test(aksi)) {
            tampilLayar('Masuk');
        } else if (/\/logout$/.test(aksi)) {
            tampilLayar('Keluar');
        }
    });

    document.addEventListener('click', (e) => {
        const a = e.target instanceof Element ? e.target.closest('a[href]') : null;

        if (! a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        if ((a.target && a.target !== '_self') || a.hasAttribute('download') || a.hasAttribute('data-bs-toggle') || a.hasAttribute('wire:click')) {
            return;
        }

        const url = new URL(a.href, location.href);

        if (url.origin !== location.origin || url.protocol.indexOf('http') !== 0) {
            return;
        }

        if (url.pathname === location.pathname && url.search === location.search) {
            return;
        }

        tampilGaris();
    });

    // Kembali lewat tombol back: pastikan indikator tidak tertinggal
    window.addEventListener('pageshow', reset);
})();
