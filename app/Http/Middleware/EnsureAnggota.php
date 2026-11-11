<?php

namespace App\Http\Middleware;

use App\Models\Anggota;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnggota
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('petugas')->check()) {
            abort(403);
        }

        $guard = Auth::guard('anggota');

        if (! $guard->check()) {
            return redirect()->route('login');
        }

        if ($guard->user()->status_anggota === Anggota::STATUS_NONAKTIF) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Akun Anda nonaktif, hubungi petugas perpustakaan.']);
        }

        return $next($request);
    }
}
