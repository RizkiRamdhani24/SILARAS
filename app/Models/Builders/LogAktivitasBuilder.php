<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

class LogAktivitasBuilder extends Builder
{
    public function update(array $values)
    {
        throw new LogicException('Log aktivitas tidak dapat diubah.');
    }

    public function delete()
    {
        throw new LogicException('Log aktivitas tidak dapat dihapus.');
    }
}
