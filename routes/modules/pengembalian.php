
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pengembalian\PengembalianController;

// Route anggota
Route::middleware('auth:anggota')
    ->prefix('anggota')
    ->name('anggota.')
    ->group(function () {
        Route::get('/pengembalian', [PengembalianController::class, 'indexAnggota'])
            ->name('pengembalian.index');

        Route::post('/pengembalian/{id}', [PengembalianController::class, 'ajukan'])
            ->name('pengembalian.ajukan');
    });

// Route petugas
Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/pengembalian', [PengembalianController::class, 'indexPetugas'])
            ->name('pengembalian.index');

        Route::patch('/pengembalian/{id}/setujui', [PengembalianController::class, 'setujui'])
            ->name('pengembalian.setujui');
    });
