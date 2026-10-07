<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            $table->renameColumn('tgl_kembali', 'tgl_pengembalian');
        });

        Schema::table('pengembalian', function (Blueprint $table) {
            $table->unsignedBigInteger('id_petugas')->nullable()->change();
            $table->date('tgl_pengembalian')->nullable()->change();
            $table->decimal('denda', 10, 2)->default(0)->nullable(false)->change();
            $table->string('kondisi_buku', 50)->nullable();
            $table->string('status_pengembalian', 20)->default('menunggu validasi')->nullable(false);
            $table->string('status_denda', 20)->default('tidak ada')->nullable(false);
        });

        DB::statement("ALTER TABLE pengembalian ADD CONSTRAINT chk_pengembalian_kondisi_buku CHECK (kondisi_buku IS NULL OR kondisi_buku IN ('baik', 'rusak', 'hilang'))");
        DB::statement("ALTER TABLE pengembalian ADD CONSTRAINT chk_pengembalian_status_pengembalian CHECK (status_pengembalian IN ('menunggu validasi', 'selesai'))");
        DB::statement("ALTER TABLE pengembalian ADD CONSTRAINT chk_pengembalian_status_denda CHECK (status_denda IN ('tidak ada', 'belum dibayar', 'lunas'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pengembalian DROP CONSTRAINT IF EXISTS chk_pengembalian_kondisi_buku');
        DB::statement('ALTER TABLE pengembalian DROP CONSTRAINT IF EXISTS chk_pengembalian_status_pengembalian');
        DB::statement('ALTER TABLE pengembalian DROP CONSTRAINT IF EXISTS chk_pengembalian_status_denda');

        Schema::table('pengembalian', function (Blueprint $table) {
            $table->dropColumn(['kondisi_buku', 'status_pengembalian', 'status_denda']);
            $table->unsignedBigInteger('id_petugas')->nullable(false)->change();
            $table->date('tgl_pengembalian')->nullable(false)->change();
        });

        Schema::table('pengembalian', function (Blueprint $table) {
            $table->renameColumn('tgl_pengembalian', 'tgl_kembali');
        });
    }
};
