<x-guest-layout>
    <h1 class="h3 mb-1">Lupa password</h1>
    <p class="text-body-secondary mb-4">Masukkan email akun Anda. Kami kirim tautan untuk membuat password baru.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="d-grid gap-3">
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Kirim tautan</button>
    </form>

    <p class="small text-body-secondary mt-4 mb-0"><a href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
</x-guest-layout>
