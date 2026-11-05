<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use Illuminate\Http\Request;

class BukuApiController extends Controller
{
    /**
     * Menampilkan daftar buku dalam format JSON.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $query = Buku::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul_buku', 'ilike', "%{$search}%")
                    ->orWhere('pengarang', 'ilike', "%{$search}%")
                    ->orWhere('penerbit', 'ilike', "%{$search}%")
                    ->orWhere('kategori', 'ilike', "%{$search}%")
                    ->orWhere('isbn', 'ilike', "%{$search}%");
            });
        }

        $books = $query->orderBy('judul_buku')
            ->paginate(10)
            ->withQueryString();

        $books->getCollection()->transform(function ($buku) {
            return [
                'id_buku' => $buku->id_buku,
                'judul_buku' => $buku->judul_buku,
                'pengarang' => $buku->pengarang,
                'penerbit' => $buku->penerbit,
                'kategori' => $buku->kategori,
                'stock' => $buku->stock,
                'isbn' => $buku->isbn,
                'rak' => $buku->rak,
                'cover' => $buku->cover
                    ? asset('storage/' . $buku->cover)
                    : null,
                'pdf' => $buku->pdf
                    ? asset('storage/' . $buku->pdf)
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar buku berhasil diambil.',
            'data' => $books,
        ]);
    }

    /**
     * Menampilkan detail satu buku.
     */
    public function show(Buku $buku)
    {
        return response()->json([
            'success' => true,
            'message' => 'Detail buku berhasil diambil.',
            'data' => [
                'id_buku' => $buku->id_buku,
                'judul_buku' => $buku->judul_buku,
                'pengarang' => $buku->pengarang,
                'penerbit' => $buku->penerbit,
                'kategori' => $buku->kategori,
                'stock' => $buku->stock,
                'isbn' => $buku->isbn,
                'rak' => $buku->rak,
                'cover' => $buku->cover
                    ? asset('storage/' . $buku->cover)
                    : null,
                'pdf' => $buku->pdf
                    ? asset('storage/' . $buku->pdf)
                    : null,
            ],
        ]);
    }
}
