<?php

namespace App\Http\Controllers\Pengembalian;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\Petugas;
use App\Models\Pengembalian;
use App\Services\DendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianController extends Controller
{
    // Anggota melihat daftar peminjamannya yang bisa dikembalikan
    public function indexAnggota()
    {
        $peminjaman = Peminjaman::with(['buku', 'pengembalian', 'pengembalians'])
            ->where('id_anggota', auth('anggota')->id())
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                Peminjaman::STATUS_DIKEMBALIKAN,
            ])
            ->latest('id_peminjaman')
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

            if (! in_array($peminjaman->status_peminjaman, [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
            ], true) || $peminjaman->pengembalianAktif()->exists()) {
                abort(422, 'Peminjaman ini tidak dapat diajukan untuk pengembalian.');
            }

            $pengembalian = Pengembalian::create([
                'id_peminjaman' => $peminjaman->id_peminjaman,
                'id_petugas' => null,
                'tgl_pengembalian' => null,
                'denda' => 0,
                'kondisi_buku' => null,
                'status_pengembalian' => Pengembalian::STATUS_MENUNGGU_VALIDASI,
                'status_denda' => Pengembalian::DENDA_TIDAK_ADA,
            ]);

            $peminjaman->update([
                'status_peminjaman' => Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            ]);

            $notifikasiService = app(DendaService::class);
            foreach (Petugas::query()->get(['id_petugas']) as $petugas) {
                $notifikasiService->buatNotifikasi(
                    null,
                    $petugas->id_petugas,
                    'Pengajuan pengembalian baru',
                    'Anggota mengajukan pengembalian untuk peminjaman #' . $peminjaman->id_peminjaman . '.',
                    'pengembalian',
                    route('petugas.pengembalian.index', ['pengembalian' => $pengembalian->id_pengembalian])
                );
            }
        });

        return redirect()->route('anggota.pengembalian.index')->with(
            'success',
            'Pengajuan pengembalian berhasil dikirim.'
        );
    }

    public function batalkan(Request $request, int $id)
    {
        $anggotaId = (int) auth('anggota')->id();
        $dibatalkan = DB::transaction(function () use ($id, $anggotaId) {
            $pengembalian = Pengembalian::query()
                ->whereKey($id)
                ->whereHas('peminjaman', fn ($query) => $query->where('id_anggota', $anggotaId))
                ->lockForUpdate()
                ->firstOrFail();

            if ($pengembalian->status_pengembalian !== Pengembalian::STATUS_MENUNGGU_VALIDASI) {
                return false;
            }

            $peminjaman = Peminjaman::query()
                ->whereKey($pengembalian->id_peminjaman)
                ->lockForUpdate()
                ->firstOrFail();

            $pengembalian->update([
                'status_pengembalian' => Pengembalian::STATUS_DIBATALKAN,
            ]);

            $statusAktif = $peminjaman->tgl_jatuh_tempo
                && $peminjaman->tgl_jatuh_tempo->toDateString()
                    < Carbon::now('Asia/Jakarta')->toDateString()
                ? Peminjaman::STATUS_TERLAMBAT
                : Peminjaman::STATUS_DIPINJAM;

            $peminjaman->update(['status_peminjaman' => $statusAktif]);

            return true;
        }, 3);

        if (! $dibatalkan) {
            return back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan.');
        }

        return back()->with('success', 'Pengajuan pengembalian dibatalkan dan tersimpan di riwayat.');
    }

    // Petugas melihat daftar pengembalian
    public function indexPetugas(Request $request)
    {
        $query = Pengembalian::with([
            'peminjaman.buku',
            'peminjaman.anggota',
            'petugas',
        ]);
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        if ($search !== '') {
            $query->whereHas('peminjaman', function ($loans) use ($search) {
                $loans->whereHas('anggota', function ($members) use ($search) {
                    $members->where('nama_anggota', 'like', "%{$search}%");
                })->orWhereHas('buku', function ($books) use ($search) {
                    $books->where('judul_buku', 'like', "%{$search}%");
                });
            });
        }

        if (in_array($status, [
            Pengembalian::STATUS_MENUNGGU_VALIDASI,
            Pengembalian::STATUS_SELESAI,
            Pengembalian::STATUS_DIBATALKAN,
        ], true)) {
            $query->where('status_pengembalian', $status);
        }

        $pengembalian = $query->latest('id_pengembalian')
            ->paginate(10)
            ->withQueryString();
        $jumlahMenunggu = Pengembalian::where(
            'status_pengembalian',
            Pengembalian::STATUS_MENUNGGU_VALIDASI
        )->count();

        return view('petugas.pengembalian.index', [
            'pengembalian' => $pengembalian,
            'jumlahMenunggu' => $jumlahMenunggu,
            'search' => $search,
            'selectedStatus' => $status,
        ]);
    }

    public function formInputPetugas()
    {
        $peminjamanAktif = Peminjaman::with(['anggota', 'buku'])
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
            ])
            ->whereDoesntHave('pengembalianAktif')
            ->orderByDesc('id_peminjaman')
            ->get();

        return view('petugas.pengembalian.input', compact('peminjamanAktif'));
    }

    public function storeLangsung(Request $request)
    {
        $validated = $request->validate([
            'id_peminjaman' => ['required', 'integer', 'exists:peminjaman,id_peminjaman'],
            'tgl_pengembalian' => [
                'required',
                'date',
                'before_or_equal:' . Carbon::now('Asia/Jakarta')->toDateString(),
            ],
            'kondisi_buku' => ['required', 'in:baik,rusak,hilang'],
        ]);
        $petugas = auth('petugas')->user();

        DB::transaction(function () use ($validated, $petugas) {
            $peminjaman = Peminjaman::where('id_peminjaman', $validated['id_peminjaman'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanPeminjamanAktif($peminjaman);

            $pengembalian = new Pengembalian([
                'id_petugas' => $petugas->getAuthIdentifier(),
                'tgl_pengembalian' => $validated['tgl_pengembalian'],
                'denda' => 0,
                'kondisi_buku' => $validated['kondisi_buku'],
                'status_pengembalian' => Pengembalian::STATUS_SELESAI,
                'status_denda' => Pengembalian::DENDA_TIDAK_ADA,
            ]);
            $pengembalian->peminjaman()->associate($peminjaman);
            $pengembalian->save();

            $this->selesaikanPengembalian($peminjaman, $pengembalian, $validated, $petugas);
        }, 3);

        return redirect()->route('petugas.pengembalian.index')
            ->with('success', 'Pengembalian langsung berhasil dicatat.');
    }

    public function setujui(Request $request, $id)
    {
        $validated = $request->validate([
            'kondisi_buku' => ['required', 'in:baik,rusak,hilang'],
            'tgl_pengembalian' => [
                'nullable',
                'date',
                'before_or_equal:' . Carbon::now('Asia/Jakarta')->toDateString(),
            ],
        ]);
        $petugas = auth('petugas')->user();

        DB::transaction(function () use ($id, $validated, $petugas) {
            $pengembalian = Pengembalian::where('id_pengembalian', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($pengembalian->status_pengembalian !== Pengembalian::STATUS_MENUNGGU_VALIDASI) {
                abort(422, 'Pengembalian ini sudah diproses.');
            }

            $peminjaman = Peminjaman::where(
                'id_peminjaman',
                $pengembalian->id_peminjaman
            )->lockForUpdate()->firstOrFail();

            if ($peminjaman->status_peminjaman !== Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN) {
                abort(422, 'Status peminjaman tidak sesuai dengan pengajuan pengembalian.');
            }

            $this->selesaikanPengembalian($peminjaman, $pengembalian, $validated, $petugas);
        }, 3);

        return redirect()->back()->with('success', 'Pengembalian berhasil dikonfirmasi.');
    }

    private function pastikanPeminjamanAktif(Peminjaman $peminjaman): void
    {
        if (! in_array($peminjaman->status_peminjaman, [
            Peminjaman::STATUS_DIPINJAM,
            Peminjaman::STATUS_TERLAMBAT,
        ], true) || $peminjaman->pengembalianAktif()->exists()) {
            abort(422, 'Peminjaman tidak aktif atau sudah memiliki transaksi pengembalian.');
        }
    }

    private function selesaikanPengembalian(
        Peminjaman $peminjaman,
        Pengembalian $pengembalian,
        array $validated,
        Petugas $petugas
    ): void {
        $buku = $peminjaman->buku()->lockForUpdate()->firstOrFail();
        $tanggalKembali = Carbon::parse(
            $validated['tgl_pengembalian'] ?? Carbon::now('Asia/Jakarta')->toDateString(),
            'Asia/Jakarta'
        )->startOfDay();
        $dendaService = app(DendaService::class);
        $tagihan = $dendaService->syncTagihanForPeminjaman(
            $peminjaman,
            $tanggalKembali,
            $validated['kondisi_buku'],
            true
        );
        $denda = $tagihan ? (float) $tagihan->nominal_tagihan : 0.0;
        $statusDenda = $tagihan
            ? $dendaService->statusDendaPengembalian($tagihan)
            : Pengembalian::DENDA_TIDAK_ADA;

        $pengembalian->update([
            'id_petugas' => $petugas->getAuthIdentifier(),
            'tgl_pengembalian' => $tanggalKembali->toDateString(),
            'kondisi_buku' => $validated['kondisi_buku'],
            'denda' => $denda,
            'status_pengembalian' => Pengembalian::STATUS_SELESAI,
            'status_denda' => $statusDenda,
        ]);
        $peminjaman->update([
            'id_petugas' => $petugas->getAuthIdentifier(),
            'status_peminjaman' => $validated['kondisi_buku'] === Pengembalian::KONDISI_HILANG
                ? Peminjaman::STATUS_HILANG
                : Peminjaman::STATUS_DIKEMBALIKAN,
        ]);

        if ($validated['kondisi_buku'] === Pengembalian::KONDISI_BAIK) {
            $buku->increment('stock');
        }

        $dendaService->buatNotifikasi(
            $peminjaman->id_anggota,
            null,
            'Pengembalian dikonfirmasi',
            'Pengembalian buku untuk peminjaman #' . $peminjaman->id_peminjaman . ' telah dikonfirmasi.'
                . ($denda > 0 ? ' Tagihan denda: Rp' . number_format($denda, 0, ',', '.') . '.' : ' Tidak ada denda keterlambatan.'),
            'pengembalian',
            route('anggota.pengembalian.index')
        );

        if ($denda > 0) {
            $dendaService->buatNotifikasi(
                $peminjaman->id_anggota,
                null,
                'Tagihan denda diterbitkan',
                'Petugas telah menerbitkan tagihan denda untuk peminjaman #' . $peminjaman->id_peminjaman . '.',
                'denda',
                route('anggota.denda.index')
            );
        }
    }
}
