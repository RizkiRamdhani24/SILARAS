<?php

use App\Http\Controllers\Buku\BukuController;
use Illuminate\Support\Facades\Route;

Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/buku', [BukuController::class, 'index'])
            ->name('buku.index');

        Route::post('/buku', [BukuController::class, 'store'])
            ->name('buku.store');

        Route::put('/buku/{buku}', [BukuController::class, 'update'])
            ->name('buku.update');

        Route::delete('/buku/{buku}', [BukuController::class, 'destroy'])
            ->name('buku.destroy');
    });
