@extends('petugas.layout')

@section('title', 'Tambah Ebook')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Tambah Ebook</h1>
            <p class="mt-1 text-sm text-slate-600">Masukkan informasi ebook ke koleksi SILARAS.</p>
        </div>
        <a href="{{ route('petugas.ebook.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke daftar ebook</a>
    </div>

    <section class="mt-6 max-w-3xl rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
        <h2 class="text-base font-extrabold">Informasi ebook</h2>
        <form action="{{ route('petugas.ebook.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-5 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2">
                <label for="judul-ebook" class="text-sm font-semibold">Judul <span class="text-red-600">*</span></label>
                <input id="judul-ebook" type="text" name="judul" value="{{ old('judul') }}" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('judul')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="penulis-ebook" class="text-sm font-semibold">Penulis <span class="text-red-600">*</span></label>
                <input id="penulis-ebook" type="text" name="penulis" value="{{ old('penulis') }}" required maxlength="255" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('penulis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="cover-ebook" class="text-sm font-semibold">Cover (JPG, PNG, WebP; maks. 1 MB) <span class="text-red-600">*</span></label>
                <input id="cover-ebook" type="file" name="cover" accept="image/jpeg,image/png,image/webp" required class="mt-1 block w-full text-sm">
                @error('cover')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="link-drive" class="text-sm font-semibold">Link Google Drive <span class="text-red-600">*</span></label>
                <input id="link-drive" type="text" name="link_drive" value="{{ old('link_drive') }}" required class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('link_drive')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Simpan ebook</button>
                <a href="{{ route('petugas.ebook.index') }}" class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</a>
            </div>
        </form>
    </section>
@endsection
