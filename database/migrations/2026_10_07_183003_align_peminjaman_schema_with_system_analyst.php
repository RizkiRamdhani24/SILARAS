<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peminjaman')
            ->where('status', 'menunggu')
            ->update(['status' => 'menunggu validasi']);

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->renameColumn('status', 'status_peminjaman');
        });

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->date('tgl_jatuh_tempo')->nullable()->change();
            $table->string('status_peminjaman', 30)->default('menunggu validasi')->nullable(false)->change();
        });

        DB::statement("ALTER TABLE peminjaman ADD CONSTRAINT chk_peminjaman_status_peminjaman CHECK (status_peminjaman IN ('menunggu validasi', 'dipinjam', 'terlambat', 'pengembalian diajukan', 'dikembalikan', 'ditolak'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE peminjaman DROP CONSTRAINT IF EXISTS chk_peminjaman_status_peminjaman');

        DB::table('peminjaman')
            ->where('status_peminjaman', 'menunggu validasi')
            ->update(['status_peminjaman' => 'menunggu']);

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->string('status_peminjaman', 20)->default('menunggu')->nullable(false)->change();
            $table->date('tgl_jatuh_tempo')->nullable(false)->change();
        });

        Schema::table('peminjaman', function (Blueprint $table) {
            $table->renameColumn('status_peminjaman', 'status');
        });
    }
};
