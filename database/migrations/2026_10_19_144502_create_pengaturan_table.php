<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->string('nilai');
            $table->string('keterangan')->nullable();
            $table->timestamp('created_at', 0)->nullable()->useCurrent();
            $table->timestamp('updated_at', 0)->nullable()->useCurrent();
        });

        DB::table('pengaturan')->insert([
            [
                'kunci' => 'lama_peminjaman_hari',
                'nilai' => '7',
                'keterangan' => 'Lama peminjaman dalam hari (contoh, silakan ganti)',
            ],
            [
                'kunci' => 'tarif_denda_per_hari',
                'nilai' => '1000',
                'keterangan' => 'Tarif denda per hari dalam Rupiah (contoh, silakan ganti)',
            ],
        ]);

        DB::statement('ALTER TABLE public.pengaturan ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        throw new \LogicException('This migration cannot be rolled back without dropping the table or disabling RLS.');
    }
};
