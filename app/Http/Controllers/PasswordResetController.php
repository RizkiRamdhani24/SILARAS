<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\Petugas;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showAnggotaLinkRequestForm(): View
    {
        return view('auth.passwords.email', [
            'accountType' => 'anggota',
            'requestRoute' => 'password.email',
            'loginRoute' => 'login',
        ]);
    }

    public function showPetugasLinkRequestForm(): View
    {
        return view('auth.passwords.email', [
            'accountType' => 'petugas',
            'requestRoute' => 'petugas.password.email',
            'loginRoute' => 'petugas.login',
        ]);
    }

    public function sendAnggotaResetLink(Request $request): RedirectResponse
    {
        return $this->sendResetLink($request, 'anggota', 'email_anggota');
    }

    public function sendPetugasResetLink(Request $request): RedirectResponse
    {
        return $this->sendResetLink($request, 'petugas', 'email_petugas');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => mb_strtolower(trim((string) $request->query('email', ''))),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ]);
        $email = mb_strtolower(trim($data['email']));

        $isAnggota = Anggota::where('email_anggota', $email)->exists();
        $isPetugas = Petugas::where('email_petugas', $email)->exists();
        if ($isAnggota === $isPetugas) {
            return back()
                ->withInput(['email' => $email])
                ->withErrors(['email' => 'Tautan reset tidak valid atau sudah kedaluwarsa.']);
        }

        $broker = $isAnggota ? 'anggota' : 'petugas';
        $emailColumn = $isAnggota ? 'email_anggota' : 'email_petugas';
        $loginRoute = $isAnggota ? 'login' : 'petugas.login';
        $status = Password::broker($broker)->reset(
            [
                $emailColumn => $email,
                'token' => $data['token'],
                'password' => $data['password'],
                'password_confirmation' => $data['password_confirmation'],
            ],
            function ($user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route($loginRoute)
                ->with('status', 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.');
        }

        return back()
            ->withInput(['email' => $email])
            ->withErrors(['email' => __($status)]);
    }

    private function sendResetLink(Request $request, string $broker, string $emailColumn): RedirectResponse
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);
        $data = $request->validate([
            'email' => ['required', 'email', 'max:100'],
        ]);

        Password::broker($broker)->sendResetLink([
            $emailColumn => $data['email'],
        ]);

        return back()->with(
            'status',
            'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.'
        );
    }
}
