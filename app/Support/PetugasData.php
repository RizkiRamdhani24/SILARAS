<?php

namespace App\Support;

use App\Models\Anggota;
use App\Models\Buku;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Petugas;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/**
 * Pembantu data untuk halaman petugas (frontend).
 *
 * Hanya MEMBACA data dari tabel yang sudah ada (anggota, petugas, buku, peminjaman,
 * pengembalian, pengaturan). Perubahan data tetap lewat controller milik Exel/Herlyan.
 */
class PetugasData
{
    /** Nilai cadangan bila tabel pengaturan belum ada / kuncinya kosong. */
    public const TARIF_DENDA_DEFAULT = 2000;
    public const LAMA_PINJAM_DEFAULT = 7;

    /** Status peminjaman yang dihitung sebagai "masih berjalan". */
    public const STATUS_BERJALAN = [
        Peminjaman::STATUS_MENUNGGU_VALIDASI,
        Peminjaman::STATUS_DIPINJAM,
        Peminjaman::STATUS_TERLAMBAT,
        Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
    ];

    // ------------------------------------------------------------------
    // Pengaturan
    // ------------------------------------------------------------------

    public static function pengaturan(string $kunci, int $default): int
    {
        try {
            if (! Schema::hasTable('pengaturan')) {
                return $default;
            }
            $nilai = DB::table('pengaturan')->where('kunci', $kunci)->value('nilai');

            return is_numeric($nilai) ? (int) $nilai : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function tarifDenda(): int
    {
        return self::pengaturan('tarif_denda_per_hari', self::TARIF_DENDA_DEFAULT);
    }

    public static function lamaPinjam(): int
    {
        return self::pengaturan('lama_peminjaman_hari', self::LAMA_PINJAM_DEFAULT);
    }

    // ------------------------------------------------------------------
    // Format & hitungan kecil
    // ------------------------------------------------------------------

    /** [nama, shift] petugas yang sedang login. Shift boleh null (kolom shift nullable). */
    public static function identitasPetugas(): array
    {
        $petugas = auth('petugas')->user();

        return [
            $petugas->nama_petugas ?? 'Petugas',
            filled($petugas?->shift) ? ucfirst(strtolower($petugas->shift)) : null,
        ];
    }

    public static function rupiah($angka): string
    {
        return 'Rp '.number_format((float) $angka, 0, ',', '.');
    }

    public static function tanggal($waktu, string $format = 'd M Y'): string
    {
        return $waktu ? Carbon::parse($waktu)->translatedFormat($format) : '-';
    }

    /** Kode tampilan, mis. PMJ-0012. Bukan kolom database; dibentuk dari id. */
    public static function kode(string $awalan, $id): string
    {
        return $awalan.'-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public static function inisial(?string $nama): string
    {
        $kata = array_filter(explode(' ', trim((string) $nama)));

        return collect($kata)->take(2)->map(fn ($k) => mb_strtoupper(mb_substr($k, 0, 1)))->implode('') ?: '?';
    }

    /** Hari terlambat: selisih jatuh tempo ke tanggal acuan (default hari ini). 0 bila belum lewat. */
    public static function hariTerlambat($jatuhTempo, $acuan = null): int
    {
        if (! $jatuhTempo) {
            return 0;
        }
        $tempo = Carbon::parse($jatuhTempo)->startOfDay();
        $tanggal = $acuan ? Carbon::parse($acuan)->startOfDay() : now()->startOfDay();

        return $tanggal->greaterThan($tempo) ? (int) abs($tempo->diffInDays($tanggal)) : 0;
    }

    public static function estimasiDenda($jatuhTempo, $acuan = null): int
    {
        return self::hariTerlambat($jatuhTempo, $acuan) * self::tarifDenda();
    }

    /** [label, kelas badge] untuk status peminjaman. */
    public static function statusPeminjaman(?string $status): array
    {
        return match ($status) {
            Peminjaman::STATUS_MENUNGGU_VALIDASI => ['Menunggu validasi', 'bg-amber-100 text-amber-800'],
            Peminjaman::STATUS_DIPINJAM => ['Dipinjam', 'bg-blue-100 text-blue-700'],
            Peminjaman::STATUS_TERLAMBAT => ['Terlambat', 'bg-red-100 text-red-700'],
            Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN => ['Pengembalian diajukan', 'bg-violet-100 text-violet-700'],
            Peminjaman::STATUS_DIKEMBALIKAN => ['Dikembalikan', 'bg-emerald-100 text-emerald-700'],
            Peminjaman::STATUS_DITOLAK => ['Ditolak', 'bg-slate-200 text-slate-700'],
            default => [ucfirst((string) $status) ?: '-', 'bg-slate-100 text-slate-600'],
        };
    }

    // ------------------------------------------------------------------
    // Rute
    // ------------------------------------------------------------------

    /** Apakah rute bernama ini sudah terdaftar (mis. milik Exel yang belum digabung)? */
    public static function bisa(string $namaRute): bool
    {
        return Route::has($namaRute);
    }

    /**
     * Alamat halaman petugas. Memilih rute modul (Exel/Herlyan) bila ada, lalu rute di web.php,
     * dan jatuh ke URL /petugas/{halaman}. Saat dibuka lewat rute pratinjau, tetap di pratinjau.
     */
    public static function link(string $halaman): string
    {
        if (request()->routeIs('petugas.preview.*')) {
            $calon = ['petugas.preview.'.$halaman];
        } else {
            $calon = match ($halaman) {
                'buku' => ['petugas.kelola-buku.index', 'petugas.buku'],
                'peminjaman' => ['petugas.peminjaman.index', 'petugas.peminjaman'],
                'pengembalian' => ['petugas.pengembalian.index', 'petugas.pengembalian'],
                default => ['petugas.'.$halaman],
            };
        }

        foreach ($calon as $nama) {
            if (Route::has($nama)) {
                return route($nama);
            }
        }

        return url('/petugas/'.$halaman);
    }

    // ------------------------------------------------------------------
    // Ringkasan umum
    // ------------------------------------------------------------------

    /** Dipakai di setiap halaman (lencana sidebar), jadi tidak boleh membuat halaman error. */
    public static function antreanPeminjaman(): int
    {
        try {
            return Peminjaman::where('status_peminjaman', Peminjaman::STATUS_MENUNGGU_VALIDASI)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    /** Peminjaman yang masih di tangan anggota (dipinjam atau terlambat). */
    public static function sedangDipinjam(): int
    {
        return Peminjaman::whereIn('status_peminjaman', [Peminjaman::STATUS_DIPINJAM, Peminjaman::STATUS_TERLAMBAT])->count();
    }

    /** Total denda berstatus "belum dibayar" per id_anggota. @return array<int, float> */
    public static function tunggakanPerAnggota(): array
    {
        return Pengembalian::query()
            ->join('peminjaman', 'peminjaman.id_peminjaman', '=', 'pengembalian.id_peminjaman')
            ->where('pengembalian.status_denda', Pengembalian::DENDA_BELUM_DIBAYAR)
            ->groupBy('peminjaman.id_anggota')
            ->selectRaw('peminjaman.id_anggota as id_anggota, SUM(pengembalian.denda) as total')
            ->pluck('total', 'id_anggota')
            ->map(fn ($n) => (float) $n)
            ->all();
    }

    /** Peminjaman & pengembalian per hari untuk $hari hari terakhir (termasuk hari ini). */
    public static function tren(int $hari = 7): Collection
    {
        $mulai = now()->subDays($hari - 1)->startOfDay();

        $pinjam = Peminjaman::where('tgl_peminjaman', '>=', $mulai->toDateString())->pluck('tgl_peminjaman')
            ->countBy(fn ($t) => Carbon::parse($t)->toDateString());
        $kembali = Pengembalian::where('tgl_pengembalian', '>=', $mulai->toDateString())->pluck('tgl_pengembalian')
            ->countBy(fn ($t) => Carbon::parse($t)->toDateString());

        return collect(range(0, $hari - 1))->map(function ($i) use ($mulai, $pinjam, $kembali, $hari) {
            $tanggal = $mulai->copy()->addDays($i);
            $kunci = $tanggal->toDateString();

            return [
                'label' => $i === $hari - 1 ? 'Hari ini' : $tanggal->translatedFormat('D'),
                'pinjam' => (int) ($pinjam[$kunci] ?? 0),
                'kembali' => (int) ($kembali[$kunci] ?? 0),
                'hariIni' => $i === $hari - 1,
            ];
        });
    }

    /**
     * Aktivitas terbaru dari tabel peminjaman & pengembalian (tidak ada tabel log khusus).
     *
     * @return Collection<int, array{waktu: \Illuminate\Support\Carbon, isi: string, oleh: ?string, meta: string}>
     */
    public static function aktivitas(int $batas = 5): Collection
    {
        $pinjam = Peminjaman::with(['anggota', 'buku', 'petugas'])->latest('updated_at')->limit($batas)->get()
            ->map(function (Peminjaman $p) {
                $anggota = $p->anggota?->nama_anggota ?? 'Anggota';
                $buku = $p->buku?->judul_buku ?? 'buku';
                $isi = match ($p->status_peminjaman) {
                    Peminjaman::STATUS_MENUNGGU_VALIDASI => "{$anggota} mengajukan peminjaman \"{$buku}\".",
                    Peminjaman::STATUS_DITOLAK => "Pengajuan {$anggota} untuk \"{$buku}\" ditolak.",
                    Peminjaman::STATUS_DIKEMBALIKAN => "\"{$buku}\" dikembalikan oleh {$anggota}.",
                    default => "{$anggota} meminjam \"{$buku}\" ({$p->status_peminjaman}).",
                };

                return ['waktu' => $p->updated_at ?? now(), 'isi' => $isi, 'oleh' => $p->petugas?->nama_petugas, 'meta' => self::kode('PMJ', $p->id_peminjaman)];
            });

        $kembali = Pengembalian::with(['peminjaman.anggota', 'peminjaman.buku', 'petugas'])->latest('updated_at')->limit($batas)->get()
            ->map(function (Pengembalian $k) {
                $anggota = $k->peminjaman?->anggota?->nama_anggota ?? 'Anggota';
                $buku = $k->peminjaman?->buku?->judul_buku ?? 'buku';
                $isi = $k->status_pengembalian === Pengembalian::STATUS_SELESAI
                    ? "Pengembalian \"{$buku}\" oleh {$anggota} divalidasi".((float) $k->denda > 0 ? ', denda '.self::rupiah($k->denda).'.' : '.')
                    : "{$anggota} mengajukan pengembalian \"{$buku}\".";

                return ['waktu' => $k->updated_at ?? now(), 'isi' => $isi, 'oleh' => $k->petugas?->nama_petugas, 'meta' => self::kode('KMB', $k->id_pengembalian)];
            });

        return $pinjam->concat($kembali)->sortByDesc('waktu')->take($batas)->values();
    }

    /** Petugas dikelompokkan per shift (kolom shift boleh kosong). */
    public static function petugasPerShift(): Collection
    {
        return Petugas::orderBy('nama_petugas')->get()
            ->groupBy(fn (Petugas $p) => $p->shift ? ucfirst(strtolower($p->shift)) : 'Belum diatur');
    }

    /** Ringkasan kategori buku: [kategori => jumlah judul]. */
    public static function kategoriBuku(): Collection
    {
        return Buku::query()
            ->selectRaw("COALESCE(NULLIF(kategori, ''), 'Tanpa kategori') as kategori, COUNT(*) as total")
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->pluck('total', 'kategori');
    }

    public static function anggotaBaruBulanIni(): int
    {
        return Anggota::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
    }
}
