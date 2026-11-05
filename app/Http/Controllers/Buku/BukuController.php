<?php

namespace App\Http\Controllers\Buku;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BukuController extends Controller
{
    /**
     * Menampilkan daftar buku.
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

        $buku = $books->getCollection()->map(function ($item) {
            return [
                'id' => $item->id_buku,
                'kode' => $item->id_buku,
                'judul' => $item->judul_buku,
                'pengarang' => $item->pengarang,
                'penerbit' => $item->penerbit,
                'stok' => $item->stock,
                'kategori' => $item->kategori ?? '',
                'isbn' => $item->isbn ?? '',
                'rak' => $item->rak ?? '',
                'cover' => $item->cover,
                'edisi' => '',
            ];
        });

        return view('petugas.buku.index', [
            'buku' => $buku,
            'books' => $books,
            'log' => [],
            'bookCount' => Buku::count(),
            'totalStock' => Buku::sum('stock'),
            'criticalCount' => Buku::where('stock', '<=', 1)->count(),
            'search' => $search,
        ]);
    }

    /**
     * Menyimpan buku baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'judul_buku' => ['required', 'string', 'max:150'],
            'pengarang' => ['required', 'string', 'max:100'],
            'penerbit' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'rak' => ['nullable', 'string', 'max:100'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')
                ->store('buku/cover', 'public');
        }

        if ($request->hasFile('pdf')) {
            $data['pdf'] = $request->file('pdf')
                ->store('buku/pdf', 'public');
        }

        Buku::create($data);

        return redirect()
            ->route('petugas.buku.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    /**
     * Memperbarui data buku.
     */
    public function update(Request $request, Buku $buku)
    {
        $data = $request->validate([
            'judul_buku' => ['required', 'string', 'max:150'],
            'pengarang' => ['required', 'string', 'max:100'],
            'penerbit' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:50'],
            'stock' => ['required', 'integer', 'min:0'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'rak' => ['nullable', 'string', 'max:100'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $oldCover = $buku->cover;
        $oldPdf = $buku->pdf;

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')
                ->store('buku/cover', 'public');
        }

        if ($request->hasFile('pdf')) {
            $data['pdf'] = $request->file('pdf')
                ->store('buku/pdf', 'public');
        }

        $buku->update($data);

        if (isset($data['cover']) && $oldCover && $oldCover !== $data['cover']) {
            Storage::disk('public')->delete($oldCover);
        }

        if (isset($data['pdf']) && $oldPdf && $oldPdf !== $data['pdf']) {
            Storage::disk('public')->delete($oldPdf);
        }

        return redirect()
            ->route('petugas.buku.index')
            ->with('success', 'Data buku berhasil diperbarui.');
    }

    /**
     * Menghapus buku.
     */
    public function destroy(Buku $buku)
    {
        if ($buku->peminjaman()->exists()) {
            return redirect()
                ->route('petugas.buku.index')
                ->with('error', 'Buku tidak dapat dihapus karena sudah memiliki riwayat peminjaman.');
        }

        $cover = $buku->cover;
        $pdf = $buku->pdf;

        $buku->delete();

        if ($cover) {
            Storage::disk('public')->delete($cover);
        }

        if ($pdf) {
            Storage::disk('public')->delete($pdf);
        }

        return redirect()
            ->route('petugas.buku.index')
            ->with('success', 'Buku berhasil dihapus.');
    }
}
