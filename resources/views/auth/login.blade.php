<x-guest-layout>
    <h1 class="lg-title">Masuk</h1>

    @if (session('status'))
        <div class="alert alert-success lg-alert" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger lg-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="lg-form" data-kirim novalidate>
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <div class="lg-input">
            <i class="bi bi-person lg-ikon" aria-hidden="true"></i>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" inputmode="email" class="form-control @error('email') is-invalid @enderror">
            </div>
        </div>

        <div>
            <div class="d-flex justify-content-between align-items-baseline">
                <label for="password" class="form-label">Password</label>
                @if (\Illuminate\Support\Facades\Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="lg-link">Lupa password?</a>
                @endif
            </div>
            <div class="lg-input">
                <i class="bi bi-lock lg-ikon" aria-hidden="true"></i>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror">
                <button type="button" class="lg-eye" data-lihat-sandi="password" aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <p class="lg-caps" data-caps="password" hidden>Caps Lock sedang aktif.</p>
        </div>

        <button type="submit" class="btn btn-primary btn-lg lg-submit">
            <span class="lg-submit-text">Masuk</span>
            <span class="spinner-border spinner-border-sm lg-spin" aria-hidden="true"></span>
        </button>
    </form>
</x-guest-layout>
