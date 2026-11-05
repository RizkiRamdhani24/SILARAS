@extends('layouts.auth')

@section('title', 'Pengembalian Buku | SILARAS')

@section('content')
<div class="silaras-admin-page">
    <div class="silaras-admin-heading">
        <div>
            <h2>Pengembalian Buku</h2>
            <p>Ajukan pengembalian buku yang sedang kamu pinjam.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="silaras-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="silaras-alert-success">
            {{ $errors->first() }}
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
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($peminjaman as $item)
                    <tr>
                        <td>{{ $item->buku->judul_buku ?? '-' }}</td>
                        <td>{{ $item->tgl_peminjaman }}</td>
                        <td>{{ $item->tgl_jatuh_tempo ?? '-' }}</td>
                        <td>{{ ucfirst($item->status_peminjaman) }}</td>
                        <td>
                            @if (in_array($item->status_peminjaman, ['dipinjam', 'terlambat']))
                                <form action="{{ route('anggota.pengembalian.ajukan', $item->id_peminjaman) }}"
                                      method="POST"
                                      onsubmit="return confirm('Yakin ingin mengajukan pengembalian buku ini?')">
                                    @csrf
                                    <button type="submit">Ajukan Pengembalian</button>
                                </form>
                            @elseif ($item->status_peminjaman === 'pengembalian diajukan')
                                Menunggu validasi petugas
                            @else
                                Selesai
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;">
                            Belum ada peminjaman buku.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
