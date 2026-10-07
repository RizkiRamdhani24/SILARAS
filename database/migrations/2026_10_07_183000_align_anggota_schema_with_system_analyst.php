<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            $table->renameColumn('email', 'email_anggota');
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->string('nama_anggota', 100)->nullable(false)->change();
            $table->string('email_anggota', 100)->nullable(false)->change();
            $table->string('no_telp', 15)->nullable()->change();
            $table->string('status_anggota', 20)->default('aktif')->nullable(false);
        });

        DB::statement("ALTER TABLE anggota ADD CONSTRAINT chk_anggota_status_anggota CHECK (status_anggota IN ('aktif', 'nonaktif'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE anggota DROP CONSTRAINT IF EXISTS chk_anggota_status_anggota');

        Schema::table('anggota', function (Blueprint $table) {
            $table->dropColumn('status_anggota');
            $table->string('nama_anggota', 255)->nullable(false)->change();
            $table->string('email_anggota', 255)->nullable(false)->change();
            $table->string('no_telp', 20)->nullable()->change();
        });

        Schema::table('anggota', function (Blueprint $table) {
            $table->renameColumn('email_anggota', 'email');
        });
    }
};
