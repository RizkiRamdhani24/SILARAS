<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengembalian', function (Blueprint $table) {
            $table->id('id_pengembalian');
            $table->foreignId('id_peminjaman')->unique()->constrained('peminjaman', 'id_peminjaman')->restrictOnDelete();
            $table->foreignId('id_petugas')->constrained('petugas', 'id_petugas')->restrictOnDelete();
            $table->date('tgl_kembali');
            $table->decimal('denda', 10, 2)->default(0);
            $table->timestamps();
        });

        foreach (['anggota', 'petugas', 'buku', 'peminjaman', 'pengembalian'] as $t) {
            DB::statement("ALTER TABLE {$t} ENABLE ROW LEVEL SECURITY");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengembalian');
    }
};
