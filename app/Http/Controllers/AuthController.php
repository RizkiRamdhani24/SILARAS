<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'password' => ['required'],
        ]);

        $anggota = Anggota::where('email_anggota', $data['email'])->first();

        if ($anggota) {
            if (! Hash::check($data['password'], $anggota->password)) {
                return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
            }

            if ($anggota->status_anggota === Anggota::STATUS_NONAKTIF) {
                return back()->withErrors(['email' => 'Akun Anda nonaktif, hubungi petugas perpustakaan.'])->onlyInput('email');
            }

            Auth::guard('petugas')->logout();
            Auth::guard('anggota')->login($anggota, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->route('anggota.dashboard');
        }

        if (Auth::guard('petugas')->attempt(
            ['email_petugas' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            Auth::guard('anggota')->logout();
            $request->session()->regenerate();

            return redirect()->route('petugas.dashboard');
        }

        return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->merge([
            'email_anggota' => mb_strtolower(trim((string) $request->input('email_anggota'))),
        ]);

        $data = $request->validate([
            'nama_anggota' => ['required', 'string', 'max:100'],
            'email_anggota' => ['required', 'email', 'max:100'],
            'no_telp' => ['nullable', 'string', 'max:15'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        if (
            Anggota::where('email_anggota', $data['email_anggota'])->exists()
            || Petugas::where('email_petugas', $data['email_anggota'])->exists()
        ) {
            return back()
                ->withErrors(['email_anggota' => 'Email sudah terdaftar.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $anggota = Anggota::create($data);

        Auth::guard('petugas')->logout();
        Auth::guard('anggota')->login($anggota);
        $request->session()->regenerate();

        return redirect()->route('anggota.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('anggota')->logout();
        Auth::guard('petugas')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
