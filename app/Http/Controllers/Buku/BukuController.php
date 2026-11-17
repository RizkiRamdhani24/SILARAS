<?php

namespace App\Http\Controllers\Buku;

use App\Exceptions\LogAktivitasException;
use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
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
        $query = Buku::query();
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('kategori', ''));
        $availability = trim((string) $request->query('ketersediaan', ''));

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
