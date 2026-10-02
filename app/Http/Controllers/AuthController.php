<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'role' => ['required', 'in:anggota,petugas'],
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $guard = $data['role'];

        if (Auth::guard($guard)->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            $request->session()->regenerate();
            return redirect()->intended($guard === 'petugas' ? '/petugas/dashboard' : '/anggota/dashboard');
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email', 'role');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama_anggota' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:anggota,email', 'unique:petugas,email'],
            'no_telp' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $anggota = Anggota::create($data);

        Auth::guard('anggota')->login($anggota);
        $request->session()->regenerate();

        return redirect('/anggota/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('anggota')->logout();
        Auth::guard('petugas')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
