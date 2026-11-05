@extends('layouts.auth')

@section('title', 'Tambah Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">
    <div class="silaras-admin-heading">
        <div>
            <span class="silaras-admin-kicker">MANAJEMEN PERPUSTAKAAN</span>
            <h1>Tambah Buku</h1>
            <p>Masukkan informasi buku baru ke dalam koleksi SILARAS.</p>
        </div>

        <a href="{{ route('petugas.kelola-buku.index') }}">
            ← Kembali ke Kelola Buku
        </a>
    </div>

    <div class="silaras-admin-panel">
        <h2>Informasi Buku</h2>

        <form action="{{ route('petugas.buku.store') }}" method="POST">
            @csrf

            <div>
                <label>Judul Buku *</label>
                <input type="text" name="judul_buku"
                    value="{{ old('judul_buku') }}" required>
                @error('judul_buku')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Pengarang *</label>
                <input type="text" name="pengarang"
                    value="{{ old('pengarang') }}" required>
                @error('pengarang')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Penerbit</label>
                <input type="text" name="penerbit"
                    value="{{ old('penerbit') }}">
            </div>

            <div>
                <label>Kategori</label>
                <input type="text" name="kategori"
                    value="{{ old('kategori') }}">
            </div>

            <div>
                <label>ISBN</label>
                <input type="text" name="isbn"
                    value="{{ old('isbn') }}">
            </div>

            <div>
                <label>Stok *</label>
                <input type="number" name="stock"
                    value="{{ old('stock', 1) }}" min="0" required>
                @error('stock')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Nomor Rak</label>
                <input type="text" name="rak"
                    value="{{ old('rak') }}">
            </div>

            <button type="submit">Simpan Buku</button>
        </form>
    </div>
</div>
@endsection
