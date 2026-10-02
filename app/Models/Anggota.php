<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anggota extends Authenticatable
{
    protected $table = 'anggota';
    protected $primaryKey = 'id_anggota';

    protected $fillable = ['nama_anggota', 'email', 'no_telp', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_anggota', 'id_anggota');
    }
}
