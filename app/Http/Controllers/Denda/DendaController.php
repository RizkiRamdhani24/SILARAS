<?php

namespace App\Http\Controllers\Denda;

use App\Http\Controllers\Controller;
use App\Models\PembayaranDenda;
use App\Models\TagihanDenda;
use App\Services\DendaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use LogicException;
use RuntimeException;
use Throwable;

class DendaController extends Controller
{
    public function indexAnggota()
    {
        $anggota = auth('anggota')->user();

        $tagihan = TagihanDenda::with(['peminjaman.buku', 'pembayaran'])
            ->where('id_anggota', $anggota->getAuthIdentifier())
            ->latest('updated_at')
            ->get();

        return view('anggota.denda.index', compact('tagihan'));
    }

    public function showAnggota(TagihanDenda $tagihan)
    {
        $anggota = auth('anggota')->user();

        abort_unless(
            (int) $tagihan->id_anggota === (int) $anggota->getAuthIdentifier(),
            403
        );

        $tagihan->load(['peminjaman.buku', 'pembayaran']);

        return view('anggota.denda.show', compact('tagihan'));
    }

    public function lihatQris(TagihanDenda $tagihan)
    {
        $anggota = auth('anggota')->user();
        abort_unless(
            (int) $tagihan->id_anggota === (int) $anggota->getAuthIdentifier(),
            403
        );

        $path = Storage::disk('s3')->path('qris/qris.png');
        if (! is_file($path)) {
            Log::error('QRIS image file not found.', [
                'path' => $path,
                'tagihan_id' => $tagihan->id_tagihan_denda,
            ]);

            abort(404, 'Gambar QRIS tidak ditemukan.');
        }

        $image = file_get_contents($path);
        if ($image === false) {
            Log::error('QRIS image file could not be read.', [
                'path' => $path,
                'tagihan_id' => $tagihan->id_tagihan_denda,
            ]);

            abort(500, 'Gambar QRIS tidak dapat dibaca.');
        }

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Length' => (string) strlen($image),
        ]);
    }

    public function indexPetugas(Request $request)
    {
        $tagihanQuery = TagihanDenda::with([
            'anggota',
            'peminjaman.buku',
            'peminjaman.pengembalian',
            'pembayaran',
        ]);
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));

        if ($search !== '') {
            $tagihanQuery->where(function ($bills) use ($search) {
                $bills->whereHas('anggota', function ($members) use ($search) {
                    $members->where('nama_anggota', 'like', "%{$search}%");
                })->orWhereHas('peminjaman.buku', function ($books) use ($search) {
                    $books->where('judul_buku', 'like', "%{$search}%");
                });

                if (ctype_digit($search)) {
                    $bills->orWhere('id_tagihan_denda', (int) $search);
                }
            });
        }

        if (in_array($status, [
            TagihanDenda::STATUS_BELUM_LUNAS,
            TagihanDenda::STATUS_LUNAS,
            TagihanDenda::STATUS_TIDAK_ADA,
        ], true)) {
            $tagihanQuery->where('status_tagihan', $status);
        }

        $tagihan = $tagihanQuery->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $pembayaranMenungguVerifikasi = PembayaranDenda::with([
            'tagihanDenda.peminjaman.buku',
            'anggota',
        ])
            ->where('status_pembayaran', 'menunggu_verifikasi')
            ->latest('waktu_pembayaran')
            ->paginate(10, ['*'], 'pembayaran_page')
            ->withQueryString();

        return view('petugas.denda.index', [
            'tagihan' => $tagihan,
            'pembayaranMenungguVerifikasi' => $pembayaranMenungguVerifikasi,
            'search' => $search,
            'selectedStatus' => $status,
            'tarifDendaPerHari' => app(DendaService::class)->tarifDendaPerHari(),
        ]);
    }

    public function showPetugas(TagihanDenda $tagihan)
    {
        $tagihan->load(['anggota', 'peminjaman.buku', 'pembayaran']);

        return view('petugas.denda.show', compact('tagihan'));
    }

    public function updateTarif(Request $request)
    {
        $validated = $request->validate([
            'tarif_denda_per_hari' => ['required', 'integer', 'min:0'],
        ]);

        $affected = DB::table('pengaturan')
            ->where('kunci', 'tarif_denda_per_hari')
            ->update([
                'nilai' => (string) $validated['tarif_denda_per_hari'],
                'updated_at' => Carbon::now('UTC'),
            ]);

        if ($affected === 0 && ! DB::table('pengaturan')->where('kunci', 'tarif_denda_per_hari')->exists()) {
            throw new LogicException('Pengaturan tarif_denda_per_hari tidak ditemukan.');
        }

        return redirect()->back()->with('success', 'Tarif denda berhasil diperbarui.');
    }

    public function storePembayaran(Request $request, TagihanDenda $tagihan)
    {
        $anggota = auth('anggota')->user();
        $validated = $request->validate([
            'metode_pembayaran' => ['required', 'in:qris'],
            'jumlah_pembayaran' => ['required', 'numeric', 'decimal:0,2', 'min:0.01'],
            'bukti_pembayaran' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);
        $service = app(DendaService::class);
        $path = null;

        try {
            DB::transaction(function () use ($request, $tagihan, $anggota, $validated, $service, &$path) {
                $lockedTagihan = TagihanDenda::query()
                    ->whereKey($tagihan->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $lockedTagihan->id_anggota !== (int) $anggota->getAuthIdentifier()) {
                    abort(403);
                }

                if ((int) $lockedTagihan->peminjaman()->value('id_anggota') !== (int) $lockedTagihan->id_anggota) {
                    throw new LogicException('Anggota pada tagihan tidak sesuai dengan anggota pada peminjaman.');
                }

                if (
                    $service->jumlahPembayaranDalamSen($lockedTagihan->nominal_tagihan) <= 0
                    || $lockedTagihan->status_tagihan === TagihanDenda::STATUS_TIDAK_ADA
                ) {
                    throw ValidationException::withMessages([
                        'jumlah_pembayaran' => 'Tagihan ini tidak memiliki nominal yang perlu dibayar.',
                    ]);
                }

                $jumlahSen = $service->jumlahPembayaranDalamSen($validated['jumlah_pembayaran']);
                $nominalTagihanSen = $service->jumlahPembayaranDalamSen($lockedTagihan->nominal_tagihan);
                if ($jumlahSen !== $nominalTagihanSen) {
                    throw ValidationException::withMessages([
                        'jumlah_pembayaran' => 'Pembayaran QRIS harus sama dengan nominal tagihan penuh.',
                    ]);
                }

                if ($lockedTagihan->pembayaran()->where('status_pembayaran', 'menunggu_verifikasi')->exists()) {
                    throw ValidationException::withMessages([
                        'metode_pembayaran' => 'Tagihan ini sudah memiliki pembayaran yang menunggu verifikasi.',
                    ]);
                }

                $path = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 's3');
                if (! $path) {
                    throw new RuntimeException('Bukti pembayaran gagal disimpan.');
                }

                PembayaranDenda::create([
                    'id_tagihan_denda' => $lockedTagihan->id_tagihan_denda,
                    'id_anggota' => $anggota->getAuthIdentifier(),
                    'metode_pembayaran' => 'qris',
                    'jumlah_pembayaran' => $service->formatJumlahDalamSen($jumlahSen),
                    'bukti_pembayaran' => $path,
                    'status_pembayaran' => 'menunggu_verifikasi',
                    'waktu_pembayaran' => Carbon::now('UTC'),
                ]);

                $service->buatNotifikasi(
                    $anggota->getAuthIdentifier(),
                    null,
                    'Pembayaran denda dikirim',
                    'Pembayaran denda kamu telah dikirim dan menunggu verifikasi petugas.',
                    'denda',
                    route('anggota.denda.show', ['tagihan' => $lockedTagihan->id_tagihan_denda])
                );
            }, 3);
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('s3')->delete($path);
            }

            throw $exception;
        }

        return redirect()->back()->with('success', 'Pembayaran denda berhasil dikirim dan menunggu verifikasi petugas.');
    }

    public function catatTunai(TagihanDenda $tagihan)
    {
        $petugas = auth('petugas')->user();
        abort_unless($petugas, 403);
        $service = app(DendaService::class);

        DB::transaction(function () use ($tagihan, $petugas, $service) {
            $lockedTagihan = TagihanDenda::query()
                ->whereKey($tagihan->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $nominalSen = $service->jumlahPembayaranDalamSen($lockedTagihan->nominal_tagihan);

            if ($nominalSen <= 0 || $lockedTagihan->status_tagihan !== TagihanDenda::STATUS_BELUM_LUNAS) {
                throw ValidationException::withMessages([
                    'tagihan' => 'Tagihan tidak memiliki kewajiban yang belum lunas.',
                ]);
            }

            if ($lockedTagihan->pembayaran()->where('status_pembayaran', 'menunggu_verifikasi')->exists()) {
                throw ValidationException::withMessages([
                    'tagihan' => 'Selesaikan pembayaran pending sebelum mencatat pembayaran tunai.',
                ]);
            }

            PembayaranDenda::create([
                'id_tagihan_denda' => $lockedTagihan->id_tagihan_denda,
                'id_anggota' => $lockedTagihan->id_anggota,
                'id_petugas_verifikasi' => $petugas->getAuthIdentifier(),
                'metode_pembayaran' => 'tunai',
                'jumlah_pembayaran' => $service->formatJumlahDalamSen($nominalSen),
                'status_pembayaran' => 'diverifikasi',
                'catatan_petugas' => 'Pembayaran tunai dicatat langsung oleh Petugas.',
                'waktu_pembayaran' => Carbon::now('UTC'),
                'waktu_verifikasi' => Carbon::now('UTC'),
            ]);

            $service->sinkronkanStatusTagihan($lockedTagihan);
        }, 3);

        return redirect()->back()->with('success', 'Pembayaran tunai berhasil dicatat sebagai lunas.');
    }

    public function lihatBukti(PembayaranDenda $pembayaran)
    {
        abort_unless($pembayaran->bukti_pembayaran, 404);
        abort_unless(Storage::disk('s3')->exists($pembayaran->bukti_pembayaran), 404);

        return Storage::disk('s3')->response(
            $pembayaran->bukti_pembayaran,
            null,
            ['X-Content-Type-Options' => 'nosniff'],
            'inline'
        );
    }

    public function verifikasiPembayaran(Request $request, PembayaranDenda $pembayaran)
    {
        $request->validate([
            'status_pembayaran' => ['required', 'in:diverifikasi,ditolak'],
            'catatan_petugas' => ['required_if:status_pembayaran,ditolak', 'nullable', 'string', 'max:255'],
        ]);

        $petugas = auth('petugas')->user();
        abort_unless($petugas, 403);

        $service = app(DendaService::class);
        DB::transaction(function () use ($pembayaran, $request, $petugas, $service) {
            $tagihanId = $pembayaran->id_tagihan_denda;
            $tagihan = TagihanDenda::query()
                ->whereKey($tagihanId)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPembayaran = PembayaranDenda::query()
                ->whereKey($pembayaran->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $lockedPembayaran->id_tagihan_denda !== (int) $tagihan->id_tagihan_denda) {
                throw new LogicException('Pembayaran tidak sesuai dengan tagihan yang dikunci.');
            }

            if ((int) $lockedPembayaran->id_anggota !== (int) $tagihan->id_anggota) {
                throw new LogicException('Anggota pada pembayaran tidak sesuai dengan anggota pada tagihan.');
            }

            if ((int) $tagihan->peminjaman()->value('id_anggota') !== (int) $tagihan->id_anggota) {
                throw new LogicException('Anggota pada tagihan tidak sesuai dengan anggota pada peminjaman.');
            }

            if ($lockedPembayaran->status_pembayaran !== 'menunggu_verifikasi') {
                throw ValidationException::withMessages([
                    'status_pembayaran' => 'Pembayaran ini sudah diproses sebelumnya.',
                ]);
            }

            $jumlahSen = $service->jumlahPembayaranDalamSen($lockedPembayaran->jumlah_pembayaran);
            if ($request->status_pembayaran === 'diverifikasi' && $jumlahSen <= 0) {
                throw ValidationException::withMessages([
                    'status_pembayaran' => 'Pembayaran dengan nominal nol atau negatif tidak dapat diverifikasi.',
                ]);
            }

            if (
                $request->status_pembayaran === 'diverifikasi'
                && $jumlahSen !== $service->jumlahPembayaranDalamSen($tagihan->nominal_tagihan)
            ) {
                throw ValidationException::withMessages([
                    'status_pembayaran' => 'Pembayaran melebihi sisa kewajiban dan tidak dapat diverifikasi.',
                ]);
            }

            $lockedPembayaran->update([
                'id_petugas_verifikasi' => $petugas->getAuthIdentifier(),
                'status_pembayaran' => $request->status_pembayaran,
                'catatan_petugas' => $request->catatan_petugas,
                'waktu_verifikasi' => Carbon::now('UTC'),
            ]);

            $service->sinkronkanStatusTagihan($tagihan);
            $tagihan->tgl_terakhir_diperbarui = Carbon::now('Asia/Jakarta')->toDateString();
            $tagihan->save();

            $service->buatNotifikasi(
                $lockedPembayaran->id_anggota,
                null,
                $request->status_pembayaran === 'diverifikasi' ? 'Pembayaran denda diterima' : 'Pembayaran denda ditolak',
                $request->status_pembayaran === 'diverifikasi'
                    ? 'Pembayaran denda kamu telah diverifikasi oleh petugas dan status tagihan diperbarui.'
                    : 'Pembayaran denda kamu ditolak. Alasan: '.$request->catatan_petugas,
                'denda',
                route('anggota.denda.show', ['tagihan' => $tagihan->id_tagihan_denda])
            );
        }, 3);

        return redirect()->back()->with('success', 'Status pembayaran berhasil diperbarui.');
    }
}
