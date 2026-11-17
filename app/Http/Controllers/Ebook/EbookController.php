<?php

namespace App\Http\Controllers\Ebook;

use App\Exceptions\LogAktivitasException;
use App\Http\Controllers\Controller;
use App\Models\Ebook;
use App\Models\UnduhanEbook;
use App\Services\EbookCoverStorageService;
use App\Services\LogAktivitasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
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
}
