<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;

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
    Route::prefix('dev/petugas')->name('petugas.preview.')->group(function () {
        Route::view('/dashboard', 'petugas.dashboard.index')->name('dashboard');
        Route::get('/buku', function () {
            return view('petugas.buku.index', [
                'buku' => new LengthAwarePaginator([], 0, 10),
                'totalBuku' => 0,
                'categories' => collect(),
                'search' => '',
                'selectedCategory' => '',
                'selectedAvailability' => '',
            ]);
        })->name('buku');
        Route::get('/anggota', function () {
            return view('petugas.anggota.index', ['anggota' => collect()]);
        })->name('anggota');
        Route::get('/peminjaman', function () {
            return view('petugas.peminjaman.index', [
                'peminjaman' => new LengthAwarePaginator([], 0, 10),
                'jumlahMenunggu' => 0,
                'search' => '',
                'selectedStatus' => '',
                'tarifDendaPerHari' => 5000,
            ]);
        })->name('peminjaman');
        Route::get('/pengembalian', function () {
            return view('petugas.pengembalian.index', [
                'pengembalian' => new LengthAwarePaginator([], 0, 10),
                'jumlahMenunggu' => 0,
                'search' => '',
                'selectedStatus' => '',
            ]);
        })->name('pengembalian');
        Route::get('/denda', function () {
            $emptyResults = new LengthAwarePaginator([], 0, 10);

            return view('petugas.denda.index', [
                'tagihan' => $emptyResults,
                'pembayaranMenungguVerifikasi' => $emptyResults,
                'search' => '',
                'selectedStatus' => '',
            ]);
        })->name('denda');
    });
}
