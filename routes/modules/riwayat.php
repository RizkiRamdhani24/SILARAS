<?php

use App\Models\Peminjaman;
use Illuminate\Support\Facades\Route;

Route::get('/anggota/riwayat', function () {
    $loans = Peminjaman::with(['buku', 'pengembalian', 'pengembalianAktif', 'pengembalians'])
        ->where('id_anggota', auth('anggota')->id())
        ->latest('id_peminjaman')
        ->get();

    return view('anggota.riwayat.index', [
        'member' => auth('anggota')->user(),
        'loans' => $loans,
    ]);
})->middleware('role.anggota')->name('anggota.riwayat');
