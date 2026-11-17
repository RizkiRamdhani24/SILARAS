<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class LogAktivitasException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Pencatatan log aktivitas gagal.', 0, $previous);
    }
}
