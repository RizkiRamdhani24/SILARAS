<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('buku', 'pdf')) {
            Schema::table('buku', function (Blueprint $table) {
                $table->string('pdf')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus kolom PDF agar data PDF
        // yang sudah ada tidak hilang saat rollback.
    }
};