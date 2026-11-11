<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NotifikasiController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('notifikasi'), 503, 'Layanan notifikasi belum tersedia.');

        $notifikasi = Notifikasi::query()
            ->where('id_anggota', auth('anggota')->id())
            ->latest('created_at')
            ->paginate(15);

        return view('anggota.notifikasi.index', compact('notifikasi'));
    }

    public function buka(Request $request, int $id)
    {
        abort_unless(Schema::hasTable('notifikasi'), 503, 'Layanan notifikasi belum tersedia.');

        $notifikasi = Notifikasi::query()
            ->where('id_anggota', auth('anggota')->id())
            ->whereKey($id)
            ->firstOrFail();

        if (! $notifikasi->is_dibaca) {
            $notifikasi->update(['is_dibaca' => true]);
        }

        $tautan = $notifikasi->tautan;
        if ($tautan) {
            $parsed = parse_url($tautan);

            if (
                isset($parsed['host'])
                && strcasecmp($parsed['host'], $request->getHost()) === 0
            ) {
                return redirect()->to($tautan);
            }

            if (
                isset($parsed['path'])
                && Str::startsWith($parsed['path'], '/')
                && ! Str::startsWith($parsed['path'], '//')
            ) {
                return redirect()->to($parsed['path'] . (isset($parsed['query']) ? '?' . $parsed['query'] : ''));
            }
        }

        return redirect()->route('anggota.notifikasi.index');
    }
}
