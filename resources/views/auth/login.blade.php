<x-guest-layout>
    <h1 class="h3 mb-1">Masuk</h1>
    <p class="text-body-secondary mb-4">Gunakan akun staf {{ config('rumahsakit.nama') }}.</p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="d-grid gap-3">
        @csrf

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-end gap-2">
            @if (\Illuminate\Support\Facades\Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="small">Lupa password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Masuk</button>
    </form>

    <p class="small text-body-secondary mt-4 mb-0">Pasien? <a href="{{ route('antrian.create') }}">Ambil nomor antrian</a> tanpa perlu masuk.</p>
</x-guest-layout>
