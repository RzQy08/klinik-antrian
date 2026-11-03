// Bootstrap JS: dropdown, offcanvas, collapse, modal bekerja lewat atribut data-bs-*
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

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
