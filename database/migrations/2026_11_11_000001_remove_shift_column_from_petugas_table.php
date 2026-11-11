<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('petugas', 'shift')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->dropColumn('shift');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('petugas') && ! Schema::hasColumn('petugas', 'shift')) {
            Schema::table('petugas', function (Blueprint $table) {
                $table->string('shift')->nullable();
            });
        }
    }
};
