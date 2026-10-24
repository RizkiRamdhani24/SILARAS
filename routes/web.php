<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->middleware('guest:anggota,petugas')->name('login');
    Route::post('/login', 'login');

    Route::get('/petugas/login', 'showPetugasLogin')->middleware('guest:anggota,petugas')->name('petugas.login');
    Route::post('/petugas/login', 'loginPetugas');

    Route::get('/register', 'showRegister')->middleware('guest:anggota,petugas')->name('register');
    Route::post('/register', 'register');

    Route::get('/petugas/register', 'showPetugasRegister')->middleware('guest:anggota,petugas')->name('petugas.register');
    Route::post('/petugas/register', 'registerPetugas');

    Route::post('/logout', 'logout')->name('logout');
});

Route::controller(GoogleAuthController::class)->middleware('guest:anggota,petugas')->group(function () {
    Route::get('/auth/google', 'redirect')->name('auth.google.redirect');
    Route::get('/auth/google/callback', 'callback')->name('auth.google.callback');
});

// TODO: Replace this placeholder with the member destination page.
Route::get('/anggota/daftar-sukses', fn() => 'Daftar sukses')
    ->name('anggota.daftar-sukses');

// TODO: Replace this placeholder with the member destination page.
Route::get('/anggota/login-sukses', fn() => 'Login sukses')
    ->name('anggota.login-sukses');

// TODO: Replace this placeholder with the staff destination page.
Route::get('/petugas/login-sukses', fn() => 'Login sukses')
    ->name('petugas.login-sukses');

// TODO: Replace this placeholder with the staff destination page.
Route::get('/petugas/daftar-sukses', fn() => 'Daftar sukses')
    ->name('petugas.daftar-sukses');

/*
|--------------------------------------------------------------------------
| Dashboard sementara (diproteksi per peran di Step 5)
|--------------------------------------------------------------------------
*/
Route::prefix('anggota')->name('anggota.')->middleware('role.anggota')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Anggota: ' . auth('anggota')->user()->nama_anggota)
        ->name('dashboard');
});

Route::prefix('petugas')->name('petugas.')->middleware('role.petugas')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Petugas: ' . auth('petugas')->user()->nama_petugas)
        ->name('dashboard');
});

require __DIR__ . '/modules/landing.php';
require __DIR__ . '/modules/buku.php';
require __DIR__ . '/modules/peminjaman.php';
require __DIR__ . '/modules/pengembalian.php';
require __DIR__ . '/modules/riwayat.php';
require __DIR__ . '/modules/kelola-anggota.php';
require __DIR__ . '/modules/dashboard-petugas.php';
