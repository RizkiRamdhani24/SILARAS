@extends('layouts.auth')

@section('title', 'Atur Ulang Kata Sandi | SILARAS')

@section('content')
    <div class="silaras-login-layout">
        <section class="silaras-login-card" aria-labelledby="password-reset-title">
            <h1 id="password-reset-title">Atur ulang kata sandi</h1>
            <p class="silaras-login-subtitle">Buat kata sandi baru untuk akun Anda.</p>

            <form class="silaras-login-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="silaras-login-field">
                    <label for="email">Email akun</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
                    @error('email')
                        <p class="silaras-login-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="silaras-login-field">
                    <label for="password">Kata sandi baru</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                    @error('password')
                        <p class="silaras-login-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="silaras-login-field">
                    <label for="password_confirmation">Ulangi kata sandi baru</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                </div>
                <button class="silaras-login-submit" type="submit">Simpan kata sandi baru</button>
            </form>
        </section>
    </div>
@endsection
