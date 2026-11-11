<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $currentValue = DB::table('pengaturan')
            ->where('kunci', 'tarif_denda_per_hari')
            ->value('nilai');

        if ($currentValue === null) {
            DB::table('pengaturan')->insert([
                'kunci' => 'tarif_denda_per_hari',
                'nilai' => '5000',
                'keterangan' => 'Tarif denda keterlambatan per hari dalam Rupiah.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return;
        }

        if ((string) $currentValue === '1000') {
            DB::table('pengaturan')
                ->where('kunci', 'tarif_denda_per_hari')
                ->where('nilai', '1000')
                ->update([
                    'nilai' => '5000',
                    'keterangan' => 'Tarif denda keterlambatan per hari dalam Rupiah.',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'The tariff migration is intentionally not reversible because the setting may have been changed by staff.'
        );
    }
};
