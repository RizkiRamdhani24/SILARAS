<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah nama kolom hanya jika kolom lama masih tersedia.
        if (
            Schema::hasColumn('pengembalian', 'tgl_kembali') &&
            !Schema::hasColumn('pengembalian', 'tgl_pengembalian')
        ) {
            Schema::table('pengembalian', function (Blueprint $table) {
                $table->renameColumn('tgl_kembali', 'tgl_pengembalian');
            });
        }

        // Pastikan kolom tanggal tujuan tersedia.
        if (!Schema::hasColumn('pengembalian', 'tgl_pengembalian')) {
            throw new RuntimeException(
                'Kolom tgl_kembali maupun tgl_pengembalian tidak ditemukan.'
            );
        }

        Schema::table('pengembalian', function (Blueprint $table) {
            if (Schema::hasColumn('pengembalian', 'id_petugas')) {
                $table->unsignedBigInteger('id_petugas')->nullable()->change();
            }

            if (!Schema::hasColumn('pengembalian', 'kondisi_buku')) {
                $table->string('kondisi_buku', 20)->nullable();
            }

            if (!Schema::hasColumn('pengembalian', 'status_pengembalian')) {
                $table->string('status_pengembalian', 50)
                    ->default('menunggu validasi');
            }

            if (!Schema::hasColumn('pengembalian', 'status_denda')) {
                $table->string('status_denda', 30)
                    ->default('tidak ada');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengembalian', function (Blueprint $table) {
            foreach ([
                'kondisi_buku',
                'status_pengembalian',
                'status_denda',
            ] as $column) {
                if (Schema::hasColumn('pengembalian', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (
            Schema::hasColumn('pengembalian', 'tgl_pengembalian') &&
            !Schema::hasColumn('pengembalian', 'tgl_kembali')
        ) {
            Schema::table('pengembalian', function (Blueprint $table) {
                $table->renameColumn('tgl_pengembalian', 'tgl_kembali');
            });
        }
    }
};