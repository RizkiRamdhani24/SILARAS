<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE public.migrations ENABLE ROW LEVEL SECURITY');
    }

    public function down(): void
    {
        throw new \LogicException('This migration cannot be rolled back without disabling RLS.');
    }
};
