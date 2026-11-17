<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ebook', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('penulis');
            $table->text('cover')->nullable();
            $table->text('link_drive');
            $table->timestamps();
        });

        Schema::create('unduhan_ebook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')
                ->constrained('anggota', 'id_anggota')
                ->restrictOnDelete();
            $table->foreignId('ebook_id')
                ->nullable()
                ->constrained('ebook')
                ->nullOnDelete();
            $table->string('judul_ebook');
            $table->string('penulis_ebook');
            $table->timestamp('waktu_unduh');
            $table->unique(['anggota_id', 'ebook_id'], 'unduhan_ebook_anggota_ebook_unique');
        });

        Schema::create('log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petugas_id')
                ->constrained('petugas', 'id_petugas')
                ->restrictOnDelete();
            $table->string('nama_petugas');
            $table->string('aksi', 10);
            $table->string('objek', 20);
            $table->string('keterangan')->nullable();
            $table->timestamp('waktu');
            $table->index('waktu');
        });

        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $this->enablePostgresPolicies();
        } elseif ($driver !== 'sqlite') {
            throw new LogicException("Driver database {$driver} tidak didukung untuk tabel Ebook dan log aktivitas.");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
        Schema::dropIfExists('unduhan_ebook');
        Schema::dropIfExists('ebook');
    }

    private function enablePostgresPolicies(): void
    {
        foreach (['ebook', 'unduhan_ebook'] as $table) {
            DB::statement("ALTER TABLE public.{$table} ENABLE ROW LEVEL SECURITY");
            DB::statement(
                "CREATE POLICY {$table}_deny_public ON public.{$table} ".
                'FOR ALL TO PUBLIC USING (false) WITH CHECK (false)'
            );
            DB::statement(
                "CREATE POLICY {$table}_postgres_access ON public.{$table} ".
                'FOR ALL TO postgres USING (true) WITH CHECK (true)'
            );
        }

        DB::statement('ALTER TABLE public.log_aktivitas ENABLE ROW LEVEL SECURITY');
        DB::statement(
            'CREATE POLICY log_aktivitas_deny_public_select ON public.log_aktivitas '.
            'FOR SELECT TO PUBLIC USING (false)'
        );
        DB::statement(
            'CREATE POLICY log_aktivitas_deny_public_insert ON public.log_aktivitas '.
            'FOR INSERT TO PUBLIC WITH CHECK (false)'
        );
        DB::statement(
            'CREATE POLICY log_aktivitas_postgres_select ON public.log_aktivitas '.
            'FOR SELECT TO postgres USING (true)'
        );
        DB::statement(
            'CREATE POLICY log_aktivitas_postgres_insert ON public.log_aktivitas '.
            'FOR INSERT TO postgres WITH CHECK (true)'
        );
    }
};
