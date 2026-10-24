@extends('layouts.auth')

@section('title', 'Daftar Petugas | SILARAS')

@section('content')
    <div class="silaras-staff-register-layout">
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

        <section class="silaras-staff-register-card" aria-labelledby="staff-register-title">
            <p class="silaras-staff-register-badge">
                <span aria-hidden="true"></span>
                Akun Petugas Baru
            </p>
            <h1 id="staff-register-title">Form Pendaftaran Petugas</h1>
            <p class="silaras-staff-register-subtitle">
                Isi data berikut sesuai dengan informasi yang benar untuk membuat akun petugas.
            </p>

            <form class="silaras-staff-register-form" method="POST" action="{{ route('petugas.register') }}">
                @csrf

                <div class="silaras-staff-register-field">
                    <label for="nama_petugas">NAMA PETUGAS</label>
                    <div class="silaras-staff-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M19 20v-1.5a4 4 0 0 0-4-4h-6a4 4 0 0 0-4 4V20M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="nama_petugas" name="nama_petugas" type="text" value="{{ old('nama_petugas') }}" placeholder="Masukkan nama lengkap petugas" autocomplete="name" required @error('nama_petugas') aria-invalid="true" aria-describedby="nama_petugas-error" @enderror>
                    </div>
                    @error('nama_petugas')
                        <p class="silaras-staff-register-error" id="nama_petugas-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-staff-register-field">
                    <label for="email_petugas">EMAIL PETUGAS</label>
                    <div class="silaras-staff-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                            <path d="m4.5 7 7.5 6 7.5-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="email_petugas" name="email_petugas" type="email" value="{{ old('email_petugas') }}" placeholder="Masukkan email petugas" autocomplete="email" required @error('email_petugas') aria-invalid="true" aria-describedby="email_petugas-error" @enderror>
                    </div>
                    @error('email_petugas')
                        <p class="silaras-staff-register-error" id="email_petugas-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-staff-register-field">
                    <label for="petugas_password">KATA SANDI</label>
                    <div class="silaras-staff-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="10" width="15" height="11" rx="2.2" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <input id="petugas_password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="petugas-password-error" @enderror>
                        <button class="silaras-staff-register-password-toggle silaras-register-password-toggle" id="petugas-password-toggle" type="button" data-password-toggle aria-controls="petugas_password" aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="silaras-password-eye" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                <circle cx="12" cy="12" r="2.5" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                            <svg class="silaras-password-eye-off" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                                <path d="m3 3 18 18M10.6 6.2A10.3 10.3 0 0 1 12 6c6.1 0 9.5 6 9.5 6a16 16 0 0 1-3.2 3.6M6.2 6.7C3.8 8.3 2.5 12 2.5 12s3.4 6 9.5 6c1 0 1.9-.2 2.7-.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="silaras-staff-register-error" id="petugas-password-error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="silaras-staff-register-submit" type="submit">Daftar</button>
            </form>

            <p class="silaras-staff-register-security">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <rect x="5" y="10" width="14" height="11" rx="2" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                Data petugas dilindungi enkripsi SHA-256 &amp; Kebijakan Perpustakaan
            </p>

            <p class="silaras-staff-register-login-link">
                Sudah punya akun?
                <a href="{{ route('petugas.login') }}">Masuk Petugas</a>
            </p>
        </section>
    </div>
@endsection
