<?php

namespace App\Http\Middleware;

use App\Models\SupirToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cek header "Authorization: Bearer <token>" dari aplikasi supir.
 * Kalau valid, supir & truk tersedia di $request->attributes ('supir', 'truck', 'supir_token').
 */
class SupirApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        $token = $plain
            ? SupirToken::with(['supir', 'truck'])->where('token', hash('sha256', $plain))->first()
            : null;

        // Token tidak berlaku lagi kalau admin memindahkan supir ke truk lain
        if (! $token || ! $token->supir || ! $token->truck || (int) $token->truck->supir_id !== (int) $token->supir_id) {
            return response()->json(['message' => 'Sesi berakhir, silakan login ulang.'], 401);
        }

        if (! $token->terakhir_dipakai || $token->terakhir_dipakai->lt(now()->subMinutes(10))) {
            $token->forceFill(['terakhir_dipakai' => now()])->save();
        }

        $request->attributes->set('supir_token', $token);
        $request->attributes->set('supir', $token->supir);
        $request->attributes->set('truck', $token->truck);

        return $next($request);
    }
}
