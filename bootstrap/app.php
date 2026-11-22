<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Belum login -> arahkan ke /admin, sudah login -> ke dashboard admin
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        // Tanda tangan surat jalan dilindungi link bertanda tangan (pelanggan bisa membuka link berhari-hari kemudian)
        $middleware->validateCsrfTokens(except: ['surat-jalan/*/tanda-tangan']);
        $middleware->redirectUsersTo(fn () => route('dashboard'));

        $middleware->alias([
            'disetujui' => \App\Http\Middleware\AkunDisetujui::class,
            'pemilik'   => \App\Http\Middleware\HanyaPemilik::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Gambar tanda tangan besar: jangan disimpan ke session saat validasi gagal
        $exceptions->dontFlash(['ttd', 'password', 'password_confirmation', 'current_password']);
        //
    })->create();
