<?php

namespace App\Http\Controllers\Pengembalian;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    // Anggota melihat daftar peminjamannya yang bisa dikembalikan
    public function indexAnggota()
    {
        $peminjaman = Peminjaman::with(['buku', 'pengembalian'])
            ->where('id_anggota', auth('anggota')->id())
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                Peminjaman::STATUS_DIKEMBALIKAN,
            ])
            ->latest()
            ->get();

        return view('anggota.pengembalian.index', compact('peminjaman'));
    }

    // Anggota mengajukan pengembalian
    public function ajukan(Request $request, $id)
    {
        $anggotaId = auth('anggota')->id();

        DB::transaction(function () use ($id, $anggotaId) {
            $peminjaman = Peminjaman::where('id_peminjaman', $id)
                ->where('id_anggota', $anggotaId)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($peminjaman->status_peminjaman, [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
            ])) {
                abort(422, 'Peminjaman ini tidak dapat diajukan untuk pengembalian.');
            }

            Pengembalian::create([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'id_petugas' => null,
                'tgl_pengembalian' => now()->toDateString(),
                'denda' => 0,
                'kondisi_buku' => null,
                'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
                'status_denda' => Pengembalian::DENDA_TIDAK_ADA,
            ]);

            $peminjaman->update([
                'status_peminjaman' => Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            ]);
        });

        return redirect()->back()->with(
            'success',
            'Pengajuan pengembalian berhasil dikirim.'
        );
    }

    // Petugas melihat daftar pengembalian
    public function indexPetugas()
    {
        $pengembalian = Pengembalian::with([
            'peminjaman.buku',
            'peminjaman.anggota',
            'petugas',
        ])->latest()->get();

        return view('petugas.pengembalian.index', compact('pengembalian'));
    }

    // Petugas memvalidasi pengembalian
    public function setujui(Request $request, $id)
    {
        $validated = $request->validate([
            'kondisi_buku' => 'required|in:baik,rusak,hilang',
        ]);

        $denda = 0;

        DB::transaction(function () use ($id, $validated, &$denda) {
            $pengembalian = Pengembalian::where(
                'id_pengembalian',
                $id
            )->lockForUpdate()->firstOrFail();

            if ($pengembalian->status_pengembalian !== Pengembalian::STATUS_MENUNGGU_VALIDASI) {
                abort(422, 'Pengembalian ini sudah diproses.');
            }

            $peminjaman = Peminjaman::where(
                'id_peminjaman',
                $pengembalian->id_peminjaman
            )->lockForUpdate()->firstOrFail();

            $buku = $peminjaman->buku()->lockForUpdate()->firstOrFail();

            // Hitung denda Rp2.000 per hari keterlambatan
            $tanggalKembali = \Carbon\Carbon::parse(
                $pengembalian->tgl_pengembalian
            )->startOfDay();

            if ($peminjaman->tgl_jatuh_tempo) {
                $jatuhTempo = \Carbon\Carbon::parse(
                    $peminjaman->tgl_jatuh_tempo
                )->startOfDay();

                if ($tanggalKembali->greaterThan($jatuhTempo)) {
                    $hariTerlambat = $jatuhTempo->diffInDays($tanggalKembali);
                    $denda = $hariTerlambat * 2000;
                }
            }

            $statusDenda = $denda > 0
                ? Pengembalian::DENDA_BELUM_DIBAYAR
                : Pengembalian::DENDA_TIDAK_ADA;

            $pengembalian->update([
                'id_petugas' => auth('petugas')->id(),
                'kondisi_buku' => $validated['kondisi_buku'],
                'denda' => $denda,
                'status_pengembalian' => Pengembalian::STATUS_SELESAI,
                'status_denda' => $statusDenda,
            ]);

            $peminjaman->update([
                'status_peminjaman' => Peminjaman::STATUS_DIKEMBALIKAN,
            ]);

            // Hanya buku kondisi baik yang kembali menjadi stok tersedia
            if ($validated['kondisi_buku'] === Pengembalian::KONDISI_BAIK) {
                $buku->increment('stock');
            }
        });

        return redirect()->back()->with(
            'success',
            'Pengembalian berhasil divalidasi. Denda: Rp' . number_format($denda, 0, ',', '.')
        );
    }
}
