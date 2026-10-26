<x-guest-layout>
    <h1 class="h3 mb-1">Verifikasi email</h1>
    <p class="text-body-secondary mb-4">Terima kasih sudah mendaftar. Klik tautan yang kami kirim ke email Anda untuk memverifikasi akun. Belum menerima? Kami kirim ulang.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success">Tautan verifikasi baru sudah dikirim ke email Anda.</div>
    @endif

    <div class="d-flex flex-wrap align-items-center gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary">Kirim ulang email verifikasi</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-body-secondary">Keluar</button>
        </form>
    </div>
</x-guest-layout>
