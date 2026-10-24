@extends('layouts.auth')

@section('title', 'Daftar Anggota | SILARAS')

@section('content')
    <div class="silaras-register-layout">
        <section class="silaras-register-intro" aria-label="Tentang SILARAS">
            <div class="silaras-register-pills">
                <span class="silaras-register-pill silaras-register-pill-verified">
                    <span class="silaras-register-pill-dot" aria-hidden="true"></span>
                    SISTEM RESMI TERVERIFIKASI
                </span>
                <span class="silaras-register-pill silaras-register-pill-version">SILARAS Smart v3.2</span>
            </div>

            <img class="silaras-register-logo" src="{{ asset('images/silaras-logo.png') }}" alt="SILARAS — Sistem Layanan Pustaka Cerdas">

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

        <section class="silaras-register-card" aria-labelledby="register-title">
            <div class="silaras-register-card-icon" aria-hidden="true">
                <svg width="34" height="34" viewBox="0 0 24 24" fill="none">
                    <path d="M15.5 20v-1.7a4.3 4.3 0 0 0-4.3-4.3H7.8a4.3 4.3 0 0 0-4.3 4.3V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19 8v6M16 11h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1 id="register-title">Daftar Anggota</h1>
            <p class="silaras-register-subtitle">Isi data diri Anda untuk membuat akun E-Library.</p>

            <form class="silaras-register-form" method="POST" action="{{ route('register') }}">
                @csrf

                <div class="silaras-register-field">
                    <label for="nama_anggota">Nama Lengkap</label>
                    <div class="silaras-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="M19 20v-1.5a4 4 0 0 0-4-4h-6a4 4 0 0 0-4 4V20M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="nama_anggota" name="nama_anggota" type="text" value="{{ old('nama_anggota') }}" placeholder="Masukkan nama lengkap" autocomplete="name" required @error('nama_anggota') aria-invalid="true" aria-describedby="nama_anggota-error" @enderror>
                    </div>
                    @error('nama_anggota')
                        <p class="silaras-register-error" id="nama_anggota-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-register-field">
                    <label for="email_anggota">Email</label>
                    <div class="silaras-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/>
                            <path d="m4.5 7 7.5 6 7.5-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="email_anggota" name="email_anggota" type="email" value="{{ old('email_anggota') }}" placeholder="Masukkan alamat email" autocomplete="email" required @error('email_anggota') aria-invalid="true" aria-describedby="email_anggota-error" @enderror>
                    </div>
                    @error('email_anggota')
                        <p class="silaras-register-error" id="email_anggota-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-register-field">
                    <label for="no_telp">Nomor Telepon</label>
                    <div class="silaras-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <path d="m7.1 3.8 2.4-.5 2 4.7-1.8 1.5a15 15 0 0 0 4.8 4.8l1.5-1.8 4.7 2-.5 2.4a2 2 0 0 1-2.2 1.6A16.7 16.7 0 0 1 4 6a2 2 0 0 1 1.6-2.2l1.5-.3Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input id="no_telp" name="no_telp" type="tel" value="{{ old('no_telp') }}" placeholder="Masukkan nomor telepon" autocomplete="tel" @error('no_telp') aria-invalid="true" aria-describedby="no_telp-error" @enderror>
                    </div>
                    @error('no_telp')
                        <p class="silaras-register-error" id="no_telp-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="silaras-register-field">
                    <label for="password">Kata Sandi</label>
                    <div class="silaras-register-input-wrap">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                            <rect x="4.5" y="10" width="15" height="11" rx="2.2" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <input id="password" name="password" type="password" placeholder="Buat kata sandi" autocomplete="new-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                        <button class="silaras-register-password-toggle" id="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Tampilkan kata sandi" aria-pressed="false">
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
                        <p class="silaras-register-error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <button class="silaras-register-submit" type="submit">Daftar</button>
            </form>

            <p class="silaras-register-login-link">
                Sudah punya akun?
                <a href="{{ route('login') }}">Masuk Anggota</a>
            </p>
        </section>
    </div>
@endsection
