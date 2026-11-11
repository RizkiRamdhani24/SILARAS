<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table) {
            $table->unsignedSmallInteger('durasi_peminjaman_hari')->nullable();
            $table->text('alasan_penolakan')->nullable();
        });

        Schema::table('buku', function (Blueprint $table) {
            $table->boolean('diarsipkan')->default(false);
        });

        Schema::table('tagihan_denda', function (Blueprint $table) {
            $table->decimal('nominal_terlambat', 15, 2)->default(0);
            $table->decimal('nominal_rusak', 15, 2)->default(0);
            $table->decimal('nominal_hilang', 15, 2)->default(0);
            $table->timestamp('dibekukan_at')->nullable();
        });

        DB::statement('ALTER TABLE peminjaman DROP CONSTRAINT IF EXISTS chk_peminjaman_status_peminjaman');
        DB::statement("ALTER TABLE peminjaman ADD CONSTRAINT chk_peminjaman_status_peminjaman CHECK (status_peminjaman IN ('menunggu validasi', 'dipinjam', 'terlambat', 'pengembalian diajukan', 'dikembalikan', 'ditolak', 'dibatalkan', 'hilang'))");
        DB::statement('ALTER TABLE pengembalian DROP CONSTRAINT IF EXISTS chk_pengembalian_status_pengembalian');
        DB::statement("ALTER TABLE pengembalian ADD CONSTRAINT chk_pengembalian_status_pengembalian CHECK (status_pengembalian IN ('menunggu validasi', 'selesai', 'dibatalkan'))");
        DB::statement('ALTER TABLE tagihan_denda DROP CONSTRAINT IF EXISTS chk_tagihan_denda_status_tagihan');
        DB::statement("ALTER TABLE tagihan_denda ADD CONSTRAINT chk_tagihan_denda_status_tagihan CHECK (status_tagihan IN ('belum_lunas', 'lunas', 'tidak ada'))");

        DB::statement('ALTER TABLE public.pengembalian DROP CONSTRAINT IF EXISTS pengembalian_id_peminjaman_unique');
        DB::statement(
            "CREATE UNIQUE INDEX pengembalian_satu_aktif_per_peminjaman " .
            "ON public.pengembalian (id_peminjaman) " .
            "WHERE status_pengembalian <> 'dibatalkan'"
        );

        $duplicatePending = DB::table('pembayaran_denda')
            ->select('id_tagihan_denda')
            ->where('status_pembayaran', 'menunggu_verifikasi')
            ->groupBy('id_tagihan_denda')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicatePending) {
            throw new RuntimeException(
                'Constraint pembayaran pending tidak dapat dibuat: ada beberapa pembayaran pending untuk tagihan #' .
                $duplicatePending->id_tagihan_denda . '. Selesaikan duplikasi secara manual sebelum migrasi.'
            );
        }

        DB::statement(
            "CREATE UNIQUE INDEX pembayaran_denda_satu_pending_per_tagihan " .
            "ON pembayaran_denda (id_tagihan_denda) " .
            "WHERE status_pembayaran = 'menunggu_verifikasi'"
        );

        foreach ([
            'buku',
            'peminjaman',
            'pengembalian',
            'pengaturan',
            'tagihan_denda',
            'pembayaran_denda',
            'notifikasi',
            'migrations',
            'personal_access_tokens',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::statement("ALTER TABLE public.{$table} ENABLE ROW LEVEL SECURITY");
            DB::statement(
                "CREATE POLICY laravel_connection_only ON public.{$table} " .
                "FOR ALL TO PUBLIC USING (false) WITH CHECK (false)"
            );
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'Business-rule schema and security changes are intentionally not reversible to preserve transaction history and uploaded data.'
        );
    }
};
