<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            $table->string('judul_buku', 150)->nullable(false)->change();
            $table->string('penerbit', 100)->nullable()->change();
            $table->string('pengarang', 100)->nullable(false)->change();
            $table->integer('stock')->default(0)->nullable(false)->change();
            $table->string('kategori', 50)->nullable()->change();
        });

        DB::statement('ALTER TABLE buku ADD CONSTRAINT chk_buku_stock CHECK (stock >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE buku DROP CONSTRAINT IF EXISTS chk_buku_stock');

        Schema::table('buku', function (Blueprint $table) {
            $table->string('judul_buku', 255)->nullable(false)->change();
            $table->string('penerbit', 255)->nullable()->change();
            $table->string('pengarang', 255)->nullable(false)->change();
            $table->integer('stock')->default(0)->nullable(false)->change();
            $table->string('kategori', 100)->nullable()->change();
        });
    }
};
