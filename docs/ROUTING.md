# Panduan Routing Modul

Setiap modul hanya menulis route di file miliknya di `routes/modules/`. Jangan menulis route modul di `routes/web.php` atau file modul milik programmer lain. Route landing bersifat publik.

Lindungi route anggota dan petugas menggunakan alias peran pada grup route:

```php
Route::middleware('role.anggota')->prefix('anggota')->name('anggota.')->group(function () {
    Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
});

Route::middleware('role.petugas')->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/buku', [BukuController::class, 'index'])->name('buku.index');
});
```

Gunakan nama route dengan prefix modul, misalnya `buku.index` dan `peminjaman.store`; grup peran dapat menambahkan prefix nama seperti `anggota.` atau `petugas.`. Letakkan setiap route pada file modul dan gunakan middleware yang sesuai di file tersebut.
