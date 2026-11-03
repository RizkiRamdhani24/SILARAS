<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Peminjaman\PeminjamanController;

// ROUTE UNTUK ANGGOTA
Route::middleware('auth:anggota')
    ->prefix('anggota')
    ->name('anggota.')
    ->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'indexAnggota'])
            ->name('peminjaman.index');

        Route::post('/peminjaman', [PeminjamanController::class, 'store'])
            ->name('peminjaman.store');
    });

// ROUTE UNTUK PETUGAS
Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'indexPetugas'])
            ->name('peminjaman.index');

        Route::patch('/peminjaman/{id}/setujui', [PeminjamanController::class, 'setujui'])
            ->name('peminjaman.setujui');

        Route::patch('/peminjaman/{id}/tolak', [PeminjamanController::class, 'tolak'])
            ->name('peminjaman.tolak');
    });