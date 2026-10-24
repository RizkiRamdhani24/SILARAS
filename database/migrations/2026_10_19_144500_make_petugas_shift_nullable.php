<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('petugas', function (Blueprint $table) {
            $table->string('shift', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        throw new \LogicException('This migration cannot be rolled back without risking data loss.');
    }
};
