<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengembalian extends Model
{
    public const KONDISI_BAIK = 'baik';
    public const KONDISI_RUSAK = 'rusak';
    public const KONDISI_HILANG = 'hilang';
    public const STATUS_MENUNGGU_VALIDASI = 'menunggu validasi';
    public const STATUS_SELESAI = 'selesai';
    public const DENDA_TIDAK_ADA = 'tidak ada';
    public const DENDA_BELUM_DIBAYAR = 'belum dibayar';
    public const DENDA_LUNAS = 'lunas';

    protected $table = 'pengembalian';
    protected $primaryKey = 'id_pengembalian';

    protected $fillable = [
        'id_peminjaman',
        'id_petugas',
        'tgl_pengembalian',
        'denda',
        'kondisi_buku',
        'status_pengembalian',
        'status_denda',
    ];

    protected function casts(): array
    {
        return [
            'tgl_pengembalian' => 'date',
            'denda' => 'decimal:2',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }
}
