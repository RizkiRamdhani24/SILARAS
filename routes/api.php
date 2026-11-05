<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BukuApiController;
use App\Http\Controllers\Peminjaman\PeminjamanController;
use App\Http\Middleware\EnsureApiAnggota;
use App\Http\Middleware\EnsureApiPetugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ========================================
// AUTENTIKASI API
// ========================================

Route::post('/login/anggota', [
    AuthApiController::class,
    'loginAnggota',
])->name('api.login.anggota');

Route::post('/login/petugas', [
    AuthApiController::class,
    'loginPetugas',
])->name('api.login.petugas');

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [
        AuthApiController::class,
        'logout',
    ])->name('api.logout');

    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    })->name('api.user');

    // ========================================
    // API PEMINJAMAN KHUSUS ANGGOTA
    // ========================================

    Route::middleware(EnsureApiAnggota::class)->group(function () {
        Route::post('/peminjaman', [
            PeminjamanController::class,
            'store',
        ])->name('api.peminjaman.store');
    });

    // ========================================
    // API PEMINJAMAN KHUSUS PETUGAS
    // ========================================

    Route::middleware(EnsureApiPetugas::class)->group(function () {
        Route::get('/peminjaman', [
            PeminjamanController::class,
            'index',
        ])->name('api.peminjaman.index');

        Route::post('/peminjaman/{peminjaman}/setujui', [
            PeminjamanController::class,
            'approve',
        ])->name('api.peminjaman.approve');

        Route::post('/peminjaman/{peminjaman}/tolak', [
            PeminjamanController::class,
            'reject',
        ])->name('api.peminjaman.reject');
    });
});

// ========================================
// API BUKU
// ========================================

Route::get('/buku', [
    BukuApiController::class,
    'index',
])->name('api.buku.index');

Route::get('/buku/{buku}', [
    BukuApiController::class,
    'show',
])->name('api.buku.show');
