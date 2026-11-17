<?php

namespace App\Http\Middleware;

use App\Models\CheckerToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cek "Authorization: Bearer <token>" dari aplikasi PWB Checker. */
class CheckerApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        $token = $plain
            ? CheckerToken::with('checker')->where('token', hash('sha256', $plain))->first()
            : null;

        if (! $token || ! $token->checker || ! $token->checker->aktif) {
            return response()->json(['message' => 'Sesi berakhir, silakan login ulang.'], 401);
        }

        if (! $token->terakhir_dipakai || $token->terakhir_dipakai->lt(now()->subMinutes(10))) {
            $token->forceFill(['terakhir_dipakai' => now()])->save();
        }

        $request->attributes->set('checker_token', $token);
        $request->attributes->set('checker', $token->checker);

        return $next($request);
    }
}
