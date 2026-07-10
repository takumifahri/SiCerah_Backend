<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Pakai di route: middleware('role:admin') atau middleware('role:bendahara,ketua').
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Anda tidak memiliki akses untuk aksi ini.'], 403);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun Anda telah dinonaktifkan. Hubungi Administrator.'], 403);
        }

        return $next($request);
    }
}
