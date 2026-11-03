
@extends('layouts.auth')

@section('title', 'Peminjaman Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">

    <div class="silaras-admin-heading">
        <div>
            <h2>Peminjaman Buku</h2>
            <p>Daftar buku yang sedang kamu pinjam dan status peminjaman.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="silaras-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="silaras-alert-success">
            {{ session('error') }}
        </div>
    @endif

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Judul Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($peminjaman as $item)
                    <tr>
                        <td>{{ $item->buku->judul_buku ?? '-' }}</td>

                        <td>
                            {{ $item->tgl_peminjaman?->format('d-m-Y') ?? '-' }}
                        </td>

                        <td>
                            {{ $item->tgl_jatuh_tempo?->format('d-m-Y') ?? '-' }}
                        </td>

                        <td>
                            {{ ucfirst($item->status_peminjaman) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center; padding:20px;">
                            Belum ada data peminjaman buku.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
