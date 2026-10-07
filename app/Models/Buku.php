<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buku extends Model
{
    protected $table = 'buku';
    protected $primaryKey = 'id_buku';

    protected $fillable = ['judul_buku', 'penerbit', 'pengarang', 'stock', 'kategori'];

    protected function casts(): array
    {
        return ['stock' => 'integer'];
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class, 'id_buku', 'id_buku');
    }
}
