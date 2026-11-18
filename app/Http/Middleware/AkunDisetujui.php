<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Akun admin yang belum (atau tidak lagi) disetujui pemilik langsung dikeluarkan. */
class AkunDisetujui
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && ! Auth::user()->disetujui) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['identifier' => 'Akun Anda belum disetujui atau aksesnya sudah dicabut oleh pemilik.']);
        }

        return $next($request);
    }
}
