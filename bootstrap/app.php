<?php

use App\Http\Middleware\EnsureAnggota;
use App\Http\Middleware\EnsurePetugas;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role.anggota' => EnsureAnggota::class,
            'role.petugas' => EnsurePetugas::class,
        ]);
        $middleware->redirectGuestsTo(fn() => route('login'));
        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::guard('petugas')->check()) {
                return route('petugas.dashboard');
            }

            if (Auth::guard('anggota')->check()) {
                return route('anggota.dashboard');
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
