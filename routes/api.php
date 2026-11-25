<?php

use App\Http\Controllers\AplikasiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\CheckerApiController;
use App\Http\Controllers\Api\SinkronController;
use App\Http\Controllers\Api\SupirApiController;
use App\Http\Middleware\AdminApi;
use App\Http\Middleware\CheckerApi;
use App\Http\Middleware\SupirApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API aplikasi Android supir  (prefix otomatis: /api)
|--------------------------------------------------------------------------
*/
Route::prefix('supir')->controller(SupirApiController::class)->group(function () {
    Route::post('/login', 'login')->middleware('throttle:10,1');

    // Batas per jenis aplikasi (bukan dibagi dengan aplikasi lain). Banyak HP operator yang sama memakai IP yang sama,
    // jadi batasnya longgar; aplikasi supir mengirim lokasi ±1x per menit + antrean saat sinyal kembali.
    Route::middleware([SupirApi::class, 'throttle:600,1,supir-api'])->group(function () {
        Route::get('/pesanan', 'pesanan');
        Route::post('/lokasi', 'lokasi');
        Route::post('/pesanan/{id}/selesai', 'selesai')->whereNumber('id');
        Route::post('/pesanan/{id}/foto', 'kirimFoto')->whereNumber('id');
        Route::delete('/pesanan/{id}/foto/{foto}', 'hapusFoto')->whereNumber(['id', 'foto']);
        Route::post('/logout', 'logout');
    });
});

/*
|--------------------------------------------------------------------------
| API aplikasi Android PWB Checker (petugas di pabrik)
|--------------------------------------------------------------------------
*/
Route::prefix('checker')->controller(CheckerApiController::class)->group(function () {
    Route::post('/login', 'login')->middleware('throttle:10,1');

    Route::middleware([CheckerApi::class, 'throttle:300,1,checker-api'])->group(function () {
        Route::get('/truk', 'truk');
        Route::get('/notifikasi', 'notifikasi');
        Route::post('/logout', 'logout');
    });
});

/*
| Versi terbaru aplikasi Android (dicek aplikasi saat dibuka untuk auto update)
|   GET /api/aplikasi/supir/versi   |   GET /api/aplikasi/checker/versi
*/
Route::get('/aplikasi/{aplikasi}/versi', [AplikasiController::class, 'versi'])
    ->whereIn('aplikasi', ['supir', 'checker', 'admin'])->middleware('throttle:60,1');

/*
|--------------------------------------------------------------------------
| API aplikasi Android PWB Admin (login dengan akun admin website)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->controller(AdminApiController::class)->group(function () {
    Route::post('/login', 'login')->middleware('throttle:10,1,admin-login');

    Route::middleware([AdminApi::class, 'throttle:240,1,admin-api'])->group(function () {
        Route::get('/saya', 'saya');
        Route::post('/logout', 'logout');
        Route::get('/ringkasan', 'ringkasan');
        Route::get('/armada', 'armada');
        Route::get('/notifikasi', 'notifikasi');

        Route::get('/pengiriman', 'daftarPengiriman');
        Route::get('/pengiriman/opsi', 'opsiPengiriman');
        Route::get('/pengiriman/{id}', 'detailPengiriman')->whereNumber('id');
        Route::post('/pengiriman', 'simpanPengiriman');
        Route::put('/pengiriman/{id}', 'ubahPengiriman')->whereNumber('id');
        Route::post('/pengiriman/{id}/selesai', 'selesaikanPengiriman')->whereNumber('id');
        Route::post('/pengiriman/{id}/surat-jalan', 'kirimSuratJalan')->whereNumber('id');
        Route::delete('/pengiriman/{id}', 'hapusPengiriman')->whereNumber('id');

        foreach (['truk' => 'Truk', 'supir' => 'Supir', 'pelanggan' => 'Pelanggan', 'checker' => 'Checker'] as $url => $nama) {
            Route::get("/{$url}", "daftar{$nama}");
            Route::post("/{$url}", "simpan{$nama}");
            Route::put("/{$url}/{id}", "ubah{$nama}")->whereNumber('id');
            Route::delete("/{$url}/{id}", "hapus{$nama}")->whereNumber('id');
        }

        Route::get('/pesan', 'daftarPesan');
        Route::post('/pesan/{id}/baca', 'bacaPesan')->whereNumber('id');
        Route::delete('/pesan/{id}', 'hapusPesan')->whereNumber('id');

        // Salin data (baca-saja) ke PostgreSQL — hanya pemilik website
        Route::get('/sinkron', [SinkronController::class, 'tarik']);

        Route::get('/akun', 'daftarAkun');
        Route::post('/akun/{id}/setujui', 'setujuiAkun')->whereNumber('id');
        Route::delete('/akun/{id}', 'hapusAkun')->whereNumber('id');
    });
});
