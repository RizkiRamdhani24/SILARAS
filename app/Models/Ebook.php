<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ebook extends Model
{
    protected $table = 'ebook';

    protected $fillable = [
        'judul',
        'penulis',
        'cover',
        'link_drive',
    ];

    public function unduhan(): HasMany
    {
        return $this->hasMany(UnduhanEbook::class, 'ebook_id');
    }
}
