<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buku', function (Blueprint $table) {
            $table->string('isbn')->nullable();
            $table->string('rak')->nullable();
            $table->string('cover')->nullable();
        });
    }

    public function down(): void
    {
        throw new \LogicException('This migration cannot be rolled back without dropping columns.');
    }
};
