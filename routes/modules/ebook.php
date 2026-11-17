<?php

use App\Http\Controllers\Ebook\EbookController;
use App\Http\Controllers\Petugas\LogAktivitasController;
use Illuminate\Support\Facades\Route;

Route::middleware('role.anggota')
    ->prefix('anggota')
    ->name('anggota.')
    ->group(function () {
        Route::get('/ebook', [EbookController::class, 'index'])->name('ebook.index');
        Route::get('/ebook/{ebook}/unduh', [EbookController::class, 'unduh'])
            ->whereNumber('ebook')
            ->name('ebook.unduh');
    });

Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/ebook', [EbookController::class, 'indexPetugas'])->name('ebook.index');
        Route::get('/ebook/tambah', [EbookController::class, 'create'])->name('ebook.create');
        Route::post('/ebook', [EbookController::class, 'store'])->name('ebook.store');
        Route::get('/ebook/unduhan', [EbookController::class, 'unduhan'])->name('ebook.unduhan');
        Route::get('/ebook/{ebook}/edit', [EbookController::class, 'edit'])
            ->whereNumber('ebook')
            ->name('ebook.edit');
        Route::put('/ebook/{ebook}', [EbookController::class, 'update'])
            ->whereNumber('ebook')
            ->name('ebook.update');
        Route::delete('/ebook/{ebook}', [EbookController::class, 'destroy'])
            ->whereNumber('ebook')
            ->name('ebook.destroy');
        Route::get('/log-aktivitas', [LogAktivitasController::class, 'index'])
            ->name('log-aktivitas.index');
    });
