{{-- Skrip layar antrian: layar penuh, tetap menyala, dan pemulihan otomatis bila pembaruan berhenti --}}
<script>
(function () {
    var h = document.documentElement, last = Date.now(), idle;

    // layar penuh (tombol, tombol F, atau klik ganda)
    var req = h.requestFullscreen || h.webkitRequestFullscreen;
    if (!req) h.classList.add('no-fs');
    function penuh() { return document.fullscreenElement || document.webkitFullscreenElement; }
    function toggle() {
        if (!req) return;
        if (penuh()) { (document.exitFullscreen || document.webkitExitFullscreen).call(document); }
        else { try { var p = req.call(h); p && p.catch && p.catch(function () {}); } catch (e) {} }
    }
    document.addEventListener('click', function (e) { if (e.target.closest('[data-fs]')) toggle(); });
    document.addEventListener('dblclick', function (e) { if (!e.target.closest('a,button')) toggle(); });
    document.addEventListener('keydown', function (e) { if ((e.key === 'f' || e.key === 'F') && !e.ctrlKey && !e.metaKey && !e.altKey) toggle(); });
    ['fullscreenchange', 'webkitfullscreenchange'].forEach(function (n) { document.addEventListener(n, function () { h.classList.toggle('is-fs', !!penuh()); }); });

    // sembunyikan kursor dan tombol setelah diam beberapa detik
    function bangun() { h.classList.remove('is-idle'); clearTimeout(idle); idle = setTimeout(function () { h.classList.add('is-idle'); }, 3500); }
    ['mousemove', 'touchstart', 'keydown'].forEach(function (n) { document.addEventListener(n, bangun, { passive: true }); }); bangun();

    // jaga layar tetap menyala
    var kunci;
    function nyala() { try { navigator.wakeLock && navigator.wakeLock.request('screen').then(function (l) { kunci = l; }).catch(function () {}); } catch (e) {} }
    nyala(); document.addEventListener('visibilitychange', function () { if (!document.hidden) { nyala(); segarkan(); } });

    // status sambungan: atribut data-t di akar komponen berubah setiap pembaruan sukses
    function sambung(ok) { h.setAttribute('data-conn', ok ? 'on' : 'off'); }
    function pantau() {
        var root = document.querySelector('.ly[data-t]');
        if (!root) { return setTimeout(pantau, 500); }
        new MutationObserver(function () { last = Date.now(); sambung(true); }).observe(root, { attributes: true, attributeFilter: ['data-t'] });
    }
    pantau();
    function segarkan() { try { window.Livewire && Livewire.all().forEach(function (c) { c.$refresh(); }); } catch (e) {} }

    // bila pembaruan terhenti (sesi habis, jaringan putus, tab dibekukan), coba segarkan lalu muat ulang halaman
    setInterval(function () {
        var d = Date.now() - last;
        if (d > 8000) { sambung(false); segarkan(); }
        if (d > 25000) {
            fetch(location.href, { method: 'HEAD', cache: 'no-store' }).then(function (r) { if (r.ok) location.reload(); }).catch(function () {});
        }
    }, 5000);
})();
</script>
