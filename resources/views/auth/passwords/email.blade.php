@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi | SILARAS')

@section('content')
    <div class="silaras-login-layout">
        <section class="silaras-login-card" aria-labelledby="password-reset-title">
            <h1 id="password-reset-title">Lupa kata sandi {{ $accountType }}</h1>
            <p class="silaras-login-subtitle">Masukkan email akun. Jika terdaftar, kami akan mengirim tautan reset.</p>

            @if (session('status'))
                <p class="silaras-login-error" role="status">{{ session('status') }}</p>
            @endif

            <form class="silaras-login-form" method="POST" action="{{ route($requestRoute) }}">
                @csrf
                <div class="silaras-login-field">
                    <label for="email">Email {{ ucfirst($accountType) }}</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                    @error('email')
                        <p class="silaras-login-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <button class="silaras-login-submit" type="submit">Kirim tautan reset</button>
            </form>

            <p class="silaras-login-register-link">
                <a href="{{ route($loginRoute) }}">Kembali ke halaman masuk</a>
            </p>
        </section>
    </div>
@endsection
