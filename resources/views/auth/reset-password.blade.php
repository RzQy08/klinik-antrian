<x-guest-layout>
    <h1 class="h3 mb-1">Password baru</h1>
    <p class="text-body-secondary mb-4">Buat password baru untuk akun Anda.</p>

    <form method="POST" action="{{ route('password.store') }}" class="d-grid gap-3">
        @csrf

        <input type="hidden" name="token" value="{{ request()->route('token') }}">

        <div>
            <label for="email" class="form-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', request()->email) }}" required autofocus autocomplete="username" class="form-control @error('email') is-invalid @enderror">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password" class="form-label">Password baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="form-label">Ulangi password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="form-control @error('password_confirmation') is-invalid @enderror">
            @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Simpan password</button>
    </form>
</x-guest-layout>
