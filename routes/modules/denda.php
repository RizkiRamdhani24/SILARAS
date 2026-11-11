<?php

use App\Http\Controllers\Denda\DendaController;
use Illuminate\Support\Facades\Route;

Route::middleware('role.anggota')
    ->prefix('anggota')
    ->name('anggota.')
    ->group(function () {
        Route::get('/denda', [DendaController::class, 'indexAnggota'])
            ->name('denda.index');

        Route::get('/denda/{tagihan}', [DendaController::class, 'showAnggota'])
            ->whereNumber('tagihan')
            ->name('denda.show');

        Route::get('/denda/{tagihan}/qris', [DendaController::class, 'lihatQris'])
            ->whereNumber('tagihan')
            ->name('denda.qris');

        Route::post('/denda/{tagihan}/pembayaran', [DendaController::class, 'storePembayaran'])
            ->whereNumber('tagihan')
            ->name('denda.pembayaran.store');
    });

Route::middleware('role.petugas')
    ->prefix('petugas')
    ->name('petugas.')
    ->group(function () {
        Route::get('/denda', [DendaController::class, 'indexPetugas'])
            ->name('denda');

        Route::get('/denda/{tagihan}', [DendaController::class, 'showPetugas'])
            ->whereNumber('tagihan')
            ->name('denda.show');

        Route::patch('/denda/pembayaran/{pembayaran}/verifikasi', [DendaController::class, 'verifikasiPembayaran'])
            ->name('denda.pembayaran.verifikasi');

        Route::get('/denda/pembayaran/{pembayaran}/bukti', [DendaController::class, 'lihatBukti'])
            ->whereNumber('pembayaran')
            ->name('denda.pembayaran.bukti');

        Route::post('/denda/{tagihan}/tunai', [DendaController::class, 'catatTunai'])
            ->whereNumber('tagihan')
            ->name('denda.pembayaran.tunai');

        Route::post('/denda/tarif', [DendaController::class, 'updateTarif'])
            ->name('denda.tarif.update');
    });
