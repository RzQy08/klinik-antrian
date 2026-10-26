<x-app-layout>
    <x-slot name="header">
        <h1 class="h2 mb-1">Dashboard admin</h1>
        <p class="text-body-secondary mb-0">Kelola data master dan pantau jalannya antrian.</p>
    </x-slot>

    @include('partials.flash')

    <div class="row g-3 mb-5">
        <div class="col-12 col-sm-4">@include('partials.stat', ['ikon' => 'building', 'label' => 'Poli terdaftar', 'nilai' => $ringkasan['jumlah_poli'], 'warna' => 'navy'])</div>
        <div class="col-6 col-sm-4">@include('partials.stat', ['ikon' => 'person-badge', 'label' => 'Dokter terdaftar', 'nilai' => $ringkasan['jumlah_dokter'], 'warna' => 'primary'])</div>
        <div class="col-6 col-sm-4">@include('partials.stat', ['ikon' => 'people', 'label' => 'Akun pengguna', 'nilai' => $ringkasan['jumlah_user'], 'warna' => 'success'])</div>
    </div>

    <h2 class="h4 mb-3">Data master</h2>
    <div class="row g-3 mb-5">
        <div class="col-md-6">
            <a href="{{ route('admin.poli.index') }}" class="card card-tile h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon-chip icon-chip-primary"><i class="bi bi-building"></i></span>
                    <div><div class="fw-semibold">Kelola poli</div><div class="small text-body-secondary">Tambah, ubah, atau nonaktifkan poli</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('admin.dokter.index') }}" class="card card-tile h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon-chip icon-chip-primary"><i class="bi bi-person-badge"></i></span>
                    <div><div class="fw-semibold">Kelola dokter</div><div class="small text-body-secondary">Data dokter, jadwal praktik, dan akun login</div></div>
                </div>
            </a>
        </div>
    </div>

    <h2 class="h4 mb-3">Operasional antrian</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <a href="{{ route('antrian.index') }}" class="card card-tile h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon-chip icon-chip-success"><i class="bi bi-people"></i></span>
                    <div><div class="fw-semibold">Antrian hari ini</div><div class="small text-body-secondary">Pantau dan kelola antrian tiap poli</div></div>
                </div>
            </a>
        </div>
        <div class="col-md-6">
            <a href="{{ route('antrian.analitik') }}" class="card card-tile h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="icon-chip icon-chip-warning"><i class="bi bi-bar-chart-line"></i></span>
                    <div><div class="fw-semibold">Analitik</div><div class="small text-body-secondary">Waktu tunggu teori dibanding data riil</div></div>
                </div>
            </a>
        </div>
    </div>
</x-app-layout>
