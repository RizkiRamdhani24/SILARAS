<?php

namespace App\Http\Controllers\Peminjaman;

use App\Http\Controllers\Controller;
use App\Models\Buku;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    // DAFTAR PEMINJAMAN MILIK ANGGOTA
    public function indexAnggota(Request $request)
    {
        $anggota = auth('anggota')->user();

        $peminjaman = Peminjaman::with('buku')
            ->where('id_anggota', $anggota->getAuthIdentifier())
            ->latest('id_peminjaman')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $peminjaman,
            ]);
        }

        return view('anggota.peminjaman.index', compact('peminjaman'));
    }

    // ANGGOTA MENGAJUKAN PEMINJAMAN
    public function store(Request $request)
    {
        $request->validate([
            'id_buku' => ['required', 'integer', 'exists:buku,id_buku'],
        ]);

        $anggota = auth('anggota')->user();

        if ($anggota->status_anggota !== 'aktif') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun anggota tidak aktif.',
                ], 403);
            }

            return back()->with('error', 'Akun anggota tidak aktif.');
        }

        $peminjaman = DB::transaction(function () use ($request, $anggota) {
            $buku = Buku::where('id_buku', $request->id_buku)
                ->lockForUpdate()
                ->firstOrFail();

            if ($buku->stock < 1) {
                return null;
            }

            $sudahMengajukan = Peminjaman::where(
                'id_anggota',
                $anggota->getAuthIdentifier()
            )
                ->where('id_buku', $buku->id_buku)
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_MENUNGGU_VALIDASI,
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->exists();

            if ($sudahMengajukan) {
                return false;
            }

            return Peminjaman::create([
                'id_buku' => $buku->id_buku,
                'id_anggota' => $anggota->getAuthIdentifier(),
                'id_petugas' => null,
                'tgl_peminjaman' => now()->toDateString(),
                'tgl_jatuh_tempo' => null,
                'status_peminjaman' => Peminjaman::STATUS_MENUNGGU_VALIDASI,
            ]);
        });

        if ($peminjaman === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok buku sedang kosong.',
                ], 422);
            }

            return back()->with('error', 'Stok buku sedang kosong.');
        }

        if ($peminjaman === false) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kamu masih memiliki pengajuan atau peminjaman aktif untuk buku ini.',
                ], 422);
            }

            return back()->with(
                'error',
                'Kamu masih memiliki pengajuan atau peminjaman aktif untuk buku ini.'
            );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil dikirim.',
                'data' => $peminjaman->load('buku'),
            ], 201);
        }

        return redirect()
            ->route('anggota.katalog')
            ->with(
                'success',
                'Pengajuan peminjaman berhasil dikirim. Silakan tunggu validasi petugas.'
            );
    }

    // DAFTAR PENGAJUAN UNTUK PETUGAS
    public function indexPetugas(Request $request)
    {
        $peminjaman = Peminjaman::with(['buku', 'anggota', 'petugas'])
            ->latest('id_peminjaman')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $peminjaman,
            ]);
        }

        return view('petugas.peminjaman.index', compact('peminjaman'));
    }

    // PETUGAS MENYETUJUI PEMINJAMAN
    public function setujui(Request $request, $id)
    {
        $petugas = auth('petugas')->user();

        $hasil = DB::transaction(function () use ($id, $petugas) {
            $peminjaman = Peminjaman::where('id_peminjaman', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $peminjaman->status_peminjaman !==
                Peminjaman::STATUS_MENUNGGU_VALIDASI
            ) {
                return 'status';
            }

            $buku = Buku::where('id_buku', $peminjaman->id_buku)
                ->lockForUpdate()
                ->firstOrFail();

            if ($buku->stock < 1) {
                return 'stok';
            }

            $buku->decrement('stock');

            $peminjaman->update([
                'id_petugas' => $petugas->getAuthIdentifier(),
                'tgl_jatuh_tempo' => now()->addDays(7)->toDateString(),
                'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
            ]);

            return 'berhasil';
        });

        if ($hasil === 'status') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini sudah diproses sebelumnya.',
                ], 422);
            }

            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
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
        $petugas = auth('petugas')->user();

        $peminjaman = Peminjaman::findOrFail($id);

        if (
            $peminjaman->status_peminjaman !==
            Peminjaman::STATUS_MENUNGGU_VALIDASI
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan ini sudah diproses sebelumnya.',
                ], 422);
            }

            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $peminjaman->update([
            'id_petugas' => $petugas->getAuthIdentifier(),
            'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil ditolak.',
            ]);
        }

        return back()->with('success', 'Pengajuan peminjaman berhasil ditolak.');
    }
    
    // API PENGAJUAN PEMINJAMAN
    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'id_buku' => ['required', 'integer', 'exists:buku,id_buku'],
        ]);

        $anggota = $request->user();

        if ($anggota->status_anggota !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun anggota tidak aktif.',
            ], 403);
        }

        $peminjaman = DB::transaction(function () use ($validated, $anggota) {
            $buku = Buku::where('id_buku', $validated['id_buku'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($buku->stock < 1) {
                return null;
            }

            $sudahMengajukan = Peminjaman::where(
                'id_anggota',
                $anggota->getAuthIdentifier()
            )
                ->where('id_buku', $buku->id_buku)
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_MENUNGGU_VALIDASI,
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->exists();

            if ($sudahMengajukan) {
                return false;
            }

            return Peminjaman::create([
                'id_buku' => $buku->id_buku,
                'id_anggota' => $anggota->getAuthIdentifier(),
                'id_petugas' => null,
                'tgl_peminjaman' => now()->toDateString(),
                'tgl_jatuh_tempo' => null,
                'status_peminjaman' => Peminjaman::STATUS_MENUNGGU_VALIDASI,
            ]);
        });

        if ($peminjaman === null) {
            return response()->json([
                'success' => false,
                'message' => 'Stok buku sedang kosong.',
            ], 422);
        }

        if ($peminjaman === false) {
            return response()->json([
                'success' => false,
                'message' => 'Kamu masih memiliki pengajuan atau peminjaman aktif untuk buku ini.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan peminjaman berhasil dikirim.',
            'data' => $peminjaman->load('buku'),
        ], 201);
    }

}
