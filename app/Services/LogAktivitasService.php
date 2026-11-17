<?php

namespace App\Services;

use App\Exceptions\LogAktivitasException;
use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;
use Throwable;

class LogAktivitasService
{
    public function catat(string $aksi, string $objek, ?string $keterangan = null): LogAktivitas
    {
        $petugas = Auth::guard('petugas')->user();

        if (! $petugas) {
            throw new LogAktivitasException;
        }

        try {
            return LogAktivitas::query()->create([
                'petugas_id' => $petugas->getAuthIdentifier(),
                'nama_petugas' => $petugas->nama_petugas,
                'aksi' => $aksi,
                'objek' => $objek,
                'keterangan' => $keterangan,
                'waktu' => now(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            throw new LogAktivitasException($exception);
        }
    }
}
