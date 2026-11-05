<?php

namespace App\Http\Middleware;

use App\Models\Petugas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiPetugas
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Petugas) {
            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk petugas.',
            ], 403);
        }

        return $next($request);
    }
}
