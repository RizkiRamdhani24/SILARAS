<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $query = LogAktivitas::query()
            ->orderByDesc('waktu')
            ->orderByDesc('id');

        if (! empty($validated['dari'])) {
            $query->whereDate('waktu', '>=', $validated['dari']);
        }

        if (! empty($validated['sampai'])) {
            $query->whereDate('waktu', '<=', $validated['sampai']);
        }

        return view('petugas.log-aktivitas.index', [
            'logs' => $query->get(),
            'dari' => $validated['dari'] ?? '',
            'sampai' => $validated['sampai'] ?? '',
        ]);
    }
}
