<?php

namespace App\Http\Controllers\Buku;

use App\Exceptions\LogAktivitasException;
use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Services\CsvImportService;
use App\Services\LogAktivitasService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BukuController extends Controller
{
    /**
     * Menampilkan daftar buku.
     */
    public function index(Request $request)
    {
        $query = Buku::query();

        // Fitur pencarian buku
        if ($request->filled('q')) {
            $keyword = $request->q;

            $query->where(function ($q) use ($keyword) {
                $q->where('judul_buku', 'like', "%{$keyword}%")
                    ->orWhere('pengarang', 'like', "%{$keyword}%")
                    ->orWhere('penerbit', 'like', "%{$keyword}%");
            });
        }

        // Mengambil data buku dengan pagination
        $buku = $query
            ->orderBy('judul_buku')
            ->paginate(10)
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil diambil.',
            'data' => $buku,
        ]);
    }

    /**
     * Menambahkan buku baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul_buku' => ['required', 'string', 'max:150'],
            'pengarang' => ['required', 'string', 'max:100'],
            'penerbit' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'isbn' => ['nullable', 'string'],
            'rak' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $storedFiles = [];
        try {
            if ($request->hasFile('cover')) {
                $validated['cover'] = $request->file('cover')->store('cover', 's3');
                if (! $validated['cover']) {
                    throw new RuntimeException('Gagal menyimpan gambar cover buku.');
                }
                $storedFiles[] = ['s3', $validated['cover']];
            }

            $buku = DB::transaction(function () use ($validated) {
                $buku = Buku::create($validated);
                app(LogAktivitasService::class)->catat('tambah', 'buku', $buku->judul_buku);

                return $buku;
            });
        } catch (LogAktivitasException $exception) {
            $this->cleanupStoredFiles($storedFiles);

            return $this->logFailure($request);
        } catch (Throwable $exception) {
            $this->cleanupStoredFiles($storedFiles);

            throw $exception;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil ditambahkan.',
                'data' => $buku,
            ], 201);
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    public function halaman(Request $request)
    {
        $query = $this->bookListQuery($request);
        $search = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('kategori', ''));
        $availability = trim((string) $request->input('ketersediaan', ''));

        $buku = $query->orderBy('judul_buku')
            ->paginate(10)
            ->withQueryString();

        $categories = Buku::query()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('petugas.buku.index', [
            'buku' => $buku,
            'totalBuku' => Buku::query()->count(),
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $category,
            'selectedAvailability' => $availability,
        ]);
    }

    public function downloadTemplate(CsvImportService $csv): StreamedResponse
    {
        return $csv->template('template-buku.csv', [
            'judul_buku',
            'pengarang',
            'penerbit',
            'stock',
            'kategori',
            'isbn',
            'rak',
        ]);
    }

    public function importCsv(
        Request $request,
        CsvImportService $csv,
        LogAktivitasService $activityLog
    ) {
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ]);

        $rows = $csv->read($request->file('file'), [
            'judul_buku',
            'pengarang',
            'penerbit',
            'stock',
            'kategori',
            'isbn',
            'rak',
        ]);
        $validatedRows = [];
        $errors = [];

        foreach ($rows as $row) {
            $values = $row['values'];
            foreach (['penerbit', 'kategori', 'isbn', 'rak'] as $optionalField) {
                $values[$optionalField] = $values[$optionalField] === '' ? null : $values[$optionalField];
            }

            $validator = Validator::make($values, [
                'judul_buku' => ['required', 'string', 'max:150'],
                'pengarang' => ['required', 'string', 'max:100'],
                'penerbit' => ['nullable', 'string', 'max:100'],
                'stock' => ['required', 'integer', 'min:0'],
                'kategori' => ['nullable', 'string', 'max:50'],
                'isbn' => ['nullable', 'string'],
                'rak' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Baris '.$row['line'].': '.$validator->errors()->first();

                continue;
            }

            $validatedRows[] = $validator->validated();
        }

        if ($errors === []) {
            $existingBooks = DB::table('buku')->get(['judul_buku', 'pengarang', 'isbn']);
            $existingIsbns = [];
            $existingTitles = [];
            foreach ($existingBooks as $book) {
                if (trim((string) $book->isbn) !== '') {
                    $existingIsbns[$this->normalizeIsbn($book->isbn)] = true;
                }
                $existingTitles[$this->normalizeTitleAuthor($book->judul_buku, $book->pengarang)] = true;
            }

            foreach ($validatedRows as $index => $book) {
                $line = $rows[$index]['line'];
                $isbn = trim((string) ($book['isbn'] ?? ''));
                $titleKey = $this->normalizeTitleAuthor($book['judul_buku'], $book['pengarang']);
                $isbnKey = $isbn !== '' ? $this->normalizeIsbn($isbn) : null;
                if (
                    isset($existingTitles[$titleKey])
                    || ($isbnKey !== null && isset($existingIsbns[$isbnKey]))
                ) {
                    $errors[] = "Baris {$line}: buku duplikat berdasarkan judul/penulis atau ISBN.";

                    continue;
                }

                $existingTitles[$titleKey] = true;
                if ($isbnKey !== null) {
                    $existingIsbns[$isbnKey] = true;
                }
            }
        }

        if ($errors !== []) {
            return back()->withErrors(['file' => $errors])->withInput();
        }

        try {
            DB::transaction(function () use ($validatedRows, $activityLog): void {
                foreach ($validatedRows as $bookData) {
                    $book = Buku::query()->create($bookData);
                    $activityLog->catat('tambah', 'buku', $book->judul_buku);
                }
            });
        } catch (LogAktivitasException $exception) {
            return back()->with('error', 'Impor dibatalkan karena log aktivitas gagal disimpan.');
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', count($validatedRows).' buku berhasil diimpor.');
    }

    public function permanentDestroy(Request $request, int $id, LogAktivitasService $activityLog)
    {
        $book = Buku::query()->findOrFail($id);

        try {
            $deleted = DB::transaction(function () use ($book, $activityLog): bool {
                $lockedBook = Buku::query()->whereKey($book->getKey())->lockForUpdate()->firstOrFail();
                if (DB::table('peminjaman')->where('id_buku', $lockedBook->getKey())->exists()) {
                    return false;
                }

                $lockedBook->delete();
                $activityLog->catat('hapus', 'buku', $lockedBook->judul_buku);

                return true;
            });
        } catch (LogAktivitasException $exception) {
            return $this->logFailure($request);
        }

        if (! $deleted) {
            return back()->with('error', 'Buku tidak dapat dihapus permanen karena memiliki riwayat peminjaman.');
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', 'Buku berhasil dihapus permanen.');
    }

    public function bulkDestroy(Request $request, LogAktivitasService $activityLog)
    {
        $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:buku,id_buku'],
            'all_filtered' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string'],
            'kategori' => ['nullable', 'string'],
            'ketersediaan' => ['nullable', 'in:tersedia,habis'],
        ]);

        $ids = $request->boolean('all_filtered')
            ? $this->bookListQuery($request)->pluck('id_buku')->all()
            : array_map('intval', $request->input('ids', []));
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return back()->with('error', 'Pilih setidaknya satu buku untuk dihapus.');
        }

        try {
            $deletedCount = DB::transaction(function () use ($ids, $activityLog): int|false {
                $books = Buku::query()->whereIn('id_buku', $ids)->orderBy('id_buku')->lockForUpdate()->get();
                if ($books->isEmpty()) {
                    return false;
                }

                if (DB::table('peminjaman')->whereIn('id_buku', $books->pluck('id_buku'))->exists()) {
                    return false;
                }

                foreach ($books as $book) {
                    $book->delete();
                    $activityLog->catat('hapus', 'buku', $book->judul_buku);
                }

                return $books->count();
            });
        } catch (LogAktivitasException $exception) {
            return $this->logFailure($request);
        }

        if ($deletedCount === false) {
            return back()->with('error', 'Penghapusan dibatalkan: buku terpilih memiliki riwayat peminjaman atau tidak ditemukan.');
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', $deletedCount.' buku berhasil dihapus permanen.');
    }

    private function bookListQuery(Request $request): Builder
    {
        $query = Buku::query();
        $search = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('kategori', ''));
        $availability = trim((string) $request->input('ketersediaan', ''));

        if ($search !== '') {
            $query->where(function ($books) use ($search) {
                $books->where('judul_buku', 'like', "%{$search}%")
                    ->orWhere('pengarang', 'like', "%{$search}%")
                    ->orWhere('penerbit', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%");
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

        return $query;
    }

    private function normalizeTitleAuthor(string $title, string $author): string
    {
        return mb_strtolower(trim($title))."\0".mb_strtolower(trim($author));
    }

    private function normalizeIsbn(string $isbn): string
    {
        return mb_strtolower((string) preg_replace('/[\s-]+/', '', trim($isbn)));
    }

    public function create()
    {
        return view('petugas.buku.create');
    }

    /**
     * Menampilkan form edit buku.
     */
    public function edit($id)
    {
        $buku = Buku::findOrFail($id);

        return view('petugas.buku.edit', compact('buku'));
    }

    /**
     * Memperbarui data buku.
     */
    public function update(Request $request, $id)
    {
        $buku = Buku::findOrFail($id);

        $validated = $request->validate([
            'judul_buku' => ['required', 'string', 'max:150'],
            'pengarang' => ['required', 'string', 'max:100'],
            'penerbit' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'isbn' => ['nullable', 'string'],
            'rak' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $storedFiles = [];
        $previousCover = $buku->cover;
        try {
            if ($request->hasFile('cover')) {
                $validated['cover'] = $request->file('cover')->store('cover', 's3');
                if (! $validated['cover']) {
                    throw new RuntimeException('Gagal menyimpan gambar cover buku.');
                }
                $storedFiles[] = ['s3', $validated['cover']];
            }

            $buku = DB::transaction(function () use ($buku, $validated) {
                $buku->update($validated);
                app(LogAktivitasService::class)->catat('ubah', 'buku', $buku->judul_buku);

                return $buku;
            });
        } catch (LogAktivitasException $exception) {
            $this->cleanupStoredFiles($storedFiles);

            return $this->logFailure($request);
        } catch (Throwable $exception) {
            $this->cleanupStoredFiles($storedFiles);

            throw $exception;
        }

        if ($request->hasFile('cover') && $previousCover) {
            Storage::disk('s3')->delete($previousCover);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil diperbarui.',
                'data' => $buku,
            ]);
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', 'Buku berhasil diperbarui.');
    }

    /**
     * Mengarsipkan buku tanpa menghapus riwayat transaksinya.
     */
    public function destroy(Request $request, $id)
    {
        $buku = Buku::findOrFail($id);

        try {
            DB::transaction(function () use ($buku) {
                $buku->update(['diarsipkan' => true]);
                app(LogAktivitasService::class)->catat('hapus', 'buku', $buku->judul_buku);
            });
        } catch (LogAktivitasException $exception) {
            return $this->logFailure($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Buku berhasil diarsipkan.',
            ]);
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('success', 'Buku berhasil diarsipkan.');
    }

    private function cleanupStoredFiles(array $storedFiles): void
    {
        foreach ($storedFiles as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function logFailure(Request $request)
    {
        $message = 'Aksi dibatalkan karena log aktivitas gagal disimpan. Coba lagi atau hubungi administrator.';

        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 503);
        }

        return back()->with('error', $message);
    }

    // KATALOG BUKU UNTUK ANGGOTA
    public function katalog(Request $request)
    {
        $query = Buku::query()->where('diarsipkan', false);

        if ($request->filled('q')) {
            $keyword = $request->q;

            $query->where(function ($q) use ($keyword) {
                $q->where('judul_buku', 'like', "%{$keyword}%")
                    ->orWhere('pengarang', 'like', "%{$keyword}%")
                    ->orWhere('penerbit', 'like', "%{$keyword}%");
            });
        }

        $buku = $query->orderBy('judul_buku')
            ->paginate(12)
            ->withQueryString();

        return view('anggota.buku.index', compact('buku'));
    }

    public function indexApi()
    {
        $buku = Buku::all();

        return response()->json([
            'success' => true,
            'message' => 'Data buku berhasil diambil',
            'data' => $buku,
        ], 200);
    }
}
