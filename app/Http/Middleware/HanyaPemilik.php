<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Halaman yang hanya boleh dibuka pemilik website (config app.pemilik). */
class HanyaPemilik
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPemilik(), 403, 'Hanya pemilik website yang bisa membuka halaman ini.');

        return $next($request);
    }
}
