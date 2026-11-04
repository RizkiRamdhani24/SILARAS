<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Login Google dibatalkan atau tidak berhasil.']);
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Sesi login Google kedaluwarsa. Silakan coba lagi.']);
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $isEmailVerified = ($googleUser->user['email_verified'] ?? false) === true;

        if (! $isEmailVerified || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Google tidak memberikan alamat email terverifikasi.']);
        }

        $anggota = Anggota::where('email_anggota', $email)->first();

        if (! $anggota && Petugas::where('email_petugas', $email)->exists()) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Email ini terdaftar sebagai petugas. Silakan masuk melalui halaman petugas.']);
        }

        if (! $anggota) {
            $nama = trim((string) $googleUser->getName());

            $anggota = Anggota::create([
                'nama_anggota' => mb_substr($nama !== '' ? $nama : Str::before($email, '@'), 0, 100),
                'email_anggota' => $email,
                'password' => Str::random(64),
            ]);
        }

        if ($anggota->status_anggota === Anggota::STATUS_NONAKTIF) {
            return redirect()
                ->route('login')
                ->withErrors(['google' => 'Akun Anda nonaktif, hubungi petugas perpustakaan.']);
        }

        Auth::guard('petugas')->logout();
        Auth::guard('anggota')->login($anggota);
        $request->session()->regenerate();

        return redirect()->route('home');
    }
}
