<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body class="lg-body">
<div class="lg-shell">
    <div class="lg-card">

        <aside class="lg-side">
            <a href="{{ route('beranda') }}" class="lg-side-brand" aria-label="Ke beranda">@include('partials.brand', ['terang' => true])</a>
            <span class="lg-bola lg-bola-a" aria-hidden="true"></span>
            <span class="lg-bola lg-bola-b" aria-hidden="true"></span>
        </aside>

        <main class="lg-main">
            <div class="lg-form-wrap">
                {{ $slot }}
            </div>
        </main>
    </div>

    <a href="{{ route('beranda') }}" class="lg-kembali"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
</div>

<script>
(function () {
    document.querySelectorAll('[data-lihat-sandi]').forEach(function (tombol) {
        var input = document.getElementById(tombol.getAttribute('data-lihat-sandi'));
        if (!input) { return; }
        tombol.addEventListener('click', function () {
            var buka = input.type === 'password';
            input.type = buka ? 'text' : 'password';
            tombol.setAttribute('aria-pressed', buka ? 'true' : 'false');
            tombol.setAttribute('aria-label', buka ? 'Sembunyikan password' : 'Tampilkan password');
            tombol.querySelector('i').className = buka ? 'bi bi-eye-slash' : 'bi bi-eye';
            input.focus();
        });
    });

    document.querySelectorAll('[data-caps]').forEach(function (info) {
        var input = document.getElementById(info.getAttribute('data-caps'));
        if (!input) { return; }
        var cek = function (e) { info.hidden = !(e.getModifierState && e.getModifierState('CapsLock')); };
        input.addEventListener('keyup', cek);
        input.addEventListener('keydown', cek);
        input.addEventListener('blur', function () { info.hidden = true; });
    });

    document.querySelectorAll('form[data-kirim]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var tombol = form.querySelector('[type=submit]');
            if (!tombol || tombol.disabled) { return; }
            tombol.classList.add('is-loading');
            setTimeout(function () { tombol.disabled = true; }, 0);
        });
    });
})();
</script>
@include('partials.dev-masuk', ['kanan' => true])
</body>
</html>
