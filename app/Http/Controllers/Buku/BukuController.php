<?php

namespace App\Http\Controllers\Buku;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use Illuminate\Http\Request;

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
                $q->where('judul_buku', 'ILIKE', "%{$keyword}%")
                    ->orWhere('pengarang', 'ILIKE', "%{$keyword}%")
                    ->orWhere('penerbit', 'ILIKE', "%{$keyword}%");
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
        ]);

        $buku = Buku::create($validated);

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

    if ($request->filled('q')) {
        $keyword = $request->q;

        $query->where(function ($q) use ($keyword) {
            $q->where('judul_buku', 'ILIKE', "%{$keyword}%")
              ->orWhere('pengarang', 'ILIKE', "%{$keyword}%")
              ->orWhere('penerbit', 'ILIKE', "%{$keyword}%");
        });
    }

    $buku = $query->orderBy('judul_buku')
        ->paginate(10)
        ->withQueryString();

    return view('petugas.buku.index', compact('buku'));
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
    ]);

    $buku->update($validated);

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
 * Menghapus data buku.
 */
public function destroy(Request $request, $id)
{
    $buku = Buku::findOrFail($id);

    // Buku yang sudah memiliki riwayat peminjaman tidak boleh dihapus.
    if ($buku->peminjaman()->exists()) {
        $message = 'Buku tidak dapat dihapus karena sudah memiliki riwayat peminjaman.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()
            ->route('petugas.kelola-buku.index')
            ->with('error', $message);
    }

    $buku->delete();

    if ($request->expectsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Buku berhasil dihapus.',
        ]);
    }

    return redirect()
        ->route('petugas.kelola-buku.index')
        ->with('success', 'Buku berhasil dihapus.');
}
// KATALOG BUKU UNTUK ANGGOTA
public function katalog(Request $request)
{
    $query = Buku::query();

    if ($request->filled('q')) {
        $keyword = $request->q;

        $query->where(function ($q) use ($keyword) {
            $q->where('judul_buku', 'ILIKE', "%{$keyword}%")
                ->orWhere('pengarang', 'ILIKE', "%{$keyword}%")
                ->orWhere('penerbit', 'ILIKE', "%{$keyword}%");
        });
    }

    $buku = $query->orderBy('judul_buku')
        ->paginate(12)
        ->withQueryString();

    return view('anggota.buku.index', compact('buku'));
}
    public function indexApi()
{
    $buku = \App\Models\Buku::all();

    return response()->json([
        'success' => true,
        'message' => 'Data buku berhasil diambil',
        'data' => $buku
    ], 200);
}

}
