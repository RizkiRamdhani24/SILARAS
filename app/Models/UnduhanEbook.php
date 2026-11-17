<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnduhanEbook extends Model
{
    public $timestamps = false;

    protected $table = 'unduhan_ebook';

    protected $fillable = [
        'anggota_id',
        'ebook_id',
        'judul_ebook',
        'penulis_ebook',
        'waktu_unduh',
    ];

    protected function casts(): array
    {
        return [
            'waktu_unduh' => 'datetime',
        ];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id', 'id_anggota');
    }

    public function ebook(): BelongsTo
    {
        return $this->belongsTo(Ebook::class, 'ebook_id');
    }
}
