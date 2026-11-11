<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\PembayaranDenda;
use App\Models\Peminjaman;
use App\Models\Pengembalian;
use App\Models\Petugas;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'jenis' => ['nullable', Rule::in(['peminjaman', 'pengembalian', 'denda'])],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([
                Peminjaman::STATUS_MENUNGGU_VALIDASI,
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                Peminjaman::STATUS_DIKEMBALIKAN,
                Peminjaman::STATUS_DITOLAK,
                Peminjaman::STATUS_DIBATALKAN,
                Peminjaman::STATUS_HILANG,
                Pengembalian::STATUS_MENUNGGU_VALIDASI,
                Pengembalian::STATUS_SELESAI,
                Pengembalian::STATUS_DIBATALKAN,
                'menunggu_verifikasi',
                'diverifikasi',
                'ditolak',
            ])],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $type = $validated['jenis'] ?? '';
        $search = trim($validated['q'] ?? '');
        $status = $validated['status'] ?? '';
        $from = $validated['dari'] ?? null;
        $until = $validated['sampai'] ?? null;

        $queries = [];

        if ($type === '' || $type === 'peminjaman') {
            $loans = DB::table('peminjaman as p')
                ->join('anggota as a', 'a.id_anggota', '=', 'p.id_anggota')
                ->join('buku as b', 'b.id_buku', '=', 'p.id_buku')
                ->leftJoin('petugas as st', 'st.id_petugas', '=', 'p.id_petugas')
                ->selectRaw(
                    "'peminjaman' as category, CAST(p.id_peminjaman AS VARCHAR) as source_id, " .
                    "CAST(p.id_peminjaman AS VARCHAR) as reference_id, p.created_at as occurred_at, " .
                    "a.nama_anggota as member_name, CAST(a.id_anggota AS VARCHAR) as member_id, " .
                    "b.judul_buku as book_title, CAST(b.id_buku AS VARCHAR) as book_id, " .
                    "COALESCE(st.nama_petugas, '-') as staff_name, " .
                    "COALESCE(CAST(st.id_petugas AS VARCHAR), '-') as staff_id, " .
                    "CAST(0 AS DECIMAL(15, 2)) as amount, p.status_peminjaman as status, " .
                    "CAST(p.tgl_jatuh_tempo AS VARCHAR) as details"
                );

            if ($search !== '') {
                $loans->where(function (Builder $query) use ($search) {
                    $query->where('a.nama_anggota', 'like', "%{$search}%")
                        ->orWhere('b.judul_buku', 'like', "%{$search}%")
                        ->orWhereRaw('CAST(p.id_peminjaman AS VARCHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(a.id_anggota AS VARCHAR) LIKE ?', ["%{$search}%"]);
                });
            }

            $this->applyFilters($loans, 'p', $status, $from, $until);
            $queries[] = $loans;
        }

        if ($type === '' || $type === 'pengembalian') {
            $returns = DB::table('pengembalian as r')
                ->join('peminjaman as p', 'p.id_peminjaman', '=', 'r.id_peminjaman')
                ->join('anggota as a', 'a.id_anggota', '=', 'p.id_anggota')
                ->join('buku as b', 'b.id_buku', '=', 'p.id_buku')
                ->leftJoin('petugas as st', 'st.id_petugas', '=', 'r.id_petugas')
                ->selectRaw(
                    "'pengembalian' as category, CAST(r.id_pengembalian AS VARCHAR) as source_id, " .
                    "CAST(p.id_peminjaman AS VARCHAR) as reference_id, r.created_at as occurred_at, " .
                    "a.nama_anggota as member_name, CAST(a.id_anggota AS VARCHAR) as member_id, " .
                    "b.judul_buku as book_title, CAST(b.id_buku AS VARCHAR) as book_id, " .
                    "COALESCE(st.nama_petugas, '-') as staff_name, " .
                    "COALESCE(CAST(st.id_petugas AS VARCHAR), '-') as staff_id, " .
                    "r.denda as amount, r.status_pengembalian as status, " .
                    "COALESCE(r.kondisi_buku, '-') as details"
                );

            if ($search !== '') {
                $returns->where(function (Builder $query) use ($search) {
                    $query->where('a.nama_anggota', 'like', "%{$search}%")
                        ->orWhere('b.judul_buku', 'like', "%{$search}%")
                        ->orWhereRaw('CAST(r.id_pengembalian AS VARCHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(p.id_peminjaman AS VARCHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(a.id_anggota AS VARCHAR) LIKE ?', ["%{$search}%"]);
                });
            }

            $this->applyFilters($returns, 'r', $status, $from, $until);
            $queries[] = $returns;
        }

        if ($type === '' || $type === 'denda') {
            $payments = DB::table('pembayaran_denda as pd')
                ->join('tagihan_denda as td', 'td.id_tagihan_denda', '=', 'pd.id_tagihan_denda')
                ->join('peminjaman as p', 'p.id_peminjaman', '=', 'td.id_peminjaman')
                ->join('anggota as a', 'a.id_anggota', '=', 'pd.id_anggota')
                ->join('buku as b', 'b.id_buku', '=', 'p.id_buku')
                ->leftJoin('petugas as st', 'st.id_petugas', '=', 'pd.id_petugas_verifikasi')
                ->selectRaw(
                    "'denda' as category, CAST(pd.id_pembayaran_denda AS VARCHAR) as source_id, " .
                    "CAST(td.id_tagihan_denda AS VARCHAR) as reference_id, pd.created_at as occurred_at, " .
                    "a.nama_anggota as member_name, CAST(a.id_anggota AS VARCHAR) as member_id, " .
                    "b.judul_buku as book_title, CAST(b.id_buku AS VARCHAR) as book_id, " .
                    "COALESCE(st.nama_petugas, '-') as staff_name, " .
                    "COALESCE(CAST(st.id_petugas AS VARCHAR), '-') as staff_id, " .
                    "pd.jumlah_pembayaran as amount, pd.status_pembayaran as status, " .
                    "pd.metode_pembayaran as details"
                );

            if ($search !== '') {
                $payments->where(function (Builder $query) use ($search) {
                    $query->where('a.nama_anggota', 'like', "%{$search}%")
                        ->orWhere('b.judul_buku', 'like', "%{$search}%")
                        ->orWhereRaw('CAST(pd.id_pembayaran_denda AS VARCHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(td.id_tagihan_denda AS VARCHAR) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(a.id_anggota AS VARCHAR) LIKE ?', ["%{$search}%"]);
                });
            }

            $this->applyFilters($payments, 'pd', $status, $from, $until);
            $queries[] = $payments;
        }

        $combined = array_shift($queries);
        foreach ($queries as $query) {
            $combined->unionAll($query);
        }

        $transactions = DB::query()
            ->fromSub($combined, 'transactions')
            ->orderByDesc('occurred_at')
            ->orderByDesc('category')
            ->orderByDesc('source_id')
            ->paginate(15)
            ->withQueryString();

        $loanCount = DB::table('peminjaman')->count();
        $returnCount = DB::table('pengembalian')->count();
        $paymentCount = DB::table('pembayaran_denda')->count();
        $completedReturns = DB::table('pengembalian')
            ->where('status_pengembalian', Pengembalian::STATUS_SELESAI)
            ->count();
        $verifiedPayments = DB::table('pembayaran_denda')
            ->where('status_pembayaran', 'diverifikasi');
        $paidAmount = (float) (clone $verifiedPayments)->sum('jumlah_pembayaran');
        $verifiedPaymentCount = (clone $verifiedPayments)->count();

        $today = now('Asia/Jakarta')->toDateString();
        $petugasHariIni = Petugas::query()
            ->withCount([
                'peminjaman as peminjaman_hari_ini' => fn ($query) => $query->whereDate('created_at', $today),
                'pengembalian as pengembalian_hari_ini' => fn ($query) => $query->whereDate('created_at', $today),
                'pembayaranDenda as denda_hari_ini' => fn ($query) => $query
                    ->where('status_pembayaran', 'diverifikasi')
                    ->whereDate('waktu_verifikasi', $today),
            ])
            ->orderBy('nama_petugas')
            ->get();

        return view('petugas.transaksi.index', [
            'transactions' => $transactions,
            'filters' => [
                'jenis' => $type,
                'q' => $search,
                'status' => $status,
                'dari' => $from,
                'sampai' => $until,
            ],
            'totalTransactions' => $loanCount + $returnCount + $paymentCount,
            'loanCount' => $loanCount,
            'returnCount' => $returnCount,
            'paymentCount' => $paymentCount,
            'completedReturns' => $completedReturns,
            'paidAmount' => $paidAmount,
            'verifiedPaymentCount' => $verifiedPaymentCount,
            'petugasHariIni' => $petugasHariIni,
        ]);
    }

    private function applyFilters(Builder $query, string $table, string $status, ?string $from, ?string $until): void
    {
        if ($status !== '') {
            $statusColumn = match ($table) {
                'p' => 'p.status_peminjaman',
                'r' => 'r.status_pengembalian',
                default => 'pd.status_pembayaran',
            };

            $query->where($statusColumn, $status);
        }

        if ($from !== null) {
            $query->whereDate("{$table}.created_at", '>=', $from);
        }

        if ($until !== null) {
            $query->whereDate("{$table}.created_at", '<=', $until);
        }
    }
}
