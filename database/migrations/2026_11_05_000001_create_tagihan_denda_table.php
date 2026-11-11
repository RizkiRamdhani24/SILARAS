<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_denda', function (Blueprint $table) {
            $table->id('id_tagihan_denda');
            $table->foreignId('id_peminjaman')->unique()->constrained('peminjaman', 'id_peminjaman')->restrictOnDelete();
            $table->foreignId('id_anggota')->constrained('anggota', 'id_anggota')->restrictOnDelete();
            $table->decimal('nominal_tagihan', 15, 2);
            $table->date('tgl_tagihan');
            $table->date('tgl_terakhir_diperbarui')->nullable();
            $table->string('status_tagihan', 20)->default('belum_lunas');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(
                ['id_tagihan_denda', 'id_anggota'],
                'tagihan_denda_id_anggota_unique'
            );
            $table->index(
                ['id_anggota', 'status_tagihan'],
                'tagihan_denda_anggota_status_index'
            );
        });

        DB::statement("ALTER TABLE tagihan_denda ADD CONSTRAINT chk_tagihan_denda_status_tagihan CHECK (status_tagihan IN ('belum_lunas', 'lunas'))");
        DB::statement('ALTER TABLE tagihan_denda ADD CONSTRAINT chk_tagihan_denda_nominal_nonnegative CHECK (nominal_tagihan >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE tagihan_denda DROP CONSTRAINT IF EXISTS chk_tagihan_denda_status_tagihan');
        DB::statement('ALTER TABLE tagihan_denda DROP CONSTRAINT IF EXISTS chk_tagihan_denda_nominal_nonnegative');
        Schema::dropIfExists('tagihan_denda');
    }
};
