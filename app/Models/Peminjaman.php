<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Peminjaman extends Model
{
    public const STATUS_MENUNGGU_VALIDASI = 'menunggu validasi';
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_TERLAMBAT = 'terlambat';
    public const STATUS_PENGEMBALIAN_DIAJUKAN = 'pengembalian diajukan';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_HILANG = 'hilang';

    public const STATUS_AKTIF = [
        self::STATUS_MENUNGGU_VALIDASI,
        self::STATUS_DIPINJAM,
        self::STATUS_TERLAMBAT,
        self::STATUS_PENGEMBALIAN_DIAJUKAN,
    ];

    public const DURASI_PEMINJAMAN_HARI = [7, 14, 30];

    protected $table = 'peminjaman';
    protected $primaryKey = 'id_peminjaman';

    protected $fillable = [
        'id_buku',
        'id_anggota',
        'id_petugas',
        'tgl_peminjaman',
        'tgl_jatuh_tempo',
        'durasi_peminjaman_hari',
        'status_peminjaman',
        'alasan_penolakan',
    ];

    protected function casts(): array
    {
        return [
            'tgl_peminjaman' => 'date',
            'tgl_jatuh_tempo' => 'date',
            'durasi_peminjaman_hari' => 'integer',
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
        return $this->hasOne(
            Pengembalian::class,
            'id_peminjaman',
            'id_peminjaman'
        )->latestOfMany('id_pengembalian');
    }

    public function pengembalianAktif(): HasOne
    {
        return $this->hasOne(
            Pengembalian::class,
            'id_peminjaman',
            'id_peminjaman'
        )->where('status_pengembalian', '!=', Pengembalian::STATUS_DIBATALKAN)
            ->latestOfMany('id_pengembalian');
    }

    public function pengembalians(): HasMany
    {
        return $this->hasMany(
            Pengembalian::class,
            'id_peminjaman',
            'id_peminjaman'
        )->orderBy('id_pengembalian');
    }

    public function tagihanDenda(): HasOne
    {
        return $this->hasOne(TagihanDenda::class, 'id_peminjaman', 'id_peminjaman');
    }
}
