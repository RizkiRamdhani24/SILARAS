@extends('layouts.auth')

@section('title', 'Masuk Anggota | SILARAS')

@section('content')
    <div class="silaras-login-layout">
        <section class="silaras-login-intro" aria-label="Tentang SILARAS">
            <div class="silaras-register-pills">
                <span class="silaras-register-pill silaras-register-pill-verified">
                    <span class="silaras-register-pill-dot" aria-hidden="true"></span>
                    SISTEM RESMI TERVERIFIKASI
                </span>
                <span class="silaras-register-pill silaras-register-pill-version">SILARAS Smart v3.2</span>
            </div>

            <img class="silaras-login-logo" src="{{ asset('images/silaras-logo.png') }}" alt="SILARAS — Sistem Layanan Pustaka Cerdas">

            <div class="silaras-register-mantra">
                <span aria-hidden="true"></span>
                GERBANG LITERASI CERDAS &amp; AKSES MANDIRI
            </div>

            <p class="silaras-register-description">
                Registrasi keanggotaan pustaka untuk akses peminjaman mandiri, koleksi digital, dan integrasi smart RFID secara instan.
            </p>

            <p class="silaras-register-connected">
                <svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="m12 2 2.1 2.2 3-.3.9 2.9 2.7 1.4-.8 2.9 1.7 2.5-2.1 2.2.3 3-2.9.9-1.4 2.7-2.9-.8-2.5 1.7-2.2-2.1-3 .3-.9-2.9-2.7-1.4.8-2.9L2.4 12l2.1-2.2-.3-3 2.9-.9 1.4-2.7 2.9.8L12 2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <path d="m8.5 12.2 2.2 2.2 4.8-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>Terhubung ke Pangkalan Data Pendidikan Tinggi &amp; SIAKAD Terpadu.</span>
            </p>
        </section>

        <section class="silaras-login-card" aria-labelledby="login-title">
            <div class="silaras-login-card-icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.8 19 5.5v5.2c0 4.4-2.8 8-7 10.5-4.2-2.5-7-6.1-7-10.5V5.5l7-2.7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <circle cx="12" cy="9" r="2.1" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M8.5 16a3.5 3.5 0 0 1 7 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </div>
            <h1 id="login-title">Masuk Anggota</h1>
            <p class="silaras-login-subtitle">Gunakan akun yang sudah terdaftar<br>untuk mengakses sistem E-Library.</p>

            <form class="silaras-login-form" method="POST" action="{{ route('login') }}">
                @csrf
                <div class="silaras-login-field">
                    <label for="email">Email Anggota</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Masukkan email anggota" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')
                        <p class="silaras-login-error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-login-field">
                    <label for="password">Kata Sandi</label>
                    <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                </div>

                <button class="silaras-login-submit" type="submit">Masuk</button>
            </form>

            <div class="silaras-login-divider" role="separator" aria-label="atau masuk dengan">
                <span>atau</span>
            </div>

            <a class="silaras-login-google" href="{{ route('auth.google.redirect') }}">
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5Z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.72 7.18l7.62 5.91c4.44-4.1 7.14-10.14 7.14-17.56Z"/>
                    <path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.75-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.87.93 7.55 2.56 10.78l7.97-6.19Z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.89l-7.62-5.91c-2.12 1.42-4.83 2.27-8.29 2.27-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48Z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>

            @error('google')
                <p class="silaras-login-error" role="alert">{{ $message }}</p>
            @enderror

            <p class="silaras-login-register-link">
                Belum punya akun?
                <a href="{{ route('register') }}">Daftar Anggota</a>
            </p>
        </section>
    </div>
@endsection
