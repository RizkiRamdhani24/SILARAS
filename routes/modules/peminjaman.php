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

        Route::patch('/peminjaman/{id}/batalkan', [PeminjamanController::class, 'batalkan'])
            ->whereNumber('id')
            ->name('peminjaman.batalkan');
    });

// ROUTE UNTUK PETUGAS
Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'indexPetugas'])
            ->name('peminjaman.index');

        Route::get('/peminjaman/{id}', [PeminjamanController::class, 'showPetugas'])
            ->whereNumber('id')
            ->name('peminjaman.show');

        Route::patch('/peminjaman/{id}/setujui', [PeminjamanController::class, 'setujui'])
            ->name('peminjaman.setujui');

        Route::patch('/peminjaman/{id}/tolak', [PeminjamanController::class, 'tolak'])
            ->name('peminjaman.tolak');
    });