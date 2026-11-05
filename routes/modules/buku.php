<?php

use App\Http\Controllers\Buku\BukuController;
use Illuminate\Support\Facades\Route;

// ROUTE PETUGAS
Route::prefix('petugas')
    ->name('petugas.')
    ->middleware('role.petugas')
    ->group(function () {

        Route::get('/buku', [BukuController::class, 'index'])
            ->name('buku.index');

        Route::post('/buku', [BukuController::class, 'store'])
            ->name('buku.store');

        Route::get('/kelola-buku', [BukuController::class, 'halaman'])
            ->name('kelola-buku.index');

        Route::get('/kelola-buku/tambah', [BukuController::class, 'create'])
            ->name('kelola-buku.create');

        Route::get('/kelola-buku/{id}/edit', [BukuController::class, 'edit'])
            ->name('kelola-buku.edit');

        Route::put('/kelola-buku/{id}', [BukuController::class, 'update'])
            ->name('kelola-buku.update');

        Route::delete('/kelola-buku/{id}', [BukuController::class, 'destroy'])
            ->name('kelola-buku.destroy');
    });

