@extends('layouts.anggota')

@section('title', 'Profil Saya | SILARAS')

@section('content')
    @php
        $profileInitials = collect(explode(' ', trim($member->nama_anggota)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    @endphp

    <section class="member-profile-page" aria-labelledby="profile-title">
        <header class="member-profile-hero">
            <div class="member-profile-avatar" aria-hidden="true">{{ $profileInitials ?: 'A' }}</div>
            <div class="member-profile-heading">
                <p class="member-profile-eyebrow">AKUN ANGGOTA</p>
                <h1 id="profile-title">Profil Saya</h1>
                <p>Kelola informasi akun Anda dengan mudah.</p>
                <div class="member-profile-identity">
                    <strong>{{ $member->nama_anggota }}</strong>
                    <span class="member-profile-status">{{ ucfirst($member->status_anggota ?? 'aktif') }}</span>
                </div>
            </div>
            <span class="material-symbols-rounded member-profile-hero-icon" aria-hidden="true">menu_book</span>
        </header>

        @if (session('status'))
            <div class="member-profile-alert {{ str_starts_with(session('status'), 'Tidak ada perubahan') ? 'member-profile-alert-info' : 'member-profile-alert-success' }}" role="status">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">check_circle</span>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="member-profile-alert member-profile-alert-error" role="alert">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">error</span>
                <span>Periksa kembali data yang dimasukkan. {{ $errors->first() }}</span>
            </div>
        @endif

        <form class="member-profile-card" id="member-profile-form" method="POST" action="{{ route('anggota.profile.update') }}">
            @csrf
            @method('PUT')

            <div class="member-profile-form-heading">
                <span class="member-profile-section-icon material-symbols-rounded" aria-hidden="true">manage_accounts</span>
                <div>
                    <h2>Informasi Profil</h2>
                    <p>Perbarui nama dan nomor telepon yang terhubung ke akun Anda.</p>
                </div>
            </div>

            <div class="member-profile-fields">
                <div class="member-profile-field">
                    <label for="nama_anggota">Nama Lengkap</label>
                    <div class="member-profile-input-wrap">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">person</span>
                        <input id="nama_anggota" name="nama_anggota" type="text" value="{{ old('nama_anggota', $member->nama_anggota) }}" maxlength="100" autocomplete="name" required aria-describedby="nama-error">
                    </div>
                    @error('nama_anggota')
                        <small class="member-profile-field-error" id="nama-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="member-profile-field">
                    <label for="no_telp">Nomor Telepon</label>
                    <div class="member-profile-input-wrap">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">call</span>
                        <input id="no_telp" name="no_telp" type="tel" value="{{ old('no_telp', $member->no_telp) }}" maxlength="20" inputmode="tel" autocomplete="tel" pattern="[+]?[0-9][0-9 ().-]{6,18}[0-9]" aria-describedby="telepon-help telepon-error">
                    </div>
                    <small id="telepon-help">Gunakan 8–15 digit, boleh diawali + dan memakai spasi atau tanda hubung.</small>
                    @error('no_telp')
                        <small class="member-profile-field-error" id="telepon-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="member-profile-field member-profile-email-field">
                    <label for="email_anggota">Email</label>
                    <div class="member-profile-input-wrap is-readonly">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">lock</span>
                        <input id="email_anggota" type="email" value="{{ $member->email_anggota }}" readonly aria-describedby="email-help">
                        <span class="member-profile-readonly-badge">
                            <span class="material-symbols-rounded member-icon" aria-hidden="true">lock</span>
                            Tidak dapat diubah
                        </span>
                    </div>
                    <small id="email-help">Email tidak dapat diubah karena terhubung dengan akun Google.</small>
                </div>
            </div>

            <div class="member-profile-notice">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>
                <p>Email tetap menggunakan alamat yang terdaftar saat Anda masuk dengan Google.</p>
            </div>

            <section class="member-profile-details" aria-labelledby="profile-details-title">
                <h2 id="profile-details-title">Informasi Keanggotaan</h2>
                <dl>
                    <div>
                        <dt>Nomor Anggota</dt>
                        <dd>#{{ $member->getAuthIdentifier() }}</dd>
                    </div>
                    <div>
                        <dt>Tanggal Bergabung</dt>
                        <dd>{{ $member->created_at?->translatedFormat('d F Y') ?? 'Belum tersedia' }}</dd>
                    </div>
                    <div>
                        <dt>Status Keanggotaan</dt>
                        <dd><span class="member-profile-status is-detail">{{ ucfirst($member->status_anggota ?? 'aktif') }}</span></dd>
                    </div>
                </dl>
            </section>

            <div class="member-profile-actions">
                <button class="member-profile-button is-secondary" id="member-profile-reset" type="button" data-name="{{ $member->nama_anggota }}" data-phone="{{ $member->no_telp }}">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">restart_alt</span>
                    Reset / Batal
                </button>
                <button class="member-profile-button is-primary" id="member-profile-submit" type="submit">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">save</span>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const form = document.getElementById('member-profile-form');
            const name = document.getElementById('nama_anggota');
            const phone = document.getElementById('no_telp');
            const submit = document.getElementById('member-profile-submit');
            const reset = document.getElementById('member-profile-reset');
            if (!form || !name || !phone || !submit || !reset) return;

            const savedName = reset.dataset.name ?? '';
            const savedPhone = reset.dataset.phone ?? '';
            const updateSubmitState = () => {
                const phoneDigits = phone.value.replace(/\D/g, '').length;
                phone.setCustomValidity(phone.value && (phoneDigits < 8 || phoneDigits > 15)
                    ? 'Nomor telepon harus berisi 8 sampai 15 digit.'
                    : '');
                submit.disabled = (name.value === savedName && phone.value === savedPhone) || !form.checkValidity();
            };

            form.addEventListener('input', updateSubmitState);
            reset.addEventListener('click', () => {
                name.value = savedName;
                phone.value = savedPhone;
                updateSubmitState();
            });
            updateSubmitState();
        })();
    </script>
@endsection
