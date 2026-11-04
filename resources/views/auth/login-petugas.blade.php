@extends('layouts.auth')

@section('title', 'Masuk Petugas | SILARAS')

@section('content')
    <div class="silaras-staff-login-layout">
        <section class="silaras-staff-register-intro" aria-label="Portal resmi SILARAS">
            <p class="silaras-staff-register-pill">
                <span aria-hidden="true"></span>
                PORTAL RESMI AKADEMIK
                <span class="silaras-staff-register-pill-separator" aria-hidden="true">•</span>
                SILARAS Smart v3.2
            </p>

            <img class="silaras-staff-register-logo" src="{{ asset('images/silaras-logo.png') }}" alt="SILARAS — Sistem Layanan Pustaka Cerdas">

            <p class="silaras-staff-register-description">
                Akses terpadu portal petugas, repositori ilmiah, dan sirkulasi literasi digital terotomatisasi secara instan dengan standar otomasi perpustakaan perguruan tinggi modern.
            </p>

            <div class="silaras-staff-register-mantra">
                <span aria-hidden="true"></span>
                GERBANG LITERASI CERDAS &amp; AKSES MANDIRI
            </div>
        </section>

        <section class="silaras-login-card silaras-staff-login-card" aria-labelledby="staff-login-title">
            <div class="silaras-login-card-icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
                    <path d="M12 2.8 19 5.5v5.2c0 4.4-2.8 8-7 10.5-4.2-2.5-7-6.1-7-10.5V5.5l7-2.7Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                    <circle cx="12" cy="9" r="2.1" stroke="currentColor" stroke-width="1.7"/>
                    <path d="M8.5 16a3.5 3.5 0 0 1 7 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                </svg>
            </div>
            <h1 id="staff-login-title">Masuk Petugas</h1>
            <p class="silaras-login-subtitle">Gunakan akun yang sudah terdaftar<br>untuk mengakses sistem E-Library.</p>

            <form class="silaras-login-form" method="POST" action="{{ route('petugas.login') }}">
                @csrf
                <div class="silaras-login-field">
                    <label for="email_petugas">Email Petugas</label>
                    <input id="email_petugas" type="email" name="email_petugas" value="{{ old('email_petugas') }}" placeholder="Masukkan email petugas" autocomplete="username" required @error('email_petugas') aria-invalid="true" aria-describedby="email-petugas-error" @enderror>
                    @error('email_petugas')
                        <p class="silaras-login-error" id="email-petugas-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-login-field">
                    <label for="petugas_login_password">Kata Sandi</label>
                    <input id="petugas_login_password" type="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                </div>

                <button class="silaras-login-submit" type="submit">Masuk</button>
            </form>

            <p class="silaras-login-register-link">
                Belum punya akun?
                <a href="{{ route('petugas.register') }}">Daftar Petugas</a>
            </p>
        </section>
    </div>
@endsection
