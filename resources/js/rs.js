// Bootstrap JS: dropdown, offcanvas, collapse, modal bekerja lewat atribut data-bs-*
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

// Panel antrian hari ini di beranda (hero) dan angka ringkasan
import './beranda-antrian';

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