<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\AkunAdminController;
use App\Http\Controllers\AplikasiController;
use App\Http\Controllers\CheckerController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PabrikController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PetaArmadaController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PesanController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupirController;
use App\Http\Controllers\SuratJalanController;
use App\Http\Controllers\TrackingAdminController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TruckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [ServiceController::class, 'service'])->name('services');
Route::get('/contact', [ContactController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');
Route::get('/tracking', [TrackingController::class, 'index'])->name('tracking');

// Unduh APK aplikasi Android (pemasangan pertama & pembaruan otomatis)
// Surat jalan dibuka pelanggan dari link WhatsApp (link bertanda tangan, tanpa login)
Route::get('/surat-jalan/{pengiriman}', [SuratJalanController::class, 'publik'])
    ->middleware('signed:relative')->name('suratJalan.publik');

// Laporan untuk pelanggan dibuka dari aplikasi PWB Admin (link bertanda tangan, berlaku 2 jam, tanpa login)
Route::get('/laporan-pelanggan/{pengiriman}', [TrackingAdminController::class, 'laporanPelangganAkses'])
    ->middleware('signed:relative')->name('laporanPelanggan.akses');
Route::post('/laporan-pelanggan/{pengiriman}/kirim', [TrackingAdminController::class, 'kirimLaporan'])
    ->middleware(['signed:relative', 'throttle:20,1'])->name('laporanPelanggan.kirim');

Route::get('/unduh/{aplikasi}/{id?}', [AplikasiController::class, 'unduh'])
    ->whereIn('aplikasi', ['supir', 'checker', 'admin'])->whereNumber('id')->name('unduh.aplikasi');

/*
|--------------------------------------------------------------------------
| Halaman admin (wajib login)
|--------------------------------------------------------------------------
| Belum login -> otomatis diarahkan ke /admin (lihat bootstrap/app.php).
*/
Route::middleware(['auth', 'disetujui'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Persetujuan akun admin baru — hanya pemilik website (config app.pemilik = darrentaolin)
    Route::middleware('pemilik')->controller(AkunAdminController::class)->prefix('akun-admin')->group(function () {
        Route::get('/', 'index')->name('akunAdmin');
        Route::post('/{user}/setujui', 'setujui')->name('akunAdmin.setujui');
        Route::post('/{user}/cabut', 'cabut')->name('akunAdmin.cabut');
        Route::delete('/{user}', 'destroy')->name('akunAdmin.destroy');
    });

    // Tracking pengiriman (data yang sama dipakai halaman publik /tracking)
    // Peta semua truk (lokasi terakhir dari aplikasi supir)
    Route::get('/trackingAdmin/{pengiriman}/surat-jalan', [SuratJalanController::class, 'lihat'])->name('suratJalan');
    Route::post('/trackingAdmin/{pengiriman}/surat-jalan/kirim', [SuratJalanController::class, 'kirim'])->name('suratJalan.kirim');

    Route::get('/whatsapp', [WhatsAppController::class, 'edit'])->name('whatsapp');
    Route::put('/whatsapp', [WhatsAppController::class, 'update'])->name('whatsapp.update');
    Route::post('/whatsapp/tes', [WhatsAppController::class, 'tes'])->name('whatsapp.tes');

    Route::get('/peta-armada', [PetaArmadaController::class, 'index'])->name('petaArmada');
    Route::get('/peta-armada/data', [PetaArmadaController::class, 'data'])->name('petaArmada.data');

    Route::controller(TrackingAdminController::class)->prefix('trackingAdmin')->group(function () {
        Route::get('/', 'index')->name('trackingAdmin');
        Route::get('/create', 'create')->name('trackingAdmin.create');
        Route::post('/', 'store')->name('trackingAdmin.store');
        Route::get('/{pengiriman}/edit', 'edit')->name('trackingAdmin.edit');
        Route::get('/{pengiriman}/laporan', 'laporan')->name('trackingAdmin.laporan');
        Route::get('/{pengiriman}/laporan-pelanggan', 'laporanPelanggan')->name('trackingAdmin.laporanPelanggan');
        Route::post('/{pengiriman}/laporan-pelanggan/kirim', 'kirimLaporan')->name('trackingAdmin.kirimLaporan');
        Route::put('/{pengiriman}', 'update')->name('trackingAdmin.update');
        Route::delete('/{pengiriman}', 'destroy')->name('trackingAdmin.destroy');
    });

    // Truck (halaman "Truck & Supir"). Nama route index tetap 'truck' supaya sidebar tidak berubah.
    Route::controller(TruckController::class)->prefix('truck-supir')->group(function () {
        Route::get('/', 'index')->name('truck');
        Route::get('/create', 'create')->name('truck.create');
        Route::post('/', 'store')->name('truck.store');
        Route::get('/{truck}/edit', 'edit')->name('truck.edit');
        Route::put('/{truck}', 'update')->name('truck.update');
        Route::delete('/{truck}', 'destroy')->name('truck.destroy');
    });

    Route::controller(SupirController::class)->prefix('supir')->group(function () {
        Route::get('/', 'index')->name('supir');
        Route::get('/create', 'create')->name('supir.create');
        Route::post('/', 'store')->name('supir.store');
        Route::get('/{supir}/edit', 'edit')->name('supir.edit');
        Route::put('/{supir}', 'update')->name('supir.update');
        Route::delete('/{supir}', 'destroy')->name('supir.destroy');
    });

    Route::controller(PelangganController::class)->prefix('pelanggan')->group(function () {
        Route::get('/', 'index')->name('pelanggan');
        Route::get('/create', 'create')->name('pelanggan.create');
        Route::post('/', 'store')->name('pelanggan.store');
        Route::get('/{pelanggan}/edit', 'edit')->name('pelanggan.edit');
        Route::put('/{pelanggan}', 'update')->name('pelanggan.update');
        Route::delete('/{pelanggan}', 'destroy')->name('pelanggan.destroy');
    });

    // Akun petugas pabrik untuk aplikasi PWB Checker
    Route::controller(CheckerController::class)->prefix('checker')->group(function () {
        Route::get('/', 'index')->name('checker');
        Route::get('/create', 'create')->name('checker.create');
        Route::post('/', 'store')->name('checker.store');
        Route::get('/{checker}/edit', 'edit')->name('checker.edit');
        Route::put('/{checker}', 'update')->name('checker.update');
        Route::delete('/{checker}', 'destroy')->name('checker.destroy');
    });

    // Unggah APK baru aplikasi Supir / Checker
    Route::get('/aplikasi', [AplikasiController::class, 'index'])->name('aplikasi');
    Route::post('/aplikasi', [AplikasiController::class, 'store'])->name('aplikasi.store');
    Route::delete('/aplikasi/{versi}', [AplikasiController::class, 'destroy'])->name('aplikasi.destroy');

    // Lokasi pabrik (tempat muat) + radius deteksi otomatis
    Route::get('/pabrik', [PabrikController::class, 'edit'])->name('pabrik');
    Route::put('/pabrik', [PabrikController::class, 'update'])->name('pabrik.update');

    // Pesan masuk dari form halaman Contact
    Route::controller(PesanController::class)->prefix('pesan')->group(function () {
        Route::get('/', 'index')->name('pesan');
        Route::get('/{pesan}', 'show')->name('pesan.show');
        Route::delete('/{pesan}', 'destroy')->name('pesan.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Login / register admin
|--------------------------------------------------------------------------
| Hanya bisa dibuka dengan mengetik langsung:
|   domain/admin            -> Sign In
|   domain/admin/register   -> Sign Up
| Tidak ada link/tombol dari halaman publik yang mengarah ke sini.
*/
Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
    });

    Route::middleware('auth')->group(function () {
        // Dashboard admin yang sebenarnya ada di /dashboard
        Route::redirect('/dashboard', '/dashboard')->name('dashboard');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});
