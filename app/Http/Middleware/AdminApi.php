<?php

namespace App\Http\Middleware;

use App\Models\AdminToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Cek "Authorization: Bearer <token>" dari aplikasi PWB Admin. Akun harus masih disetujui pemilik. */
class AdminApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        $token = $plain
            ? AdminToken::with('user')->where('token', hash('sha256', $plain))->first()
            : null;

        if (! $token || ! $token->user || ! $token->user->disetujui) {
            return response()->json(['message' => 'Sesi berakhir, silakan login ulang.'], 401);
        }

        if (! $token->terakhir_dipakai || $token->terakhir_dipakai->lt(now()->subMinutes(10))) {
            $token->forceFill(['terakhir_dipakai' => now()])->save();
        }

        $request->attributes->set('admin_token', $token);
        $request->setUserResolver(fn () => $token->user);

        return $next($request);
    }
}
