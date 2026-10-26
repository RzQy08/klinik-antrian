<x-guest-layout>
    <h1 class="h3 mb-1">Buat akun</h1>
    <p class="text-body-secondary mb-4">Daftarkan akun baru untuk {{ config('rumahsakit.nama') }}.</p>

    <form method="POST" action="{{ route('register') }}" class="d-grid gap-3">
        @csrf

        <div>
            <label for="name" class="form-label">Nama</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="form-control @error('name') is-invalid @enderror">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Ulangi password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control @error('password_confirmation') is-invalid @enderror">
            @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Daftar</button>
    </form>

    <p class="small text-body-secondary mt-4 mb-0">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
</x-guest-layout>
