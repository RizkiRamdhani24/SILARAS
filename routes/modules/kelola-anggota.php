<?php

use App\Http\Controllers\Anggota\KelolaAnggotaController;
use Illuminate\Support\Facades\Route;

Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/anggota', [KelolaAnggotaController::class, 'index'])
            ->name('anggota');

        Route::patch('/anggota/{id}/nonaktifkan', [KelolaAnggotaController::class, 'nonaktifkan'])
            ->whereNumber('id')
            ->name('anggota.nonaktifkan');
    });
