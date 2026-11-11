<?php

use App\Http\Controllers\Anggota\NotifikasiController;
use Illuminate\Support\Facades\Route;

Route::middleware('role.anggota')
    ->prefix('anggota/notifikasi')
    ->name('anggota.notifikasi.')
    ->group(function () {
        Route::get('/', [NotifikasiController::class, 'index'])
            ->name('index');

        Route::post('/{id}/buka', [NotifikasiController::class, 'buka'])
            ->whereNumber('id')
            ->name('buka');
    });
