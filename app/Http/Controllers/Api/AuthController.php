<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // LOGIN ANGGOTA API
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $anggota = Anggota::where(
            'email_anggota',
            $credentials['email']
        )->first();

        if (
            !$anggota ||
            !Hash::check($credentials['password'], $anggota->password)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        if ($anggota->status_anggota !== 'aktif') {
            return response()->json([
                'success' => false,
                'message' => 'Akun anggota tidak aktif.',
            ], 403);
        }

        $token = $anggota->createToken('silaras-api')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token_type' => 'Bearer',
            'token' => $token,
            'data' => [
                'id_anggota' => $anggota->id_anggota,
                'nama_anggota' => $anggota->nama_anggota,
                'email_anggota' => $anggota->email_anggota,
            ],
        ], 200);
    }

    // DATA ANGGOTA YANG SEDANG LOGIN
    public function me(Request $request)
    {
        $anggota = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id_anggota' => $anggota->id_anggota,
                'nama_anggota' => $anggota->nama_anggota,
                'email_anggota' => $anggota->email_anggota,
            ],
        ]);
    }

    // LOGOUT API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
