<?php

namespace App\Http\Controllers\Ebook;

use App\Exceptions\LogAktivitasException;
use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\UnduhanEbook;
use App\Services\CsvImportService;
use App\Services\EbookCoverStorageService;
use App\Services\LogAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EbookController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $ebooks = Ebook::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('judul', 'like', '%'.$search.'%')
                        ->orWhere('penulis', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('judul')
            ->get();

        return view('anggota.ebook.index', compact('ebooks', 'search'));
    }

    public function unduh(int $ebook): RedirectResponse
    {
        $item = Ebook::query()->findOrFail($ebook);
        $anggotaId = auth('anggota')->user()->getAuthIdentifier();

        UnduhanEbook::query()->firstOrCreate(
            [
                'anggota_id' => $anggotaId,
                'ebook_id' => $item->getKey(),
            ],
            [
                'judul_ebook' => $item->judul,
                'penulis_ebook' => $item->penulis,
                'waktu_unduh' => now(),
            ]
        );

        return redirect()->away($item->link_drive);
    }

    public function indexPetugas(): View
    {
        $ebooks = Ebook::query()->orderBy('judul')->get();

        return view('petugas.ebook.index', compact('ebooks'));
    }

    public function downloadTemplate(CsvImportService $csv): StreamedResponse
    {
        return $csv->template('template-ebook.csv', [
            'judul',
            'penulis',
            'link_drive',
        ], false);
    }

    public function importCsv(
        Request $request,
        CsvImportService $csv,
        LogAktivitasService $activityLog
    ): RedirectResponse {
        $request->validate([
            'file' => ['required', 'file', 'extensions:csv,txt', 'max:2048'],
        ]);

        $rows = $csv->read($request->file('file'), [
            'judul',
            'penulis',
            'link_drive',
        ]);
        $validatedRows = [];
        $errors = [];

        foreach ($rows as $row) {
            $validator = Validator::make($row['values'], [
                'judul' => ['required', 'string', 'max:255'],
                'penulis' => ['required', 'string', 'max:255'],
                'link_drive' => ['required', 'string'],
            ]);

            if ($validator->fails()) {
                $errors[] = 'Baris '.$row['line'].': '.$validator->errors()->first();

                continue;
            }

            $validatedRows[] = $validator->validated();
        }

        if ($errors === []) {
            $existingTitles = [];
            foreach (Ebook::query()->get(['judul', 'penulis']) as $ebook) {
                $existingTitles[$this->normalizeTitleAuthor($ebook->judul, $ebook->penulis)] = true;
            }

            foreach ($validatedRows as $index => $ebook) {
                $titleKey = $this->normalizeTitleAuthor($ebook['judul'], $ebook['penulis']);
                if (isset($existingTitles[$titleKey])) {
                    $errors[] = 'Baris '.$rows[$index]['line'].': ebook dengan judul dan penulis tersebut sudah ada.';

                    continue;
                }

                $existingTitles[$titleKey] = true;
            }
        }

        if ($errors !== []) {
            return back()->withErrors(['file' => $errors])->withInput();
        }

        try {
            DB::transaction(function () use ($validatedRows, $activityLog): void {
                foreach ($validatedRows as $ebookData) {
                    $ebook = Ebook::query()->create([
                        'judul' => $ebookData['judul'],
                        'penulis' => $ebookData['penulis'],
                        'cover' => null,
                        'link_drive' => $ebookData['link_drive'],
                    ]);
                    $activityLog->catat('tambah', 'ebook', $ebook->judul);
                }
            });
        } catch (LogAktivitasException $exception) {
            return back()->with('error', $this->logFailureMessage());
        }

        return redirect()
            ->route('petugas.ebook.index')
            ->with('success', count($validatedRows).' ebook berhasil diimpor.');
    }

    public function create(): View
    {
        return view('petugas.ebook.create');
    }

    public function store(
        Request $request,
        EbookCoverStorageService $coverStorage,
        LogAktivitasService $activityLog
    ): RedirectResponse {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'penulis' => ['required', 'string', 'max:255'],
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'link_drive' => ['required', 'string'],
        ]);

        $coverUrl = $coverStorage->store($request->file('cover'));

        try {
            $ebook = DB::transaction(function () use ($validated, $coverUrl, $activityLog) {
                $ebook = Ebook::query()->create([
                    'judul' => $validated['judul'],
                    'penulis' => $validated['penulis'],
                    'cover' => $coverUrl,
                    'link_drive' => $validated['link_drive'],
                ]);
                $activityLog->catat('tambah', 'ebook', $ebook->judul);

                return $ebook;
            });
        } catch (LogAktivitasException $exception) {
            $coverStorage->delete($coverUrl);

            return back()->with('error', $this->logFailureMessage());
        } catch (Throwable $exception) {
            $coverStorage->delete($coverUrl);

            throw $exception;
        }

        return redirect()
            ->route('petugas.ebook.index')
            ->with('success', 'Ebook berhasil ditambahkan.');
    }

    public function edit(int $ebook): View
    {
        $ebook = Ebook::query()->findOrFail($ebook);

        return view('petugas.ebook.edit', compact('ebook'));
    }

    public function update(
        Request $request,
        int $ebook,
        EbookCoverStorageService $coverStorage,
        LogAktivitasService $activityLog
    ): RedirectResponse {
        $item = Ebook::query()->findOrFail($ebook);
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'penulis' => ['required', 'string', 'max:255'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'link_drive' => ['required', 'string'],
        ]);

        $previousCover = $item->cover;
        $coverUrl = $request->hasFile('cover')
            ? $coverStorage->store($request->file('cover'))
            : null;

        try {
            DB::transaction(function () use ($item, $validated, $coverUrl, $activityLog) {
                $item->update([
                    'judul' => $validated['judul'],
                    'penulis' => $validated['penulis'],
                    'cover' => $coverUrl ?? $item->cover,
                    'link_drive' => $validated['link_drive'],
                ]);
                $activityLog->catat('ubah', 'ebook', $item->judul);
            });
        } catch (LogAktivitasException $exception) {
            if ($coverUrl !== null) {
                $coverStorage->delete($coverUrl);
            }

            return back()->with('error', $this->logFailureMessage());
        } catch (Throwable $exception) {
            if ($coverUrl !== null) {
                $coverStorage->delete($coverUrl);
            }

            throw $exception;
        }

        if ($coverUrl !== null) {
            $coverStorage->delete($previousCover);
        }

        return redirect()
            ->route('petugas.ebook.index')
            ->with('success', 'Ebook berhasil diperbarui.');
    }

    public function destroy(
        int $ebook,
        EbookCoverStorageService $coverStorage,
        LogAktivitasService $activityLog
    ): RedirectResponse {
        $item = Ebook::query()->findOrFail($ebook);
        $coverUrl = $item->cover;

        try {
            DB::transaction(function () use ($item, $activityLog) {
                UnduhanEbook::query()
                    ->where('ebook_id', $item->getKey())
                    ->update([
                        'judul_ebook' => $item->judul,
                        'penulis_ebook' => $item->penulis,
                        'ebook_id' => null,
                    ]);
                $item->delete();
                $activityLog->catat('hapus', 'ebook', $item->judul);
            });
        } catch (LogAktivitasException $exception) {
            return back()->with('error', $this->logFailureMessage());
        }

        $coverStorage->delete($coverUrl);

        return redirect()
            ->route('petugas.ebook.index')
            ->with('success', 'Ebook berhasil dihapus.');
    }

    public function bulkDestroy(
        Request $request,
        EbookCoverStorageService $coverStorage,
        LogAktivitasService $activityLog
    ): RedirectResponse {
        $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'distinct', 'exists:ebook,id'],
            'all_filtered' => ['nullable', 'boolean'],
        ]);

        $ids = $request->boolean('all_filtered')
            ? Ebook::query()->pluck('id')->all()
            : array_map('intval', $request->input('ids', []));
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return back()->with('error', 'Pilih setidaknya satu ebook untuk dihapus.');
        }

        $items = Ebook::query()->whereIn('id', $ids)->orderBy('id')->get();
        if ($items->count() !== count($ids)) {
            return back()->with('error', 'Sebagian ebook terpilih tidak ditemukan; tidak ada yang dihapus.');
        }

        try {
            DB::transaction(function () use ($items, $activityLog): void {
                foreach ($items as $item) {
                    UnduhanEbook::query()
                        ->where('ebook_id', $item->getKey())
                        ->update([
                            'judul_ebook' => $item->judul,
                            'penulis_ebook' => $item->penulis,
                            'ebook_id' => null,
                        ]);
                    $item->delete();
                    $activityLog->catat('hapus', 'ebook', $item->judul);
                }
            });
        } catch (LogAktivitasException $exception) {
            return back()->with('error', $this->logFailureMessage());
        }

        foreach ($items as $item) {
            $coverStorage->delete($item->cover);
        }

        return redirect()
            ->route('petugas.ebook.index')
            ->with('success', $items->count().' ebook berhasil dihapus.');
    }

    public function unduhan(): View
    {
        $unduhan = UnduhanEbook::query()
            ->with('anggota')
            ->orderBy('anggota_id')
            ->orderByDesc('waktu_unduh')
            ->get()
            ->groupBy('anggota_id');

        return view('petugas.ebook.unduhan', compact('unduhan'));
    }

    private function logFailureMessage(): string
    {
        return 'Aksi dibatalkan karena log aktivitas gagal disimpan. Coba lagi atau hubungi administrator.';
    }

    private function normalizeTitleAuthor(string $title, string $author): string
    {
        return mb_strtolower(trim($title))."\0".mb_strtolower(trim($author));
    }
}
