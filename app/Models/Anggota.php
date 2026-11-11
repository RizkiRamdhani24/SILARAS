<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Anggota extends Authenticatable
{
    use HasApiTokens;

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_NONAKTIF = 'nonaktif';

    protected $table = 'anggota';
    protected $primaryKey = 'id_anggota';

    protected $fillable = [
        'nama_anggota',
        'email_anggota',
        'no_telp',
        'password',
        'status_anggota'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed'
        ];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(
            Peminjaman::class,
            'id_anggota',
            'id_anggota'
        );
    }

    public function tagihanDenda(): HasMany
    {
        return $this->hasMany(TagihanDenda::class, 'id_anggota', 'id_anggota');
    }

    public function pembayaranDenda(): HasMany
    {
        return $this->hasMany(PembayaranDenda::class, 'id_anggota', 'id_anggota');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'id_anggota', 'id_anggota');
    }
}
