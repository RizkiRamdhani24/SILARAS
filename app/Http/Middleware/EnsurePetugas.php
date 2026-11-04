<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePetugas
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('anggota')->check()) {
            abort(403);
        }

        if (! Auth::guard('petugas')->check()) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
