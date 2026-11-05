<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function loginAnggota(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_anggota' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $anggota = Anggota::where(
            'email_anggota',
            $validated['email_anggota']
        )->first();

        if (! $anggota || ! Hash::check($validated['password'], $anggota->password)) {
            throw ValidationException::withMessages([
                'email_anggota' => ['Email atau password salah.'],
            ]);
        }

        if ($anggota->status_anggota !== Anggota::STATUS_AKTIF) {
            return response()->json([
                'success' => false,
                'message' => 'Akun anggota tidak aktif. Hubungi petugas perpustakaan.',
            ], 403);
        }

        $token = $anggota->createToken(
            $validated['device_name'] ?? 'silaras-anggota'
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login anggota berhasil.',
            'data' => [
                'user' => [
                    'id_anggota' => $anggota->id_anggota,
                    'nama_anggota' => $anggota->nama_anggota,
                    'email_anggota' => $anggota->email_anggota,
                    'status_anggota' => $anggota->status_anggota,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function loginPetugas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_petugas' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $petugas = Petugas::where(
            'email_petugas',
            $validated['email_petugas']
        )->first();

        if (! $petugas || ! Hash::check($validated['password'], $petugas->password)) {
            throw ValidationException::withMessages([
                'email_petugas' => ['Email atau password salah.'],
            ]);
        }

        $token = $petugas->createToken(
            $validated['device_name'] ?? 'silaras-petugas'
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login petugas berhasil.',
            'data' => [
                'user' => [
                    'id_petugas' => $petugas->id_petugas,
                    'nama_petugas' => $petugas->nama_petugas,
                    'email_petugas' => $petugas->email_petugas,
                    'shift' => $petugas->shift,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}
