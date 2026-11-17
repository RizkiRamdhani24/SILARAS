<?php

namespace App\Models;

use App\Models\Builders\LogAktivitasBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class LogAktivitas extends Model
{
    public $timestamps = false;

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'petugas_id',
        'nama_petugas',
        'aksi',
        'objek',
        'keterangan',
        'waktu',
    ];

    public function newEloquentBuilder($query): LogAktivitasBuilder
    {
        return new LogAktivitasBuilder($query);
    }

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
        ];
    }

    protected function performUpdate(Builder $query)
    {
        throw new LogicException('Log aktivitas tidak dapat diubah.');
    }

    protected function performDeleteOnModel()
    {
        throw new LogicException('Log aktivitas tidak dapat dihapus.');
    }
}
