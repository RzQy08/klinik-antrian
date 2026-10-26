<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
<div class="d-flex min-vh-dvh">

    <div class="d-none d-lg-flex w-50 p-3">
        <div class="board board-pattern w-100 p-5 d-flex flex-column justify-content-between" style="border-radius: 1.75rem">
            <a href="{{ route('beranda') }}" class="text-decoration-none">@include('partials.brand', ['terang' => true])</a>

            <div>
                <h1 class="display-5 fw-bold text-white lh-sm mb-3">{{ config('rumahsakit.nama_lengkap', 'Rumah Sakit Bersama') }}</h1>
                <p class="fs-5 board-muted mb-0" style="max-width: 28rem">Akses khusus petugas, dokter, dan admin untuk mengelola antrian poliklinik.</p>
            </div>

            <p class="small board-muted mb-0">Pantau antrian, panggil pasien, dan lihat perkiraan waktu tunggu dalam satu tempat.</p>
        </div>
    </div>

    <div class="d-flex flex-grow-1 align-items-center justify-content-center p-4">
        <div class="w-100" style="max-width: 26rem">
            <a href="{{ route('beranda') }}" class="d-lg-none d-inline-block mb-4 text-decoration-none">@include('partials.brand')</a>
            {{ $slot }}
        </div>
    </div>
</div>
</body>
</html>
