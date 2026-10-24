<?php

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/', function (Request $request) {
    $hasBooksTable = Schema::hasTable('buku');
    $search = trim((string) $request->query('q', ''));
    $category = trim((string) $request->query('kategori', ''));
    $books = collect();
    $categories = collect();
    $bookCount = 0;
    $copyCount = 0;
    $memberCount = 0;
    $circulationSuccess = 0;

    if ($hasBooksTable) {
        $bookQuery = Buku::query();

        if ($search !== '') {
            $bookQuery->where(function (Builder $query) use ($search) {
                $query->where('judul_buku', 'like', '%' . $search . '%')
                    ->orWhere('pengarang', 'like', '%' . $search . '%')
                    ->orWhere('penerbit', 'like', '%' . $search . '%')
                    ->orWhere('isbn', 'like', '%' . $search . '%');
            });
        }

        if ($category !== '') {
            $bookQuery->where('kategori', $category);
        }

        $books = $bookQuery->orderByDesc('stock')
            ->orderBy('judul_buku')
            ->limit(4)
            ->get();

        $categories = Buku::query()
            ->select('kategori')
            ->selectRaw('COUNT(*) as jumlah')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->get();

        $bookCount = Buku::query()->count();
        $copyCount = (int) Buku::query()->sum('stock');
    }

    if (Schema::hasTable('anggota')) {
        $memberCount = Anggota::query()->count();
    }

    if (Schema::hasTable('peminjaman')) {
        $loanCount = Peminjaman::query()->count();
        $returnedCount = Peminjaman::query()
            ->where('status_peminjaman', Peminjaman::STATUS_DIKEMBALIKAN)
            ->count();

        $circulationSuccess = $loanCount > 0
            ? round(($returnedCount / $loanCount) * 100, 1)
            : 0;
    }

    return view('welcome', [
        'books' => $books,
        'categories' => $categories,
        'search' => $search,
        'selectedCategory' => $category,
        'bookCount' => $bookCount,
        'copyCount' => $copyCount,
        'memberCount' => $memberCount,
        'circulationSuccess' => $circulationSuccess,
    ]);
})->name('home');
