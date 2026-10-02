<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peminjaman extends Model
{
    protected $table = 'peminjaman';
    protected $primaryKey = 'id_peminjaman';

    protected $fillable = [
        'id_buku',
        'id_anggota',
        'id_petugas',
        'tgl_peminjaman',
        'tgl_jatuh_tempo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_peminjaman' => 'date',
            'tgl_jatuh_tempo' => 'date',
        ];
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(Buku::class, 'id_buku', 'id_buku');
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'id_anggota', 'id_anggota');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(Petugas::class, 'id_petugas', 'id_petugas');
    }

    public function pengembalian(): HasOne
    {
        return $this->hasOne(Pengembalian::class, 'id_peminjaman', 'id_peminjaman');
    }
}
