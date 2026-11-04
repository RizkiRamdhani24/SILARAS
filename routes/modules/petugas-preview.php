<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute PRATINJAU halaman petugas (frontend) — Danar
|--------------------------------------------------------------------------
| Menampilkan halaman petugas dengan data contoh, tanpa login, supaya tampilan
| bisa dikerjakan sebelum backend dari Exel dan Herlyan siap.
|
| - Hanya aktif saat APP_ENV=local, jadi tidak pernah ikut aktif di produksi.
| - Saat controller backend siap, ganti tiap Route::view() di bawah dengan
|   route ke controller (di dalam group middleware 'auth:petugas'), lalu hapus
|   file ini.
*/
if (app()->environment('local')) {
    Route::prefix('petugas')->name('petugas.preview.')->group(function () {
        Route::view('/dashboard', 'petugas.dashboard.index')->name('dashboard');
        Route::view('/buku', 'petugas.buku.index')->name('buku');
        Route::view('/anggota', 'petugas.anggota.index')->name('anggota');
        Route::view('/peminjaman', 'petugas.peminjaman.index')->name('peminjaman');
        Route::view('/pengembalian', 'petugas.pengembalian.index')->name('pengembalian');
        Route::view('/denda', 'petugas.denda.index')->name('denda');
        Route::view('/transaksi', 'petugas.transaksi.index')->name('transaksi');
    });
}
