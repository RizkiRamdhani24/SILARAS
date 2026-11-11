<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function showAnggota(): View
    {
        $member = Auth::guard('anggota')->user();
        abort_unless($member instanceof Anggota, 403);

        return view('anggota.profile', ['member' => $member]);
    }

    public function updateAnggota(Request $request): RedirectResponse
    {
        $member = Auth::guard('anggota')->user();
        abort_unless($member instanceof Anggota, 403);

        $validated = $request->validate([
            'nama_anggota' => ['required', 'string', 'max:100'],
            'no_telp' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(?=(?:\\D*\\d){8,15}\\D*$)\\+?[0-9][0-9\\s().-]*$/',
            ],
        ], [
            'nama_anggota.required' => 'Nama lengkap wajib diisi.',
            'nama_anggota.max' => 'Nama lengkap maksimal 100 karakter.',
            'no_telp.regex' => 'Masukkan nomor telepon yang valid dengan 8 sampai 15 digit.',
            'no_telp.max' => 'Nomor telepon maksimal 20 karakter.',
        ]);

        if ($member->nama_anggota === $validated['nama_anggota']
            && $member->no_telp === ($validated['no_telp'] ?? null)) {
            return back()->with('status', 'Tidak ada perubahan pada profil Anda.');
        }

        $member->fill($validated)->save();

        return back()->with('status', 'Profil berhasil diperbarui.');
    }

    public function showPetugas(): View
    {
        $petugas = Auth::guard('petugas')->user();
        abort_unless($petugas instanceof Petugas, 403);

        return view('petugas.profile', ['petugas' => $petugas]);
    }

    public function updatePetugas(Request $request): RedirectResponse
    {
        $petugas = Auth::guard('petugas')->user();
        abort_unless($petugas instanceof Petugas, 403);

        $validated = $request->validate([
            'nama_petugas' => ['required', 'string', 'max:100'],
        ], [
            'nama_petugas.required' => 'Nama lengkap wajib diisi.',
            'nama_petugas.max' => 'Nama lengkap maksimal 100 karakter.',
        ]);

        if ($petugas->nama_petugas === $validated['nama_petugas']) {
            return back()->with('status', 'Tidak ada perubahan pada profil Anda.');
        }

        $petugas->fill($validated)->save();

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
