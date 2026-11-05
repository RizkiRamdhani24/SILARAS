<?php

namespace App\Http\Controllers\Peminjaman;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Petugas;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeminjamanController extends Controller
{
    /**
     * Menentukan apakah request berasal dari API atau meminta JSON.
     */
    private function wantsJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Mengambil akun anggota dari Sanctum atau session web.
     */
    private function getAnggota(Request $request): ?Anggota
    {
        $user = $request->user();

        if ($user instanceof Anggota) {
            return $user;
        }

        $anggota = auth('anggota')->user();

        return $anggota instanceof Anggota ? $anggota : null;
    }

    /**
     * Mengambil akun petugas dari Sanctum atau session web.
     */
    private function getPetugas(Request $request): ?Petugas
    {
        $user = $request->user();

        if ($user instanceof Petugas) {
            return $user;
        }

        $petugas = auth('petugas')->user();

        return $petugas instanceof Petugas ? $petugas : null;
    }

    /**
     * Menampilkan antrean pengajuan peminjaman untuk petugas.
     */
    public function index(Request $request)
    {
        $query = Peminjaman::with(['anggota', 'buku', 'petugas'])
            ->where(
                'status_peminjaman',
                Peminjaman::STATUS_MENUNGGU_VALIDASI
            );

        if ($request->filled('q')) {
            $search = trim($request->q);

            $query->where(function ($q) use ($search) {
                $q->whereHas('anggota', function ($anggota) use ($search) {
                    $anggota->where('nama_anggota', 'ilike', "%{$search}%");
                })->orWhereHas('buku', function ($buku) use ($search) {
                    $buku->where('judul_buku', 'ilike', "%{$search}%");
                });
            });
        }

        $peminjaman = $query
            ->latest('id_peminjaman')
            ->paginate(10)
            ->withQueryString();

        $totalAntrean = Peminjaman::where(
            'status_peminjaman',
            Peminjaman::STATUS_MENUNGGU_VALIDASI
        )->count();

        $totalHariIni = Peminjaman::whereDate('tgl_peminjaman', today())
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                Peminjaman::STATUS_DIKEMBALIKAN,
            ])
            ->count();

        $totalStokKritis = Buku::where('stock', '<=', 1)->count();

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Antrean peminjaman berhasil diambil.',
                'data' => $peminjaman,
                'summary' => [
                    'total_antrean' => $totalAntrean,
                    'total_hari_ini' => $totalHariIni,
                    'total_stok_kritis' => $totalStokKritis,
                ],
            ]);
        }

        return view('petugas.peminjaman.index', [
            'peminjaman' => $peminjaman,
            'antrean' => $peminjaman->getCollection(),
            'log' => [],
            'search' => $request->q,
            'totalAntrean' => $totalAntrean,
            'totalHariIni' => $totalHariIni,
            'totalStokKritis' => $totalStokKritis,
        ]);
    }

    /**
     * Mengajukan peminjaman buku oleh anggota.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_buku' => ['required', 'integer', 'exists:buku,id_buku'],
        ], [
            'id_buku.required' => 'Silakan pilih buku yang ingin dipinjam.',
            'id_buku.exists' => 'Buku yang dipilih tidak ditemukan.',
        ]);

        $anggota = $this->getAnggota($request);

        if (! $anggota) {
            abort(403, 'Silakan login sebagai anggota terlebih dahulu.');
        }

        if ($anggota->status_anggota !== Anggota::STATUS_AKTIF) {
            throw ValidationException::withMessages([
                'peminjaman' => 'Akun anggota tidak aktif. Pengajuan peminjaman tidak dapat dilakukan.',
            ]);
        }

        $pengajuan = DB::transaction(function () use ($validated, $anggota) {
            $buku = Buku::where('id_buku', $validated['id_buku'])
                ->lockForUpdate()
                ->first();

            if (! $buku) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Buku tidak ditemukan.',
                ]);
            }

            if ($buku->stock < 1) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Stok buku sedang tidak tersedia.',
                ]);
            }

            $memilikiDenda = Pengembalian::where('denda', '>', 0)
                ->where('status_denda', '!=', Pengembalian::DENDA_LUNAS)
                ->whereHas('peminjaman', function ($query) use ($anggota) {
                    $query->where('id_anggota', $anggota->id_anggota);
                })
                ->exists();

            if ($memilikiDenda) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Masih terdapat denda yang belum lunas. Silakan selesaikan denda terlebih dahulu.',
                ]);
            }

            $jumlahPinjamanAktif = Peminjaman::where(
                'id_anggota',
                $anggota->id_anggota
            )
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->count();

            if ($jumlahPinjamanAktif >= 3) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Batas maksimal peminjaman adalah 3 buku. Silakan selesaikan peminjaman sebelumnya.',
                ]);
            }

            $pengajuanSebelumnya = Peminjaman::where(
                'id_anggota',
                $anggota->id_anggota
            )
                ->where('id_buku', $buku->id_buku)
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_MENUNGGU_VALIDASI,
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->exists();

            if ($pengajuanSebelumnya) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Kamu sudah memiliki pengajuan atau peminjaman aktif untuk buku ini.',
                ]);
            }

            return Peminjaman::create([
                'id_buku' => $buku->id_buku,
                'id_anggota' => $anggota->id_anggota,
                'id_petugas' => null,
                'tgl_peminjaman' => today(),
                'tgl_jatuh_tempo' => null,
                'status_peminjaman' => Peminjaman::STATUS_MENUNGGU_VALIDASI,
            ]);
        });

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil dikirim. Silakan tunggu validasi petugas.',
                'data' => $pengajuan->load(['buku', 'anggota']),
            ], 201);
        }

        return back()->with(
            'success',
            'Pengajuan peminjaman berhasil dikirim. Silakan tunggu validasi petugas.'
        );
    }

    /**
     * Menyetujui pengajuan peminjaman oleh petugas.
     * Stok berkurang satu dan jatuh tempo ditetapkan 7 hari.
     */
    public function approve(Request $request, Peminjaman $peminjaman)
    {
        $petugas = $this->getPetugas($request);

        abort_unless($petugas, 403, 'Akses hanya untuk petugas.');

        $pengajuan = DB::transaction(function () use ($peminjaman, $petugas) {
            $pengajuan = Peminjaman::whereKey($peminjaman->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $pengajuan->status_peminjaman !==
                Peminjaman::STATUS_MENUNGGU_VALIDASI
            ) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            $anggota = Anggota::whereKey($pengajuan->id_anggota)
                ->lockForUpdate()
                ->first();

            if (! $anggota || $anggota->status_anggota !== Anggota::STATUS_AKTIF) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Akun anggota tidak aktif atau tidak ditemukan.',
                ]);
            }

            $buku = Buku::whereKey($pengajuan->id_buku)
                ->lockForUpdate()
                ->first();

            if (! $buku || $buku->stock < 1) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Stok buku sudah tidak tersedia. Pengajuan tidak dapat disetujui.',
                ]);
            }

            $memilikiDenda = Pengembalian::where('denda', '>', 0)
                ->where('status_denda', '!=', Pengembalian::DENDA_LUNAS)
                ->whereHas('peminjaman', function ($query) use ($anggota) {
                    $query->where('id_anggota', $anggota->id_anggota);
                })
                ->exists();

            if ($memilikiDenda) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Anggota masih memiliki denda yang belum lunas.',
                ]);
            }

            $jumlahPinjamanAktif = Peminjaman::where(
                'id_anggota',
                $anggota->id_anggota
            )
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->count();

            if ($jumlahPinjamanAktif >= 3) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Anggota sudah mencapai batas maksimal 3 buku aktif.',
                ]);
            }

            $bukuYangMasihDipinjam = Peminjaman::where(
                'id_anggota',
                $anggota->id_anggota
            )
                ->where('id_buku', $buku->id_buku)
                ->where('id_peminjaman', '!=', $pengajuan->id_peminjaman)
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_MENUNGGU_VALIDASI,
                    Peminjaman::STATUS_DIPINJAM,
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->exists();

            if ($bukuYangMasihDipinjam) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Anggota sudah memiliki pengajuan atau peminjaman aktif untuk buku yang sama.',
                ]);
            }

            $tanggalPinjam = today();

            $buku->decrement('stock');

            $pengajuan->update([
                'id_petugas' => $petugas->id_petugas,
                'tgl_peminjaman' => $tanggalPinjam,
                'tgl_jatuh_tempo' => $tanggalPinjam->copy()->addDays(7),
                'status_peminjaman' => Peminjaman::STATUS_DIPINJAM,
            ]);

            return $pengajuan->fresh(['anggota', 'buku', 'petugas']);
        });

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil disetujui. Stok buku telah diperbarui dan batas pengembalian ditetapkan 7 hari.',
                'data' => $pengajuan,
            ]);
        }

        return back()->with(
            'success',
            'Pengajuan berhasil disetujui. Stok buku telah diperbarui dan batas pengembalian ditetapkan 7 hari.'
        );
    }

    /**
     * Menolak pengajuan peminjaman oleh petugas.
     * Penolakan tidak mengubah stok buku.
     */
    public function reject(Request $request, Peminjaman $peminjaman)
    {
        $petugas = $this->getPetugas($request);

        abort_unless($petugas, 403, 'Akses hanya untuk petugas.');

        $pengajuan = DB::transaction(function () use ($peminjaman, $petugas) {
            $pengajuan = Peminjaman::whereKey($peminjaman->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $pengajuan->status_peminjaman !==
                Peminjaman::STATUS_MENUNGGU_VALIDASI
            ) {
                throw ValidationException::withMessages([
                    'peminjaman' => 'Pengajuan ini sudah diproses sebelumnya.',
                ]);
            }

            $pengajuan->update([
                'id_petugas' => $petugas->id_petugas,
                'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
            ]);

            return $pengajuan->fresh(['anggota', 'buku', 'petugas']);
        });

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan peminjaman berhasil ditolak. Stok buku tidak berubah.',
                'data' => $pengajuan,
            ]);
        }

        return back()->with(
            'success',
            'Pengajuan peminjaman berhasil ditolak. Stok buku tidak berubah.'
        );
    }
}
