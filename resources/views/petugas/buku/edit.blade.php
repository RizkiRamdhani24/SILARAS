@extends('petugas.layout')

@section('title', 'Edit Buku')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Edit Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Perbarui informasi buku dalam koleksi perpustakaan.</p>
        </div>
        <a href="{{ route('petugas.buku') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke daftar buku</a>
    </div>

    <section class="mt-6 max-w-3xl rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
        <h2 class="text-base font-extrabold">Informasi buku</h2>
        <form action="{{ route('petugas.kelola-buku.update', $buku->id_buku) }}" method="POST" enctype="multipart/form-data" class="mt-5 grid gap-5 sm:grid-cols-2">
            @csrf
            @method('PUT')
            <div class="sm:col-span-2">
                <label for="judul-buku" class="text-sm font-semibold">Judul buku <span class="text-red-600">*</span></label>
                <input id="judul-buku" type="text" name="judul_buku" value="{{ old('judul_buku', $buku->judul_buku) }}" required maxlength="150" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('judul_buku')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pengarang" class="text-sm font-semibold">Pengarang <span class="text-red-600">*</span></label>
                <input id="pengarang" type="text" name="pengarang" value="{{ old('pengarang', $buku->pengarang) }}" required maxlength="100" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('pengarang')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="penerbit" class="text-sm font-semibold">Penerbit</label>
                <input id="penerbit" type="text" name="penerbit" value="{{ old('penerbit', $buku->penerbit) }}" maxlength="100" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
            </div>
            <div>
                <label for="kategori" class="text-sm font-semibold">Kategori</label>
                <input id="kategori" type="text" name="kategori" value="{{ old('kategori', $buku->kategori) }}" maxlength="50" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
            </div>
            <div>
                <label for="isbn" class="text-sm font-semibold">ISBN</label>
                <input id="isbn" type="text" name="isbn" value="{{ old('isbn', $buku->isbn) }}" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
            </div>
            <div>
                <label for="stock" class="text-sm font-semibold">Stok <span class="text-red-600">*</span></label>
                <input id="stock" type="number" name="stock" value="{{ old('stock', $buku->stock) }}" min="0" required class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                @error('stock')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="rak" class="text-sm font-semibold">Nomor rak</label>
                <input id="rak" type="text" name="rak" value="{{ old('rak', $buku->rak) }}" class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
            </div>
            <div class="sm:col-span-2">
                <label for="cover" class="text-sm font-semibold">Gambar cover (opsional, maks. 5 MB)</label>
                <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                @if ($buku->cover && \Illuminate\Support\Facades\Storage::disk('public')->exists($buku->cover))<p class="mt-1 text-xs text-slate-500">Cover saat ini:</p><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($buku->cover) }}" alt="Cover {{ $buku->judul_buku }}" class="mt-2 h-32 rounded-lg object-cover">@elseif ($buku->cover)<p class="mt-1 text-xs text-amber-700">File cover sebelumnya tidak ditemukan. Unggah cover baru untuk menggantinya.</p>@endif
                @error('cover')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <label for="pdf" class="text-sm font-semibold">PDF buku (opsional, maks. 50 MB)</label>
                <input id="pdf" type="file" name="pdf" accept="application/pdf" class="mt-1 block w-full text-sm">
                @if ($buku->pdf)<p class="mt-1 text-xs text-slate-500">PDF sudah tersedia. Kosongkan untuk mempertahankan file saat ini.</p>@endif
                @error('pdf')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex flex-wrap gap-2 sm:col-span-2">
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Simpan perubahan</button>
                <a href="{{ route('petugas.buku') }}" class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</a>
            </div>
        </form>
    </section>
@endsection
