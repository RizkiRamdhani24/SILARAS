
@extends('layouts.auth')

@section('title', 'Kelola Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">

    {{-- Pesan notifikasi --}}
    @if (session('success'))
        <div class="silaras-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="silaras-alert-error">
            {{ session('error') }}
        </div>
    @endif

    {{-- Header halaman --}}
    <div class="silaras-admin-heading">
        <div>
            <span class="silaras-admin-kicker">
                MANAJEMEN PERPUSTAKAAN
            </span>
            <h1>Kelola Buku</h1>
            <p>
                Kelola koleksi buku yang tersedia di perpustakaan SILARAS.
            </p>
        </div>

        <a href="{{ route('petugas.kelola-buku.create') }}"
           class="silaras-admin-add-button">
            + Tambah Buku
        </a>
    </div>

    {{-- Statistik --}}
    <div class="silaras-admin-stat">
        <div>
            <span>Total Koleksi Buku</span>
            <strong>{{ $buku->total() }}</strong>
        </div>

        <div>
            <span>Halaman</span>
            <strong>{{ $buku->currentPage() }}</strong>
        </div>
    </div>

    {{-- Daftar buku --}}
    <div class="silaras-admin-panel">

        <div class="silaras-admin-panel-heading">
            <div>
                <h2>Daftar Koleksi Buku</h2>
                <p>
                    Informasi buku yang tersimpan dalam database.
                </p>
            </div>

            {{-- Form pencarian --}}
            <form action="{{ route('petugas.kelola-buku.index') }}"
                  method="GET">

                <input
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari judul atau pengarang..."
                >

                <button type="submit">Cari</button>
            </form>
        </div>

        {{-- Tabel buku --}}
        <div class="silaras-admin-table-wrapper">
            <table class="silaras-admin-table">

                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Judul Buku</th>
                        <th>Pengarang</th>
                        <th>Penerbit</th>
                        <th>Kategori</th>
                        <th>Stok</th>
                        <th>Rak</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($buku as $item)
                        <tr>
                            <td>
                                {{ $loop->iteration + $buku->firstItem() - 1 }}
                            </td>

                            <td>{{ $item->judul_buku }}</td>

                            <td>{{ $item->pengarang }}</td>

                            <td>{{ $item->penerbit ?: '-' }}</td>

                            <td>{{ $item->kategori ?: '-' }}</td>

                            <td>{{ $item->stock }}</td>

                            <td>{{ $item->rak ?: '-' }}</td>

                            {{-- Tombol aksi --}}
                            <td>
                                <a href="{{ route('petugas.kelola-buku.edit', $item->id_buku) }}">
                                    Edit
                                </a>

                                <form
                                    action="{{ route('petugas.kelola-buku.destroy', $item->id_buku) }}"
                                    method="POST"
                                    onsubmit="return confirm('Yakin ingin menghapus buku ini?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="8" class="silaras-admin-empty">
                                Belum ada data buku.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

        {{-- Pagination --}}
        <div class="silaras-admin-pagination">
            {{ $buku->links() }}
        </div>

    </div>
</div>
@endsection
