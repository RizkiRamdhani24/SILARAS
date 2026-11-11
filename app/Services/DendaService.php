<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\TagihanDenda;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use LogicException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DendaService
{
    private const BUSINESS_TIMEZONE = 'Asia/Jakarta';
    private const DENDA_RUSAK = 50000;
    private const DENDA_HILANG = 100000;

    public function tarifDendaPerHari(): float
    {
        $nilai = $this->nilaiPengaturan('tarif_denda_per_hari');

        if (! is_numeric($nilai) || (float) $nilai < 0) {
            throw new LogicException('Pengaturan tarif_denda_per_hari harus berupa angka nonnegatif.');
        }

        return (float) $nilai;
    }

    public function lamaPeminjamanHari(): int
    {
        $nilai = $this->nilaiPengaturan('lama_peminjaman_hari');

        if (! ctype_digit($nilai) || (int) $nilai < 1) {
            throw new LogicException('Pengaturan lama_peminjaman_hari harus berupa bilangan bulat lebih besar dari nol.');
        }

        return (int) $nilai;
    }

    public function hitungNominalDenda(
        Peminjaman $peminjaman,
        ?DateTimeInterface $tanggalAkhir = null
    ): float
    {
        if (! $peminjaman->tgl_jatuh_tempo) {
            return 0.0;
        }

        $jatuhTempo = $this->tanggalOperasional($peminjaman->tgl_jatuh_tempo);
        $tanggalSekarang = $tanggalAkhir
            ? $this->tanggalOperasional($tanggalAkhir)
            : Carbon::now(self::BUSINESS_TIMEZONE)->startOfDay();

        if ($tanggalSekarang->lessThanOrEqualTo($jatuhTempo)) {
            return 0.0;
        }

        $hariTerlambat = $jatuhTempo->diffInDays($tanggalSekarang);

        return max(0, (int) $hariTerlambat) * $this->tarifDendaPerHari();
    }

    public function syncTagihanForPeminjaman(
        Peminjaman $peminjaman,
        ?DateTimeInterface $tanggalAkhir = null,
        ?string $kondisiBuku = null,
        bool $bekukan = false
    ): ?TagihanDenda
    {
        if (! $peminjaman->exists) {
            return null;
        }

        $existingTagihan = TagihanDenda::query()
            ->where('id_peminjaman', $peminjaman->id_peminjaman)
            ->first();

        if ($existingTagihan?->dibekukan_at) {
            return $existingTagihan;
        }

        if ($tanggalAkhir === null) {
            $pengembalian = $peminjaman->pengembalianAktif()->first();

            if (
                $pengembalian?->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI
                && $pengembalian->tgl_pengembalian
            ) {
                $tanggalAkhir = $pengembalian->tgl_pengembalian;
                $kondisiBuku = $pengembalian->kondisi_buku;
                $bekukan = true;
            }
        }

        $nominalTerlambat = $this->hitungNominalDenda($peminjaman, $tanggalAkhir);
        $nominalRusak = $kondisiBuku === Pengembalian::KONDISI_RUSAK
            ? self::DENDA_RUSAK
            : 0;
        $nominalHilang = $kondisiBuku === Pengembalian::KONDISI_HILANG
            ? self::DENDA_HILANG
            : 0;
        $nominal = $nominalTerlambat + $nominalRusak + $nominalHilang;

        return DB::transaction(function () use (
            $peminjaman,
            $nominal,
            $nominalTerlambat,
            $nominalRusak,
            $nominalHilang,
            $bekukan
        ) {
            $tagihan = TagihanDenda::query()
                ->where('id_peminjaman', $peminjaman->id_peminjaman)
                ->lockForUpdate()
                ->first();

            if ($tagihan?->dibekukan_at) {
                return $tagihan;
            }

            if (! $tagihan && $nominal <= 0 && ! $bekukan) {
                return null;
            }

            $tagihan ??= new TagihanDenda([
                'id_peminjaman' => $peminjaman->id_peminjaman,
            ]);
            $tagihan->id_anggota = $peminjaman->id_anggota;
            $tagihan->nominal_tagihan = max(0, $nominal);
            $tagihan->nominal_terlambat = max(0, $nominalTerlambat);
            $tagihan->nominal_rusak = max(0, $nominalRusak);
            $tagihan->nominal_hilang = max(0, $nominalHilang);
            $tanggalOperasional = Carbon::now(self::BUSINESS_TIMEZONE)->toDateString();
            $tagihan->tgl_tagihan = $tagihan->tgl_tagihan ?? $tanggalOperasional;
            $tagihan->tgl_terakhir_diperbarui = $tanggalOperasional;
            $tagihan->keterangan = implode(' ', array_filter([
                $nominalTerlambat > 0 ? 'Denda keterlambatan: Rp' . number_format($nominalTerlambat, 0, ',', '.') . '.' : null,
                $nominalRusak > 0 ? 'Denda buku rusak: Rp50.000.' : null,
                $nominalHilang > 0 ? 'Denda buku hilang: Rp100.000.' : null,
                $nominal <= 0 ? 'Tidak ada denda.' : null,
            ]));

            if ($bekukan) {
                $tagihan->dibekukan_at = Carbon::now(self::BUSINESS_TIMEZONE);
            }

            $tagihan->save();
            $this->sinkronkanStatusTagihan($tagihan);

            return $tagihan->fresh();
        });
    }

    public function sisaTagihan(TagihanDenda $tagihan): float
    {
        return $this->sisaTagihanDalamSen($tagihan) / 100;
    }

    public function sisaTagihanDalamSen(TagihanDenda $tagihan): int
    {
        return max(
            0,
            $this->keSen($tagihan->nominal_tagihan)
                - $this->totalTerverifikasiDalamSen($tagihan)
        );
    }

    public function statusTagihan(TagihanDenda $tagihan): string
    {
        if ($this->keSen($tagihan->nominal_tagihan) === 0) {
            return TagihanDenda::STATUS_TIDAK_ADA;
        }

        if (
            $this->totalTerverifikasiDalamSen($tagihan) > 0
            && $this->sisaTagihanDalamSen($tagihan) === 0
        ) {
            return TagihanDenda::STATUS_LUNAS;
        }

        return TagihanDenda::STATUS_BELUM_LUNAS;
    }

    public function jumlahPembayaranDalamSen(string|int|float $jumlah): int
    {
        return $this->keSen($jumlah);
    }

    public function formatJumlahDalamSen(int $jumlah): string
    {
        return intdiv($jumlah, 100) . '.' . str_pad((string) abs($jumlah % 100), 2, '0', STR_PAD_LEFT);
    }

    public function statusDendaPengembalian(TagihanDenda $tagihan): string
    {
        if ($this->keSen($tagihan->nominal_tagihan) === 0) {
            return Pengembalian::DENDA_TIDAK_ADA;
        }

        return $this->sisaTagihanDalamSen($tagihan) === 0
            ? Pengembalian::DENDA_LUNAS
            : Pengembalian::DENDA_BELUM_DIBAYAR;
    }

    public function sinkronkanStatusTagihan(TagihanDenda $tagihan): void
    {
        $tagihan->status_tagihan = $this->statusTagihan($tagihan);
        $tagihan->save();

        $pengembalian = Pengembalian::query()
            ->where('id_peminjaman', $tagihan->id_peminjaman)
            ->where('status_pengembalian', '!=', Pengembalian::STATUS_DIBATALKAN)
            ->latest('id_pengembalian')
            ->first();

        if ($pengembalian) {
            $pengembalian->update([
                'denda' => $tagihan->nominal_tagihan,
                'status_denda' => $this->statusDendaPengembalian($tagihan),
            ]);
        }
    }

    public function bolehMeminjamBaru(int $idAnggota): bool
    {
        $tanggalHariIni = Carbon::now(self::BUSINESS_TIMEZONE)->toDateString();
        $peminjamanTerlambat = Peminjaman::query()
            ->where('id_anggota', $idAnggota)
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            ])
            ->whereNotNull('tgl_jatuh_tempo')
            ->whereDate('tgl_jatuh_tempo', '<', $tanggalHariIni)
            ->get();

        foreach ($peminjamanTerlambat as $peminjaman) {
            $this->syncTagihanForPeminjaman($peminjaman);
        }

        return ! TagihanDenda::query()
            ->where('id_anggota', $idAnggota)
            ->where('status_tagihan', TagihanDenda::STATUS_BELUM_LUNAS)
            ->exists();
    }

    public function buatNotifikasi(
        ?int $idAnggota = null,
        ?int $idPetugas = null,
        string $judul,
        string $isi,
        string $jenis = 'info',
        ?string $tautan = null
    ): ?Notifikasi {
        if (! $this->notifikasiTersedia()) {
            Log::warning('Notifikasi tidak disimpan karena tabel notifikasi belum tersedia.', [
                'id_anggota' => $idAnggota,
                'id_petugas' => $idPetugas,
                'judul' => $judul,
            ]);

            return null;
        }

        return Notifikasi::create([
            'id_anggota' => $idAnggota,
            'id_petugas' => $idPetugas,
            'judul' => $judul,
            'isi' => $isi,
            'jenis' => $jenis,
            'is_dibaca' => false,
            'tautan' => $tautan,
        ]);
    }

    public function notifikasiTersedia(): bool
    {
        $tersedia = Schema::hasTable('notifikasi');

        if (! $tersedia) {
            Log::warning('Fitur notifikasi tidak tersedia karena tabel notifikasi belum dibuat.');
        }

        return $tersedia;
    }

    private function nilaiPengaturan(string $kunci): string
    {
        if (! Schema::hasTable('pengaturan')) {
            throw new LogicException('Tabel pengaturan belum tersedia; perhitungan denda tidak dapat dilanjutkan.');
        }

        $nilai = DB::table('pengaturan')->where('kunci', $kunci)->value('nilai');

        if ($nilai === null || trim((string) $nilai) === '') {
            throw new LogicException("Pengaturan {$kunci} belum tersedia.");
        }

        return (string) $nilai;
    }

    private function tanggalOperasional(DateTimeInterface|string $tanggal): CarbonInterface
    {
        if ($tanggal instanceof DateTimeInterface) {
            return Carbon::instance($tanggal)
                ->setTimezone(self::BUSINESS_TIMEZONE)
                ->startOfDay();
        }

        return Carbon::parse($tanggal, self::BUSINESS_TIMEZONE)->startOfDay();
    }

    private function keSen(string|int|float|null $jumlah): int
    {
        if ($jumlah === null) {
            return 0;
        }

        $nilai = is_float($jumlah) ? number_format($jumlah, 2, '.', '') : (string) $jumlah;

        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $nilai, $bagian)) {
            throw new LogicException('Nilai nominal harus berupa angka dengan maksimal dua angka desimal.');
        }

        $sen = ((int) $bagian[2] * 100) + (int) str_pad($bagian[3] ?? '', 2, '0');

        return ($bagian[1] ?? '') === '-' ? -$sen : $sen;
    }

    private function totalTerverifikasiDalamSen(TagihanDenda $tagihan): int
    {
        $jumlah = $tagihan->pembayaran()
            ->where('status_pembayaran', 'diverifikasi')
            ->sum('jumlah_pembayaran');

        return $this->keSen($jumlah);
    }
}
