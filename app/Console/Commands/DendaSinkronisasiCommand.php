<?php

namespace App\Console\Commands;

use App\Models\Peminjaman;
use App\Services\DendaService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DendaSinkronisasiCommand extends Command
{
    protected $signature = 'denda:sinkronisasi';

    protected $description = 'Sinkronisasi tagihan denda harian untuk peminjaman yang sudah lewat jatuh tempo.';

    public function handle(DendaService $dendaService): int
    {
        try {
            $tanggalHariIni = Carbon::now('Asia/Jakarta')->toDateString();
            $sekarang = Carbon::now('Asia/Jakarta');

            Peminjaman::query()
                ->where('status_peminjaman', Peminjaman::STATUS_DIPINJAM)
                ->whereNotNull('tgl_jatuh_tempo')
                ->whereDate('tgl_jatuh_tempo', '<', $tanggalHariIni)
                ->update(['status_peminjaman' => Peminjaman::STATUS_TERLAMBAT]);

            $pengajuanKedaluwarsa = Peminjaman::query()
                ->where('status_peminjaman', Peminjaman::STATUS_MENUNGGU_VALIDASI)
                ->where('created_at', '<', $sekarang->copy()->subDays(3))
                ->get();

            foreach ($pengajuanKedaluwarsa as $pengajuan) {
                DB::transaction(function () use ($pengajuan, $sekarang, $dendaService) {
                    $terkunci = Peminjaman::query()
                        ->whereKey($pengajuan->getKey())
                        ->lockForUpdate()
                        ->first();

                    if (
                        ! $terkunci
                        || $terkunci->status_peminjaman !== Peminjaman::STATUS_MENUNGGU_VALIDASI
                        || ! $terkunci->created_at?->lt($sekarang->copy()->subDays(3))
                    ) {
                        return;
                    }

                    $alasan = 'Pengajuan kedaluwarsa setelah 3 hari tanpa diproses.';
                    $terkunci->update([
                        'status_peminjaman' => Peminjaman::STATUS_DITOLAK,
                        'alasan_penolakan' => $alasan,
                    ]);

                    $dendaService->buatNotifikasi(
                        $terkunci->id_anggota,
                        null,
                        'Pengajuan peminjaman kedaluwarsa',
                        'Pengajuan peminjaman #' . $terkunci->id_peminjaman . ' ditolak otomatis. Alasan: ' . $alasan,
                        'peminjaman',
                        route('anggota.peminjaman.index')
                    );
                }, 3);
            }

            $peminjamanTerlambat = Peminjaman::query()
                ->whereNotNull('tgl_jatuh_tempo')
                ->whereDate('tgl_jatuh_tempo', '<', $tanggalHariIni)
                ->whereIn('status_peminjaman', [
                    Peminjaman::STATUS_TERLAMBAT,
                    Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                ])
                ->with('pengembalian')
                ->get();

            $jumlahDiperbarui = 0;

            foreach ($peminjamanTerlambat as $peminjaman) {
                $tagihan = $dendaService->syncTagihanForPeminjaman($peminjaman);

                if ($tagihan) {
                    $jumlahDiperbarui++;
                }
            }

            $this->info("Sinkronisasi denda selesai. {$jumlahDiperbarui} tagihan diperbarui.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Gagal menjalankan sinkronisasi denda harian.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error('Sinkronisasi denda gagal: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
