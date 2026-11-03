@extends('layouts.auth')

@section('title', 'Katalog Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">

    <div class="silaras-admin-heading">
        <div>
            <h1>Katalog Buku</h1>
            <p>Temukan buku dan ajukan peminjaman melalui SILARAS.</p>
        </div>
    </div>

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

    <form method="GET" action="{{ route('anggota.buku.index') }}"
          style="display:flex; gap:10px; margin:20px 0;">
        <input
            type="text"
            name="q"
            value="{{ request('q') }}"
            placeholder="Cari judul, pengarang, atau penerbit..."
            style="flex:1; padding:12px; border:1px solid #ddd; border-radius:8px;"
        >

        <button type="submit"
                style="padding:12px 20px; border:0; border-radius:8px; cursor:pointer;">
            Cari Buku
        </button>
    </form>

    @if ($buku->count())
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:20px;">

            @foreach ($buku as $item)
                <div style="border:1px solid #e5e7eb; border-radius:12px; padding:18px; background:white;">

                    @if ($item->cover)
                        <img
                            src="{{ asset('storage/' . $item->cover) }}"
                            alt="{{ $item->judul_buku }}"
                            style="width:100%; height:180px; object-fit:contain; margin-bottom:12px;"
                        >
                    @else
                        <div style="height:180px; display:flex; align-items:center; justify-content:center; background:#f3f4f6; border-radius:8px; margin-bottom:12px;">
                            <span>Buku SILARAS</span>
                        </div>
                    @endif

                    <h3 style="font-weight:700; margin-bottom:8px;">
                        {{ $item->judul_buku }}
                    </h3>

                    <p>Pengarang: {{ $item->pengarang }}</p>
                    <p>Penerbit: {{ $item->penerbit ?? '-' }}</p>
                    <p>Kategori: {{ $item->kategori ?? '-' }}</p>

                    <p style="margin:10px 0;">
                        Stok:
                        <strong>{{ $item->stock }}</strong>
                    </p>

                    @if ($item->stock > 0)
                        <form method="POST" action="{{ route('anggota.peminjaman.store') }}">
                            @csrf
                            <input type="hidden" name="id_buku" value="{{ $item->id_buku }}">

                            <button type="submit"
                                style="width:100%; padding:11px; background:#2563eb; color:white; border:0; border-radius:8px; cursor:pointer;">
                                Pinjam Buku
                            </button>
                        </form>
                    @else
                        <button disabled
                            style="width:100%; padding:11px; background:#9ca3af; color:white; border:0; border-radius:8px;">
                            Stok Habis
                        </button>
                    @endif

                </div>
            @endforeach

        </div>

        <div style="margin-top:20px;">
            {{ $buku->links() }}
        </div>
    @else
        <p>Belum ada buku yang tersedia.</p>
    @endif

</div>
@endsection