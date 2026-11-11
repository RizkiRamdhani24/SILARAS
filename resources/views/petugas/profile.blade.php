@extends('petugas.layout')

@section('title', 'Profil Petugas')

@section('content')
    @php
        $profileInitials = collect(explode(' ', trim($petugas->nama_petugas)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    @endphp

    <section class="mx-auto mt-8 max-w-4xl" aria-labelledby="profile-title">
        <header class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-700 via-blue-700 to-indigo-800 p-6 text-white shadow-sm sm:p-8">
            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center">
                <span class="grid size-16 shrink-0 place-items-center rounded-full border border-white/30 bg-white/15 text-xl font-bold" aria-hidden="true">
                    {{ $profileInitials ?: 'P' }}
                </span>
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-100">Akun Petugas</p>
                    <h1 id="profile-title" class="mt-1 text-2xl font-extrabold">Profil Saya</h1>
                    <p class="mt-1 text-sm text-blue-100">Kelola informasi akun Anda dengan mudah.</p>
                    <p class="mt-3 text-sm font-semibold">{{ $petugas->nama_petugas }}</p>
                </div>
            </div>
            <span class="material-symbols-rounded pointer-events-none absolute -bottom-5 right-5 text-8xl text-white/10" aria-hidden="true">badge</span>
        </header>

        @if (session('status'))
            <div @class([
                'mt-5 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-medium',
                'border-blue-200 bg-blue-50 text-blue-800' => str_starts_with(session('status'), 'Tidak ada perubahan'),
                'border-emerald-200 bg-emerald-50 text-emerald-800' => ! str_starts_with(session('status'), 'Tidak ada perubahan'),
            ]) role="status">
                @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-5'])
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                @include('petugas.partials.icon', ['name' => 'alert', 'class' => 'size-5 shrink-0'])
                <span>Periksa kembali data yang dimasukkan. {{ $errors->first() }}</span>
            </div>
        @endif

        <form id="petugas-profile-form" method="POST" action="{{ route('petugas.profile.update') }}" class="mt-5 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-100 sm:p-8">
            @csrf
            @method('PUT')

            <div class="mb-6 flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700">
                    @include('petugas.partials.icon', ['name' => 'users', 'class' => 'size-5'])
                </span>
                <div>
                    <h2 class="font-extrabold">Informasi Profil</h2>
                    <p class="mt-1 text-sm text-slate-500">Perbarui nama yang ditampilkan pada aktivitas petugas.</p>
                </div>
            </div>

            <div class="grid gap-5">
                <div>
                    <label for="nama_petugas" class="mb-2 block text-sm font-semibold">Nama Lengkap</label>
                    <input id="nama_petugas" name="nama_petugas" type="text" value="{{ old('nama_petugas', $petugas->nama_petugas) }}" maxlength="100" autocomplete="name" required aria-describedby="nama-petugas-error" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                    @error('nama_petugas')
                        <small class="mt-1 block text-sm text-red-700" id="nama-petugas-error">{{ $message }}</small>
                    @enderror
                </div>

                <div>
                    <label for="email_petugas" class="mb-2 block text-sm font-semibold">Email</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-4 grid place-items-center text-slate-400">
                            @include('petugas.partials.icon', ['name' => 'lock', 'class' => 'size-4'])
                        </span>
                        <input id="email_petugas" type="email" value="{{ $petugas->email_petugas }}" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-4 text-sm text-slate-500 outline-none">
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Email akun digunakan untuk autentikasi dan tidak dapat diubah dari halaman profil.</p>
                </div>
            </div>

            <section class="mt-7 border-t border-slate-100 pt-6" aria-labelledby="petugas-profile-details">
                <h2 id="petugas-profile-details" class="text-sm font-extrabold">Informasi Kepegawaian</h2>
                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">ID Petugas</dt>
                        <dd class="mt-1 text-sm font-bold">#{{ $petugas->getAuthIdentifier() }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Bergabung</dt>
                        <dd class="mt-1 text-sm font-bold">{{ $petugas->created_at?->translatedFormat('d F Y') ?? 'Belum tersedia' }}</dd>
                    </div>
                </dl>
            </section>

            <div class="mt-6 flex flex-col-reverse justify-end gap-3 sm:flex-row">
                <button id="petugas-profile-reset" type="button" data-name="{{ $petugas->nama_petugas }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-4'])
                    Reset / Batal
                </button>
                <button id="petugas-profile-submit" type="submit" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 text-sm font-bold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50">
                    @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-4'])
                    Simpan Perubahan
                </button>
            </div>
        </form>

        <script>
            (() => {
                const form = document.getElementById('petugas-profile-form');
                const name = document.getElementById('nama_petugas');
                const submit = document.getElementById('petugas-profile-submit');
                const reset = document.getElementById('petugas-profile-reset');
                if (!form || !name || !submit || !reset) return;

                const savedName = reset.dataset.name ?? '';
                const updateSubmitState = () => {
                    submit.disabled = name.value === savedName || !form.checkValidity();
                };
                form.addEventListener('input', updateSubmitState);
                reset.addEventListener('click', () => {
                    name.value = savedName;
                    updateSubmitState();
                });
                updateSubmitState();
            })();
        </script>
    </section>
@endsection
