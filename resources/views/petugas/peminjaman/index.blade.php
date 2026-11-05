
@extends('layouts.auth')

@section('title', 'Validasi Peminjaman | SILARAS')

@section('content')
<div class="silaras-admin-page">

    <div class="silaras-admin-heading">
        <div>
            <h1>Validasi Peminjaman</h1>
            <p>Kelola pengajuan peminjaman buku dari anggota.</p>
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

    <div class="silaras-admin-card">
        <div class="silaras-admin-card-header">
            <h2>Daftar Pengajuan</h2>
            <span>{{ $peminjaman->count() }} pengajuan</span>
        </div>

        @if ($peminjaman->isEmpty())
            <div class="silaras-empty-state">
                <h3>Belum Ada Pengajuan</h3>
                <p>Pengajuan peminjaman dari anggota akan muncul di sini.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Anggota</th>
                            <th>Judul Buku</th>
                            <th>Tanggal Pengajuan</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($peminjaman as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    {{ $item->anggota->nama_anggota ?? 'Anggota tidak ditemukan' }}
                                </td>

                                <td>
                                    {{ $item->buku->judul_buku ?? 'Buku tidak ditemukan' }}
                                </td>

                                <td>
                                    {{ $item->tgl_peminjaman
                                        ? \Carbon\Carbon::parse($item->tgl_peminjaman)->format('d-m-Y')
                                        : '-' }}
                                </td>

                                <td>
                                    {{ $item->tgl_jatuh_tempo
                                        ? \Carbon\Carbon::parse($item->tgl_jatuh_tempo)->format('d-m-Y')
                                        : '-' }}
                                </td>

                                <td>
                                    @if ($item->status_peminjaman === 'menunggu validasi')
                                        <span class="badge bg-warning text-dark">Menunggu Validasi</span>
                                    @elseif ($item->status_peminjaman === 'dipinjam')
                                        <span class="badge bg-success">Dipinjam</span>
                                    @elseif ($item->status_peminjaman === 'ditolak')
                                        <span class="badge bg-danger">Ditolak</span>
                                    @elseif ($item->status_peminjaman === 'terlambat')
                                        <span class="badge bg-danger">Terlambat</span>
                                    @elseif ($item->status_peminjaman === 'dikembalikan')
                                        <span class="badge bg-secondary">Dikembalikan</span>
                                    @else
                                        <span class="badge bg-info">
                                            {{ ucfirst($item->status_peminjaman) }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($item->status_peminjaman === 'menunggu validasi')
                                        <div class="d-flex gap-2">

                                            <form action="{{ route('petugas.peminjaman.setujui', $item->id_peminjaman) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Setujui peminjaman buku ini?')">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="btn btn-success btn-sm">
                                                    Setujui
                                                </button>
                                            </form>

                                            <form action="{{ route('petugas.peminjaman.tolak', $item->id_peminjaman) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('Tolak pengajuan peminjaman ini?')">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    Tolak
                                                </button>
                                            </form>

                                        </div>
                                    @else
                                        <span class="text-muted">Sudah diproses</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
