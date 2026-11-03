
@extends('layouts.auth')

@section('title', 'Validasi Pengembalian | SILARAS')

@section('content')
<div class="silaras-admin-page">
    <div class="silaras-admin-heading">
        <div>
            <h2>Validasi Pengembalian Buku</h2>
            <p>Periksa kondisi buku dan validasi pengembalian anggota.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="silaras-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Judul Buku</th>
                    <th>Tanggal Pengembalian</th>
                    <th>Status</th>
                    <th>Denda</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pengembalian as $item)
                    <tr>
                        <td>{{ $item->peminjaman->anggota->nama_anggota ?? '-' }}</td>
                        <td>{{ $item->peminjaman->buku->judul_buku ?? '-' }}</td>
                        <td>{{ $item->tgl_pengembalian }}</td>
                        <td>{{ ucfirst($item->status_pengembalian) }}</td>
                        <td>Rp{{ number_format($item->denda, 0, ',', '.') }}</td>
                        <td>
                            @if ($item->status_pengembalian === 'menunggu validasi')
                                <form action="{{ route('petugas.pengembalian.setujui', $item->id_pengembalian) }}"
                                      method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <label>Kondisi Buku</label>
                                    <select name="kondisi_buku" required>
                                        <option value="">Pilih kondisi</option>
                                        <option value="baik">Baik</option>
                                        <option value="rusak">Rusak</option>
                                        <option value="hilang">Hilang</option>
                                    </select>

                                    <button type="submit"
                                            onclick="return confirm('Validasi pengembalian buku ini?')">
                                        Validasi
                                    </button>
                                </form>
                            @else
                                Sudah diproses
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;">
                            Belum ada pengajuan pengembalian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
