<?php

use App\Http\Controllers\Api\SupirApiController;
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
