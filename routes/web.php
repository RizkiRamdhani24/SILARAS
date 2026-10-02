<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('library');
});
/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login');

    Route::get('/register', 'showRegister')->name('register');
    Route::post('/register', 'register');

    Route::post('/logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Dashboard sementara (diproteksi per peran di Step 5)
|--------------------------------------------------------------------------
*/
Route::prefix('anggota')->name('anggota.')->middleware('auth:anggota')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Anggota: ' . auth('anggota')->user()->nama_anggota)
        ->name('dashboard');
});

Route::prefix('petugas')->name('petugas.')->middleware('auth:petugas')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Petugas: ' . auth('petugas')->user()->nama_petugas)
        ->name('dashboard');
});
