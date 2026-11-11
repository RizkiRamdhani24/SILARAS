<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->foreignId('id_anggota')->nullable()->constrained('anggota', 'id_anggota')->cascadeOnDelete();
            $table->foreignId('id_petugas')->nullable()->constrained('petugas', 'id_petugas')->cascadeOnDelete();
            $table->string('judul', 150);
            $table->text('isi');
            $table->string('jenis', 30)->default('info');
            $table->boolean('is_dibaca')->default(false);
            $table->string('tautan')->nullable();
            $table->timestamps();

            $table->index(['id_anggota', 'created_at'], 'notifikasi_anggota_created_index');
            $table->index(['id_petugas', 'created_at'], 'notifikasi_petugas_created_index');
        });

        DB::statement("ALTER TABLE notifikasi ADD CONSTRAINT chk_notifikasi_satu_penerima CHECK ((id_anggota IS NOT NULL AND id_petugas IS NULL) OR (id_anggota IS NULL AND id_petugas IS NOT NULL))");
        DB::statement("ALTER TABLE notifikasi ADD CONSTRAINT chk_notifikasi_jenis CHECK (jenis IN ('peminjaman', 'pengembalian', 'denda', 'sistem', 'info'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE notifikasi DROP CONSTRAINT IF EXISTS chk_notifikasi_satu_penerima');
        DB::statement('ALTER TABLE notifikasi DROP CONSTRAINT IF EXISTS chk_notifikasi_jenis');
        Schema::dropIfExists('notifikasi');
    }
};
