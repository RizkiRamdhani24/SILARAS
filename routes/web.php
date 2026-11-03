<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Landing Page
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome')->name('home');

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

    Route::get('/katalog', fn() => 'Katalog Buku')->name('katalog');
    Route::get('/buku/{id}', fn($id) => 'Detail Buku ' . $id)->name('buku.show');
});

Route::prefix('petugas')->name('petugas.')->middleware('auth:petugas')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Petugas: ' . auth('petugas')->user()->nama_petugas)
        ->name('dashboard');
});
