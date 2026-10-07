<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->renameColumn('email', 'email_petugas');
        });

        Schema::table('petugas', function (Blueprint $table) {
            $table->string('nama_petugas', 100)->nullable(false)->change();
            $table->string('email_petugas', 100)->nullable(false)->change();
            $table->string('shift', 20)->nullable(false)->change();
            $table->string('password', 255)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->string('nama_petugas', 255)->nullable(false)->change();
            $table->string('email_petugas', 255)->nullable(false)->change();
            $table->string('shift', 20)->nullable(false)->change();
            $table->string('password', 255)->nullable(false)->change();
        });

        Schema::table('petugas', function (Blueprint $table) {
            $table->renameColumn('email_petugas', 'email');
        });
    }
};
