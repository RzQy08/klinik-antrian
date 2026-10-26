<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\DokterController;
use App\Http\Controllers\Admin\JadwalPraktikController;
use App\Http\Controllers\Admin\PoliController;
use App\Http\Controllers\AnalitikController;
use App\Http\Controllers\AntrianController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Beranda publik & masuk cepat (khusus lokal)
|--------------------------------------------------------------------------
*/
Route::get('/', [BerandaController::class, 'index'])->name('beranda');

Route::post('/demo/masuk/{role}', DemoLoginController::class)
    ->whereIn('role', ['admin', 'petugas', 'dokter'])
    ->name('demo.masuk');

/*
|--------------------------------------------------------------------------
| Setelah login, arahkan sesuai peran
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Akses publik (pasien / kios self-service)
|--------------------------------------------------------------------------
| Route statis ("/antrian/ambil") harus didaftarkan SEBELUM route dinamis
| yang bentuknya mirip ("/antrian/{poli}").
*/
Route::get('/antrian/ambil', [AntrianController::class, 'create'])->name('antrian.create');
Route::post('/antrian/ambil', [AntrianController::class, 'store'])->middleware('throttle:20,1')->name('antrian.store');
Route::get('/antrian/tiket/{antrian:kode_tiket}', [AntrianController::class, 'tiket'])->name('antrian.tiket');
Route::get('/antrian/display/{poli}', [AntrianController::class, 'display'])->name('antrian.display');
Route::view('/antrian/layar', 'antrian.layar')->name('antrian.layar');

/*
|--------------------------------------------------------------------------
| Area petugas & dokter (butuh login + peran yang sesuai)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/antrian', [AntrianController::class, 'index'])
        ->middleware('role:admin,petugas')
        ->name('antrian.index');

    Route::get('/antrian/analitik', [AnalitikController::class, 'index'])
        ->middleware('role:admin,petugas')
        ->name('antrian.analitik');

    Route::get('/antrian/dokter', [AntrianController::class, 'dokterDashboard'])
        ->middleware('role:dokter')
        ->name('antrian.dokter');

    Route::get('/antrian/kelola/{poli}', [AntrianController::class, 'kelola'])
        ->middleware('role:admin,petugas')
        ->name('antrian.kelola');
    Route::post('/antrian/kelola/{poli}/panggil', [AntrianController::class, 'panggilBerikutnya'])
        ->middleware('role:admin,petugas')
        ->name('antrian.panggil');

    Route::post('/antrian/{antrian}/panggil-ulang', [AntrianController::class, 'panggilUlang'])
        ->middleware('role:admin,petugas')
        ->name('antrian.panggil-ulang');

    // Dipakai petugas (halaman kelola) maupun dokter (dashboard dokter)
    Route::post('/antrian/{antrian}/mulai-layani', [AntrianController::class, 'mulaiLayani'])
        ->middleware('role:admin,petugas,dokter')
        ->name('antrian.mulai-layani');
    Route::post('/antrian/{antrian}/selesai', [AntrianController::class, 'selesaiLayani'])
        ->middleware('role:admin,petugas,dokter')
        ->name('antrian.selesai');
    Route::post('/antrian/{antrian}/tidak-hadir', [AntrianController::class, 'tidakHadir'])
        ->middleware('role:admin,petugas,dokter')
        ->name('antrian.tidak-hadir');
});

/*
|--------------------------------------------------------------------------
| Area admin - data master (khusus role admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->prefix('admin/dashboard')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::resource('poli', PoliController::class);
    Route::resource('dokter', DokterController::class)->except(['show']);

    Route::get('dokter/{dokter}/jadwal', [JadwalPraktikController::class, 'index'])
        ->name('dokter.jadwal.index');
    Route::post('dokter/{dokter}/jadwal', [JadwalPraktikController::class, 'store'])
        ->name('dokter.jadwal.store');
    Route::delete('jadwal/{jadwal}', [JadwalPraktikController::class, 'destroy'])
        ->name('jadwal.destroy');
});

require __DIR__.'/auth.php';
