<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Petugas extends Authenticatable
{
    protected $table = 'petugas';
    protected $primaryKey = 'id_petugas';

    protected $fillable = ['nama_petugas', 'email', 'shift', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_petugas', 'id_petugas');
    }

    public function pengembalian(): HasMany
    {
        return $this->hasMany(Pengembalian::class, 'id_petugas', 'id_petugas');
    }
}
