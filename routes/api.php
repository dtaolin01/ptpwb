<?php

use App\Http\Controllers\Api\CheckerApiController;
use App\Http\Controllers\Api\SupirApiController;
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

    Route::middleware([SupirApi::class, 'throttle:60,1'])->group(function () {
        Route::get('/pesanan', 'pesanan');
        Route::post('/lokasi', 'lokasi');
        Route::post('/pesanan/{id}/selesai', 'selesai')->whereNumber('id');
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

    Route::middleware([CheckerApi::class, 'throttle:120,1'])->group(function () {
        Route::get('/truk', 'truk');
        Route::get('/notifikasi', 'notifikasi');
        Route::post('/logout', 'logout');
    });
});
