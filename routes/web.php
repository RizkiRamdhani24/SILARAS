<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\Petugas\TransaksiController;
use App\Http\Controllers\ProfileController;
use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\TagihanDenda;
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

Route::get('/anggota/daftar-sukses', fn () => redirect()->route('anggota.dashboard'))
    ->name('anggota.daftar-sukses');

Route::get('/anggota/login-sukses', function () {
    return response('<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Login sukses</title><script>setTimeout(function(){window.location.href = "'.route('anggota.dashboard').'";}, 200);</script></head><body><p>Login sukses</p></body></html>');
})->name('anggota.login-sukses');

Route::get('/petugas/login-sukses', function () {
    return response('<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Login sukses</title><script>setTimeout(function(){window.location.href = "'.route('petugas.dashboard').'";}, 200);</script></head><body><p>Login sukses</p></body></html>');
})->name('petugas.login-sukses');

Route::get('/petugas/daftar-sukses', fn () => redirect()->route('petugas.dashboard'))
    ->name('petugas.daftar-sukses');

/*
|--------------------------------------------------------------------------
| Dashboard sementara (diproteksi per peran di Step 5)
|--------------------------------------------------------------------------
*/
Route::prefix('anggota')->name('anggota.')->middleware('role.anggota')->group(function () {
    Route::get('/profil', [ProfileController::class, 'showAnggota'])->name('profile');
    Route::put('/profil', [ProfileController::class, 'updateAnggota'])->name('profile.update');

    Route::get('/dashboard', function () {
        /** @var Anggota|null $member */
        $member = auth('anggota')->user();
        $loans = collect();

        if (Schema::hasTable('peminjaman') && Schema::hasTable('buku')) {
            $loans = $member->peminjaman()
                ->with(['buku', 'pengembalian', 'pengembalianAktif', 'pengembalians'])
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

        $dendaRingkasan = null;
        if (Schema::hasTable('tagihan_denda') && Schema::hasTable('pembayaran_denda')) {
            $tagihanBelumLunas = TagihanDenda::query()
                ->with([
                    'peminjaman',
                    'pembayaran' => fn ($query) => $query->latest('created_at'),
                ])
                ->where('id_anggota', $member->getAuthIdentifier())
                ->where('status_tagihan', TagihanDenda::STATUS_BELUM_LUNAS)
                ->get();
            $pembayaranTerbaru = $tagihanBelumLunas
                ->map(fn ($tagihan) => $tagihan->pembayaran->first())
                ->filter();
            $jumlahMenungguVerifikasi = $pembayaranTerbaru
                ->where('status_pembayaran', 'menunggu_verifikasi')
                ->count();
            $adaPembayaranDitolak = $pembayaranTerbaru
                ->contains('status_pembayaran', 'ditolak');
            $dendaMasihBerjalan = $tagihanBelumLunas->contains(
                fn ($tagihan) => $tagihan->peminjaman?->status_peminjaman === Peminjaman::STATUS_TERLAMBAT
                    && $tagihan->dibekukan_at === null
            );
            $nominalTunggakan = (float) $tagihanBelumLunas->sum('nominal_tagihan');

            $warna = $adaPembayaranDitolak || $dendaMasihBerjalan
                ? 'merah'
                : ($nominalTunggakan > 0 || $jumlahMenungguVerifikasi > 0 ? 'kuning' : 'biru');
            $pesan = match ($warna) {
                'merah' => $adaPembayaranDitolak
                    ? 'Pembayaran denda ditolak. Periksa alasan penolakan dan ajukan kembali.'
                    : 'Denda keterlambatan masih bertambah sampai pengembalian dikonfirmasi.',
                'kuning' => $jumlahMenungguVerifikasi > 0
                    ? 'Pembayaran sedang menunggu verifikasi Petugas.'
                    : 'Kamu memiliki tagihan denda yang belum lunas.',
                default => 'Tidak ada tunggakan denda yang perlu diselesaikan.',
            };

            $dendaRingkasan = [
                'warna' => $warna,
                'pesan' => $pesan,
                'nominal' => $nominalTunggakan,
                'jumlah_tagihan' => $tagihanBelumLunas->count(),
                'jumlah_menunggu_verifikasi' => $jumlahMenungguVerifikasi,
            ];
        }

        return view('anggota.dashboard', [
            'member' => $member,
            'loans' => $loans,
            'activeLoans' => $activeLoans,
            'dendaRingkasan' => $dendaRingkasan,
        ]);
    })->name('dashboard');

    Route::get('/katalog', function (Request $request) {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $availability = trim((string) $request->query('ketersediaan', ''));
        $query = Buku::query();
        if (Schema::hasColumn('buku', 'diarsipkan')) {
            $query->where('diarsipkan', false);
        }

        if ($search !== '') {
            $query->where(function (Builder $books) use ($search) {
                $books->where('judul_buku', 'like', '%'.$search.'%')
                    ->orWhere('pengarang', 'like', '%'.$search.'%')
                    ->orWhere('penerbit', 'like', '%'.$search.'%')
                    ->orWhere('kategori', 'like', '%'.$search.'%')
                    ->orWhere('isbn', 'like', '%'.$search.'%');
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
        $bookQuery = Buku::query();
        if (Schema::hasColumn('buku', 'diarsipkan')) {
            $bookQuery->where('diarsipkan', false);
        }
        $selectedBook = $bookQuery->findOrFail($book);
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
    Route::get('/profil', [ProfileController::class, 'showPetugas'])->name('profile');
    Route::put('/profil', [ProfileController::class, 'updatePetugas'])->name('profile.update');

    Route::get('/dashboard', function () {
        return view('petugas.dashboard.index');
    })->name('dashboard');

    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi');

    Route::get('/', function () {
        return redirect()->route('petugas.dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| Preview routes untuk development frontend anggota
|--------------------------------------------------------------------------
| URL: /dev/anggota/{peminjaman,riwayat,pengembalian}
| Tujuan: Preview UI tanpa data backend
| Catatan: View menggunakan fallback auth('anggota')->user() dan isset() checks
*/
if (app()->environment('local')) {
    Route::prefix('dev/anggota')->name('dev.anggota.')->group(function () {
        Route::get('/peminjaman', function () {
            return view('anggota.peminjaman.index', [
                'member' => auth('anggota')->user(),
                'peminjaman' => collect(),
                'penolakanTerbaru' => collect(),
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
                'peminjaman' => collect(),
                'penolakanTerbaru' => collect(),
            ]);
        })->name('pengembalian');
    });
}

require __DIR__.'/modules/landing.php';
require __DIR__.'/modules/buku.php';
require __DIR__.'/modules/peminjaman.php';
require __DIR__.'/modules/pengembalian.php';
require __DIR__.'/modules/denda.php';
require __DIR__.'/modules/notifikasi.php';
require __DIR__.'/modules/riwayat.php';
require __DIR__.'/modules/kelola-anggota.php';
require __DIR__.'/modules/petugas-preview.php';
