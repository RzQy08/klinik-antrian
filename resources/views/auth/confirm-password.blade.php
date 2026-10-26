<x-guest-layout>
    <h1 class="h3 mb-1">Konfirmasi password</h1>
    <p class="text-body-secondary mb-4">Ini area yang diamankan. Masukkan password Anda untuk melanjutkan.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="d-grid gap-3">
        @csrf

        <div>
            <label for="password" class="form-label">Password</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="form-control @error('password') is-invalid @enderror">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Konfirmasi</button>
    </form>
</x-guest-layout>
