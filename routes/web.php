<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

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
    Route::get('/dashboard', function () {
        $member = auth('anggota')->user();
        $loans = collect();

        if (Schema::hasTable('peminjaman') && Schema::hasTable('buku')) {
            $loans = $member->peminjaman()
                ->with(['buku', 'pengembalian'])
                ->orderByDesc('tgl_peminjaman')
                ->get();
        } elseif ($member->relationLoaded('peminjaman')) {
            $loans = $member->peminjaman;
        }

        $activeLoans = $loans->whereIn('status_peminjaman', [
            Peminjaman::STATUS_MENUNGGU_VALIDASI,
            Peminjaman::STATUS_DIPINJAM,
            Peminjaman::STATUS_TERLAMBAT,
            Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
        ]);

        return view('anggota.dashboard', [
            'member' => $member,
            'loans' => $loans,
            'activeLoans' => $activeLoans,
        ]);
    })->name('dashboard');

    Route::get('/katalog', function (Request $request) {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $availability = trim((string) $request->query('ketersediaan', ''));
        $query = Buku::query();

        if ($search !== '') {
            $query->where(function (Builder $books) use ($search) {
                $books->where('judul_buku', 'like', '%' . $search . '%')
                    ->orWhere('pengarang', 'like', '%' . $search . '%')
                    ->orWhere('penerbit', 'like', '%' . $search . '%')
                    ->orWhere('kategori', 'like', '%' . $search . '%')
                    ->orWhere('isbn', 'like', '%' . $search . '%');
            });
        }

        if ($category !== '') {
            $query->where('kategori', $category);
        }

        if ($availability === 'tersedia') {
            $query->where('stock', '>', 0);
        } elseif ($availability === 'habis') {
            $query->where('stock', 0);
        }

        $books = $query->orderByDesc('stock')->orderBy('judul_buku')->paginate(8)->withQueryString();

        return view('anggota.katalog.index', [
            'member' => auth('anggota')->user(),
            'books' => $books,
            'bookCount' => Buku::query()->count(),
            'categories' => Buku::query()->whereNotNull('kategori')->where('kategori', '!=', '')->distinct()->orderBy('kategori')->pluck('kategori'),
            'search' => $search,
            'selectedCategory' => $category,
            'selectedAvailability' => $availability,
        ]);
    })->name('katalog');

    Route::get('/katalog/{book}', function (int $book) {
        $selectedBook = Buku::query()->findOrFail($book);
        $relatedBooks = Buku::query()
            ->when($selectedBook->kategori, fn (Builder $books) => $books->where('kategori', $selectedBook->kategori))
            ->whereKeyNot($selectedBook->getKey())
            ->orderByDesc('stock')
            ->orderBy('judul_buku')
            ->limit(4)
            ->get();

        return view('anggota.buku.show', [
            'member' => auth('anggota')->user(),
            'book' => $selectedBook,
            'relatedBooks' => $relatedBooks,
        ]);
    })->whereNumber('book')->name('buku.show');
});


Route::prefix('petugas')->name('petugas.')->middleware('role.petugas')->group(function () {
    Route::get('/dashboard', fn() => 'Dashboard Petugas: ' . auth('petugas')->user()->nama_petugas)
        ->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Preview routes untuk development frontend anggota
|--------------------------------------------------------------------------
| URL: /dev/anggota/{peminjaman,riwayat,pengembalian}
| Tujuan: Preview UI tanpa data backend
| Catatan: View menggunakan fallback auth('anggota')->user() dan isset() checks
*/
Route::prefix('dev/anggota')->name('dev.anggota.')->group(function () {
    Route::get('/peminjaman', function () {
        return view('anggota.peminjaman.index', [
            'member' => auth('anggota')->user(),
        ]);
    })->name('peminjaman');

    Route::get('/riwayat', function () {
        return view('anggota.riwayat.index', [
            'member' => auth('anggota')->user(),
        ]);
    })->name('riwayat');

    Route::get('/pengembalian', function () {
        return view('anggota.pengembalian.index', [
            'member' => auth('anggota')->user(),
        ]);
    })->name('pengembalian');
});

require __DIR__ . '/modules/landing.php';
require __DIR__ . '/modules/buku.php';
require __DIR__ . '/modules/peminjaman.php';
require __DIR__ . '/modules/pengembalian.php';
require __DIR__ . '/modules/riwayat.php';
require __DIR__ . '/modules/kelola-anggota.php';
require __DIR__ . '/modules/dashboard-petugas.php';
