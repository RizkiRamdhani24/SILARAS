<?php

use App\Http\Controllers\Peminjaman\PeminjamanController;
use Illuminate\Support\Facades\Route;

// Route khusus petugas.
Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'index'])
            ->name('peminjaman.index');

        Route::post('/peminjaman/{peminjaman}/setujui', [
            PeminjamanController::class,
            'approve',
        ])->name('peminjaman.approve');

        Route::post('/peminjaman/{peminjaman}/tolak', [
            PeminjamanController::class,
            'reject',
        ])->name('peminjaman.reject');
    });

// Route khusus anggota.
Route::middleware('auth:anggota')
    ->prefix('anggota')
    ->name('anggota.')
    ->group(function () {
        Route::post('/peminjaman', [PeminjamanController::class, 'store'])
            ->name('peminjaman.store');
    });
