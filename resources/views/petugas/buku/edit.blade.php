
@extends('layouts.auth')

@section('title', 'Edit Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">
    <div class="silaras-admin-heading">
        <div>
            <span class="silaras-admin-kicker">MANAJEMEN PERPUSTAKAAN</span>
            <h1>Edit Buku</h1>
            <p>Perbarui informasi buku dalam koleksi SILARAS.</p>
        </div>

        <a href="{{ route('petugas.kelola-buku.index') }}">
            ← Kembali
        </a>
    </div>

    <div class="silaras-admin-panel">
        <h2>Informasi Buku</h2>

        <form action="{{ route('petugas.kelola-buku.update', $buku->id_buku) }}"
              method="POST">
            @csrf
            @method('PUT')

            <div>
                <label>Judul Buku *</label>
                <input type="text" name="judul_buku"
                       value="{{ old('judul_buku', $buku->judul_buku) }}" required>
                @error('judul_buku')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Pengarang *</label>
                <input type="text" name="pengarang"
                       value="{{ old('pengarang', $buku->pengarang) }}" required>
                @error('pengarang')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Penerbit</label>
                <input type="text" name="penerbit"
                       value="{{ old('penerbit', $buku->penerbit) }}">
            </div>

            <div>
                <label>Kategori</label>
                <input type="text" name="kategori"
                       value="{{ old('kategori', $buku->kategori) }}">
            </div>

            <div>
                <label>ISBN</label>
                <input type="text" name="isbn"
                       value="{{ old('isbn', $buku->isbn) }}">
            </div>

            <div>
                <label>Stok *</label>
                <input type="number" name="stock"
                       value="{{ old('stock', $buku->stock) }}" min="0" required>
                @error('stock')
                    <small>{{ $message }}</small>
                @enderror
            </div>

            <div>
                <label>Nomor Rak</label>
                <input type="text" name="rak"
                       value="{{ old('rak', $buku->rak) }}">
            </div>

            <button type="submit">Simpan Perubahan</button>
        </form>
    </div>
</div>
@endsection
