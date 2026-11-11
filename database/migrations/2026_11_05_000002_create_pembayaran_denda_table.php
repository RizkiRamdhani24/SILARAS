<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_denda', function (Blueprint $table) {
            $table->id('id_pembayaran_denda');
            $table->unsignedBigInteger('id_tagihan_denda');
            $table->foreignId('id_anggota')->constrained('anggota', 'id_anggota')->restrictOnDelete();
            $table->foreignId('id_petugas_verifikasi')->nullable()->constrained('petugas', 'id_petugas')->nullOnDelete();
            $table->string('metode_pembayaran', 20);
            $table->decimal('jumlah_pembayaran', 15, 2);
            $table->string('bukti_pembayaran')->nullable();
            $table->string('status_pembayaran', 30)->default('menunggu_verifikasi');
            $table->text('catatan_petugas')->nullable();
            $table->dateTime('waktu_pembayaran')->nullable();
            $table->dateTime('waktu_verifikasi')->nullable();
            $table->timestamps();

            $table->foreign(
                ['id_tagihan_denda', 'id_anggota'],
                'pembayaran_denda_tagihan_anggota_foreign'
            )->references(
                ['id_tagihan_denda', 'id_anggota']
            )->on('tagihan_denda')->restrictOnDelete();
            $table->index(
                ['id_tagihan_denda', 'status_pembayaran'],
                'pembayaran_denda_tagihan_status_index'
            );
            $table->index(
                ['id_anggota', 'status_pembayaran'],
                'pembayaran_denda_anggota_status_index'
            );
            $table->index(
                ['status_pembayaran', 'waktu_pembayaran'],
                'pembayaran_denda_status_waktu_index'
            );
        });

        DB::statement("ALTER TABLE pembayaran_denda ADD CONSTRAINT chk_pembayaran_denda_metode_pembayaran CHECK (metode_pembayaran IN ('qris', 'tunai'))");
        DB::statement("ALTER TABLE pembayaran_denda ADD CONSTRAINT chk_pembayaran_denda_status_pembayaran CHECK (status_pembayaran IN ('menunggu_verifikasi', 'diverifikasi', 'ditolak'))");
        DB::statement('ALTER TABLE pembayaran_denda ADD CONSTRAINT chk_pembayaran_denda_jumlah_positive CHECK (jumlah_pembayaran > 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE pembayaran_denda DROP CONSTRAINT IF EXISTS chk_pembayaran_denda_metode_pembayaran');
        DB::statement('ALTER TABLE pembayaran_denda DROP CONSTRAINT IF EXISTS chk_pembayaran_denda_status_pembayaran');
        DB::statement('ALTER TABLE pembayaran_denda DROP CONSTRAINT IF EXISTS chk_pembayaran_denda_jumlah_positive');
        Schema::dropIfExists('pembayaran_denda');
    }
};
