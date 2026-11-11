<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TagihanDenda extends Model
{
    public const STATUS_BELUM_LUNAS = 'belum_lunas';
    public const STATUS_LUNAS = 'lunas';
    public const STATUS_TIDAK_ADA = 'tidak ada';

    protected $table = 'tagihan_denda';
    protected $primaryKey = 'id_tagihan_denda';

    protected $fillable = [
        'id_peminjaman',
        'id_anggota',
        'nominal_tagihan',
        'nominal_terlambat',
        'nominal_rusak',
        'nominal_hilang',
        'tgl_tagihan',
        'tgl_terakhir_diperbarui',
        'dibekukan_at',
        'status_tagihan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal_tagihan' => 'decimal:2',
            'nominal_terlambat' => 'decimal:2',
            'nominal_rusak' => 'decimal:2',
            'nominal_hilang' => 'decimal:2',
            'tgl_tagihan' => 'date',
            'tgl_terakhir_diperbarui' => 'date',
            'dibekukan_at' => 'datetime',
        ];
    }

    public function peminjaman(): BelongsTo
    {
        return $this->belongsTo(Peminjaman::class, 'id_peminjaman', 'id_peminjaman');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_anggota', 'id_anggota');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(PembayaranDenda::class, 'id_tagihan_denda', 'id_tagihan_denda');
    }
}
