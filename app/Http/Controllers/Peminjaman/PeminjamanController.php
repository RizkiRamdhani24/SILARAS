<?php

namespace App\Http\Controllers\Peminjaman;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Services\DendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    // DAFTAR PEMINJAMAN MILIK ANGGOTA
    public function indexAnggota(Request $request)
    {
        $anggota = auth('anggota')->user();

        $peminjaman = Peminjaman::with(['buku', 'pengembalian'])
            ->where('id_anggota', $anggota->getAuthIdentifier())
            ->latest('id_peminjaman')
            ->get();
        $penolakanTerbaru = app(DendaService::class)->notifikasiTersedia()
            ? $anggota->notifikasi()
                ->where('judul', 'Pengajuan peminjaman ditolak')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $peminjaman,
            ]);
        }

        return view('anggota.peminjaman.index', compact('peminjaman', 'penolakanTerbaru'));
    }

    // ANGGOTA MENGAJUKAN PEMINJAMAN
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_buku' => ['required', 'integer', 'exists:buku,id_buku'],
            'durasi_peminjaman_hari' => ['required', 'integer', 'in:7,14,30'],
        ]);

        $anggota = auth('anggota')->user();
        $hasil = $this->buatPengajuan(
            (int) $validated['id_buku'],
            (int) $anggota->getAuthIdentifier(),
            (int) $validated['durasi_peminjaman_hari']
        );

        if (! $hasil instanceof Peminjaman) {
            $message = $this->pesanGagalPengajuan($hasil);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], $hasil === 'akun_nonaktif' ? 403 : 422);
            }

            return back()->with('error', $message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil dikirim.',
                'data' => $hasil->load('buku'),
            ], 201);
        }

        return redirect()
            ->route('anggota.katalog')
            ->with(
                'success',
                'Pengajuan peminjaman berhasil dikirim. Silakan tunggu validasi petugas.'
            );
    }

    public function batalkan(Request $request, int $id)
    {
        $anggota = auth('anggota')->user();
        $dibatalkan = DB::transaction(function () use ($id, $anggota) {
            $peminjaman = Peminjaman::query()
                ->whereKey($id)
                ->where('id_anggota', $anggota->getAuthIdentifier())
                ->lockForUpdate()
                ->firstOrFail();

            if ($peminjaman->status_peminjaman !== Peminjaman::STATUS_MENUNGGU_VALIDASI) {
                return false;
            }

            $peminjaman->update([
                'status_peminjaman' => Peminjaman::STATUS_DIBATALKAN,
            ]);

            return true;
        });

        if (! $dibatalkan) {
            return back()->with('error', 'Hanya pengajuan yang menunggu validasi yang dapat dibatalkan.');
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }

    private function buatPengajuan(int $idBuku, int $idAnggota, int $durasiHari): Peminjaman|string
    {
        return DB::transaction(function () use ($idBuku, $idAnggota, $durasiHari) {
            $anggota = Anggota::query()->whereKey($idAnggota)->lockForUpdate()->firstOrFail();

            if ($anggota->status_anggota !== Anggota::STATUS_AKTIF) {
                return 'akun_nonaktif';
            }

            $buku = Buku::query()->whereKey($idBuku)->lockForUpdate()->firstOrFail();

            if ($buku->diarsipkan) {
                return 'buku_diarsipkan';
            }

            if ($buku->stock < 1) {
                return 'stok';
            }

            if (! app(DendaService::class)->bolehMeminjamBaru($idAnggota)) {
                return 'denda';
            }

            $jumlahAktif = Peminjaman::query()
                ->where('id_anggota', $idAnggota)
                ->whereIn('status_peminjaman', Peminjaman::STATUS_AKTIF)
                ->count();

            if ($jumlahAktif >= 3) {
                return 'batas';
            }

            return Peminjaman::create([
                'id_buku' => $buku->id_buku,
                'id_anggota' => $idAnggota,
                'id_petugas' => null,
                'tgl_peminjaman' => Carbon::now('Asia/Jakarta')->toDateString(),
                'tgl_jatuh_tempo' => null,
                'durasi_peminjaman_hari' => $durasiHari,
                'status_peminjaman' => Peminjaman::STATUS_MENUNGGU_VALIDASI,
            ]);
        }, 3);
    }

    private function pesanGagalPengajuan(string $status): string
    {
        return match ($status) {
            'akun_nonaktif' => 'Akun anggota tidak aktif.',
            'buku_diarsipkan' => 'Buku ini sudah diarsipkan dan tidak dapat dipinjam.',
            'stok' => 'Stok buku sedang kosong.',
            'denda' => 'Kamu masih memiliki tagihan denda yang belum lunas, sehingga tidak bisa meminjam buku baru.',
            'batas' => 'Kamu sudah mencapai batas maksimal 3 peminjaman berjalan.',
            default => 'Pengajuan peminjaman tidak dapat diproses.',
        };
    }

    // DAFTAR PENGAJUAN UNTUK PETUGAS
    public function indexPetugas(Request $request)
    {
        $query = Peminjaman::with(['buku', 'anggota', 'petugas']);
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        if ($search !== '') {
            $query->where(function ($loans) use ($search) {
                $loans->whereHas('anggota', function ($members) use ($search) {
                    $members->where('nama_anggota', 'like', "%{$search}%");
                })->orWhereHas('buku', function ($books) use ($search) {
                    $books->where('judul_buku', 'like', "%{$search}%");
                });
            });
        }

        if (in_array($status, [
            Peminjaman::STATUS_MENUNGGU_VALIDASI,
            Peminjaman::STATUS_DIPINJAM,
            Peminjaman::STATUS_TERLAMBAT,
            Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            Peminjaman::STATUS_DIKEMBALIKAN,
            Peminjaman::STATUS_DITOLAK,
            Peminjaman::STATUS_DIBATALKAN,
            Peminjaman::STATUS_HILANG,
        ], true)) {
            $query->where('status_peminjaman', $status);
        }

        $peminjaman = $query->latest('id_peminjaman')
            ->paginate(10)
            ->withQueryString();
        $jumlahMenunggu = Peminjaman::where(
            'status_peminjaman',
            Peminjaman::STATUS_MENUNGGU_VALIDASI
        )->count();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $peminjaman,
            ]);
        }

        return view('petugas.peminjaman.index', [
            'peminjaman' => $peminjaman,
            'jumlahMenunggu' => $jumlahMenunggu,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    public function showPetugas($id)
    {
        $peminjaman = Peminjaman::with(['buku', 'anggota', 'petugas', 'pengembalian'])
            ->findOrFail($id);
        $notifikasiPenolakan = $peminjaman->anggota
            && app(DendaService::class)->notifikasiTersedia()
            ? $peminjaman->anggota->notifikasi()
                ->where('judul', 'Pengajuan peminjaman ditolak')
                ->where('tautan', route('anggota.peminjaman.index', ['penolakan' => $peminjaman->id_peminjaman]))
                ->latest()
                ->first()
            : null;

        return view('petugas.peminjaman.show', compact('peminjaman', 'notifikasiPenolakan'));
    }

    // PETUGAS MENYETUJUI PEMINJAMAN
    public function setujui(Request $request, $id)
    {
        $petugas = auth('petugas')->user();
        $dendaService = app(DendaService::class);

        $hasil = DB::transaction(function () use ($id, $petugas, $dendaService) {
            $peminjaman = Peminjaman::where('id_peminjaman', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $peminjaman->status_peminjaman !==
                Peminjaman::STATUS_MENUNGGU_VALIDASI
            ) {
                return 'status';
            }

            $anggota = Anggota::query()
                ->whereKey($peminjaman->id_anggota)
                ->lockForUpdate()
                ->firstOrFail();

            if ($anggota->status_anggota !== Anggota::STATUS_AKTIF) {
                return 'akun_nonaktif';
            }

            $buku = Buku::where('id_buku', $peminjaman->id_buku)
                ->lockForUpdate()
                ->firstOrFail();

            if ($buku->diarsipkan) {
                return 'buku_diarsipkan';
            }

            if ($buku->stock < 1) {
                $alasan = 'Stok buku habis saat persetujuan.';
                $peminjaman->update([
                    'id_petugas' => $petugas->getAuthIdentifier(),
                    'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
                    'alasan_penolakan' => $alasan,
                ]);
                $dendaService->buatNotifikasi(
                    $peminjaman->id_anggota,
                    null,
                    'Pengajuan peminjaman ditolak',
                    'Pengajuan peminjaman #' . $peminjaman->id_peminjaman . ' ditolak otomatis. Alasan: ' . $alasan,
                    'peminjaman',
                    route('anggota.peminjaman.index')
                );

                return 'stok';
            }

            if (! $dendaService->bolehMeminjamBaru((int) $anggota->getKey())) {
                return 'denda';
            }

            $jumlahAktif = Peminjaman::query()
                ->where('id_anggota', $anggota->getKey())
                ->whereIn('status_peminjaman', Peminjaman::STATUS_AKTIF)
                ->count();

            if ($jumlahAktif > 3) {
                return 'batas';
            }

            $buku->decrement('stock');

            $peminjaman->update([
                'id_petugas' => $petugas->getAuthIdentifier(),
                'tgl_jatuh_tempo' => Carbon::now('Asia/Jakarta')
                    ->addDays((int) ($peminjaman->durasi_peminjaman_hari ?: 7))
                    ->toDateString(),
                'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
            ]);

            return 'berhasil';
        });

        if ($hasil === 'status') {
            $message = 'Pengajuan ini sudah diproses sebelumnya.';

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if (in_array($hasil, ['akun_nonaktif', 'denda', 'batas', 'buku_diarsipkan'], true)) {
            $messages = [
                'akun_nonaktif' => 'Akun anggota tidak aktif.',
                'denda' => 'Anggota memiliki tagihan denda yang belum lunas.',
                'batas' => 'Anggota telah melewati batas maksimal 3 peminjaman berjalan.',
                'buku_diarsipkan' => 'Buku sudah diarsipkan dan tidak dapat disetujui.',
            ];
            $message = $messages[$hasil];

            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

        if ($hasil === 'stok') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok buku sudah habis.',
                ], 422);
            }

            return back()->with('error', 'Stok buku sudah habis.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Peminjaman berhasil disetujui. Stok buku telah diperbarui.',
            ]);
        }

        return back()->with(
            'success',
            'Peminjaman berhasil disetujui. Stok buku telah diperbarui.'
        );
    }

    // PETUGAS MENOLAK PEMINJAMAN
    public function tolak(Request $request, $id)
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:500'],
        ]);
        $petugas = auth('petugas')->user();
        $peminjaman = DB::transaction(function () use ($id, $petugas, $validated) {
            $loan = Peminjaman::where('id_peminjaman', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($loan->status_peminjaman !== Peminjaman::STATUS_MENUNGGU_VALIDASI) {
                return null;
            }

            $loan->update([
                'id_petugas' => $petugas->getAuthIdentifier(),
                'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
                'alasan_penolakan' => $validated['alasan_penolakan'],
            ]);

            app(DendaService::class)->buatNotifikasi(
                $loan->id_anggota,
                null,
                'Pengajuan peminjaman ditolak',
                'Pengajuan peminjaman #' . $loan->id_peminjaman . ' ditolak. Alasan: ' . $validated['alasan_penolakan'],
                'peminjaman',
                route('anggota.peminjaman.index', ['penolakan' => $loan->id_peminjaman])
            );

            return $loan;
        });

        if (! $peminjaman) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini sudah diproses sebelumnya.',
                ], 422);
            }

            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil ditolak dan alasan telah dicatat.',
            ]);
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil ditolak dan alasan telah dicatat.');
    }
    
    // API PENGAJUAN PEMINJAMAN
    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'id_buku' => ['required', 'integer', 'exists:buku,id_buku'],
            'durasi_peminjaman_hari' => ['required', 'integer', 'in:7,14,30'],
        ]);

        $anggota = $request->user();
        $peminjaman = $this->buatPengajuan(
            (int) $validated['id_buku'],
            (int) $anggota->getAuthIdentifier(),
            (int) $validated['durasi_peminjaman_hari']
        );

        if (! $peminjaman instanceof Peminjaman) {
            return response()->json([
                'success' => false,
                'message' => $this->pesanGagalPengajuan($peminjaman),
            ], $peminjaman === 'akun_nonaktif' ? 403 : 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan peminjaman berhasil dikirim.',
            'data' => $peminjaman->load('buku'),
        ], 201);
    }

}
