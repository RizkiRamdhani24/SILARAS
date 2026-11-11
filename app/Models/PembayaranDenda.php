<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembayaranDenda extends Model
{
    protected $table = 'pembayaran_denda';
    protected $primaryKey = 'id_pembayaran_denda';

    protected $fillable = [
        'id_tagihan_denda',
        'id_anggota',
        'id_petugas_verifikasi',
        'metode_pembayaran',
        'jumlah_pembayaran',
        'bukti_pembayaran',
        'status_pembayaran',
        'catatan_petugas',
        'waktu_pembayaran',
        'waktu_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_pembayaran' => 'decimal:2',
            'waktu_pembayaran' => 'datetime',
            'waktu_verifikasi' => 'datetime',
        ];
    }

    public function tagihanDenda(): BelongsTo
    {
        return $this->belongsTo(TagihanDenda::class, 'id_tagihan_denda', 'id_tagihan_denda');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_anggota', 'id_anggota');
    }

    public function petugasVerifikasi(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas_verifikasi', 'id_petugas');
    }
}
