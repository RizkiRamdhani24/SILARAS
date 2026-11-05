<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAnggota
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Anggota) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk anggota.',
            ], 403);
        }

        if ($user->status_anggota !== Anggota::STATUS_AKTIF) {
            return response()->json([
                'success' => false,
                'message' => 'Akun anggota tidak aktif.',
            ], 403);
        }

        return $next($request);
    }
}
