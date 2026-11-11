<?php

namespace App\Http\Controllers\Anggota;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Peminjaman;
use App\Services\DendaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class KelolaAnggotaController extends Controller
{
    public function index(DendaService $dendaService)
    {
        $anggota = Anggota::query()
            ->orderBy('nama_anggota')
            ->get()
            ->map(function (Anggota $member) use ($dendaService) {
                $pinjamanAktif = $member->peminjaman()
                    ->whereIn('status_peminjaman', Peminjaman::STATUS_AKTIF)
                    ->count();
                $bebasTunggakan = $dendaService->bolehMeminjamBaru((int) $member->getKey());

                return [
                    'id_anggota' => $member->getKey(),
                    'id' => '#AGT-' . str_pad((string) $member->getKey(), 3, '0', STR_PAD_LEFT),
                    'induk' => 'ID: ' . $member->getKey(),
                    'nama' => $member->nama_anggota,
                    'sub' => $member->email_anggota,
                    'email' => $member->email_anggota,
                    'telp' => $member->no_telp ?? '-',
                    'pinjam' => $pinjamanAktif,
                    'status' => $member->status_anggota === Anggota::STATUS_NONAKTIF ? 'Ditangguhkan' : 'Aktif',
                    'cat' => $member->status_anggota === Anggota::STATUS_NONAKTIF
                        ? 'ditangguhkan'
                        : ($bebasTunggakan ? 'bebas' : 'tunggakan'),
                    'catatan' => $bebasTunggakan ? 'bebas tunggakan' : 'ada tunggakan',
                    'tone' => $bebasTunggakan ? 'blue' : 'red',
                    'can_disable' => $member->status_anggota === Anggota::STATUS_AKTIF
                        && $pinjamanAktif === 0
                        && $bebasTunggakan,
                ];
            });

        return view('petugas.anggota.index', compact('anggota'));
    }

    public function nonaktifkan(int $id, DendaService $dendaService): RedirectResponse
    {
        $anggota = DB::transaction(function () use ($id, $dendaService) {
            $member = Anggota::query()->whereKey($id)->lockForUpdate()->firstOrFail();

            if ($member->status_anggota === Anggota::STATUS_NONAKTIF) {
                return 'nonaktif';
            }

            $pinjamanAktif = $member->peminjaman()
                ->whereIn('status_peminjaman', Peminjaman::STATUS_AKTIF)
                ->exists();

            if ($pinjamanAktif) {
                return 'pinjaman';
            }

            if (! $dendaService->bolehMeminjamBaru((int) $member->getKey())) {
                return 'denda';
            }

            $member->update(['status_anggota' => Anggota::STATUS_NONAKTIF]);

            return 'berhasil';
        }, 3);

        if ($anggota === 'pinjaman') {
            return back()->with('error', 'Anggota masih memiliki peminjaman berjalan.');
        }

        if ($anggota === 'denda') {
            return back()->with('error', 'Anggota masih memiliki tagihan denda yang belum lunas.');
        }

        if ($anggota === 'nonaktif') {
            return back()->with('error', 'Akun anggota sudah nonaktif.');
        }

        return back()->with('success', 'Akun anggota berhasil dinonaktifkan.');
    }
}
