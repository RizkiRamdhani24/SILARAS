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

    public function showPetugasLogin()
    {
        return view('auth.login-petugas');
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

            return redirect()->route('home');
        }

        return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
    }

    public function loginPetugas(Request $request)
    {
        $request->merge([
            'email_petugas' => mb_strtolower(trim((string) $request->input('email_petugas'))),
        ]);

        $data = $request->validate([
            'email_petugas' => ['required', 'email', 'max:100'],
            'password' => ['required'],
        ]);

        $petugas = Petugas::where('email_petugas', $data['email_petugas'])->first();

        if (! $petugas || ! Hash::check($data['password'], $petugas->password)) {
            return back()->withErrors(['email_petugas' => 'Email atau kata sandi salah.'])->onlyInput('email_petugas');
        }

        Auth::guard('anggota')->logout();
        Auth::guard('petugas')->login($petugas, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('petugas.login-sukses');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function showPetugasRegister()
    {
        return view('auth.register-petugas');
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
            'password' => ['required', 'min:8'],
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

        return redirect()->route('anggota.daftar-sukses');
    }

    public function registerPetugas(Request $request)
    {
        $request->merge([
            'email_petugas' => mb_strtolower(trim((string) $request->input('email_petugas'))),
        ]);

        $data = $request->validate([
            'nama_petugas' => ['required', 'string', 'max:100'],
            'email_petugas' => ['required', 'email', 'max:100'],
            'password' => ['required', 'min:8'],
        ]);

        if (
            Petugas::where('email_petugas', $data['email_petugas'])->exists()
            || Anggota::where('email_anggota', $data['email_petugas'])->exists()
        ) {
            return back()
                ->withErrors(['email_petugas' => 'Email sudah terdaftar.'])
                ->withInput($request->except('password'));
        }

        $petugas = Petugas::create($data);

        Auth::guard('anggota')->logout();
        Auth::guard('petugas')->login($petugas);
        $request->session()->regenerate();

        return redirect()->route('petugas.daftar-sukses');
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
