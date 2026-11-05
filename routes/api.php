<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Buku\BukuController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Peminjaman\PeminjamanController;

Route::get('/buku', [BukuController::class, 'indexApi']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Pengajuan peminjaman buku
    Route::post('/peminjaman', [PeminjamanController::class, 'storeApi']);
});