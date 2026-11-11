@extends('petugas.layout')

@section('title', 'Input Pengembalian')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Input Pengembalian</h1>
            <p class="mt-1 text-sm text-slate-600">Catat buku yang diterima langsung oleh Petugas tanpa pengajuan Anggota.</p>
        </div>
        <a href="{{ route('petugas.pengembalian.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke pengembalian</a>
    </div>

    @if ($errors->any())
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <section class="mt-6 max-w-3xl rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
        <h2 class="text-base font-extrabold">Data buku yang diterima</h2>
        @if ($peminjamanAktif->isEmpty())
            <div class="mt-5 rounded-xl bg-[#EEF3FC] p-5 text-sm text-slate-600">Tidak ada peminjaman aktif tanpa catatan pengembalian.</div>
        @else
            <form method="POST" action="{{ route('petugas.pengembalian.store') }}" class="mt-5 space-y-5">
                @csrf
                <div>
                    <label for="peminjaman" class="text-sm font-semibold">Peminjaman aktif <span class="text-red-600">*</span></label>
                    <select id="peminjaman" name="id_peminjaman" required class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        <option value="">Pilih anggota dan buku</option>
                        @foreach ($peminjamanAktif as $item)
                            <option value="{{ $item->id_peminjaman }}" @selected(old('id_peminjaman') == $item->id_peminjaman)>
                                #{{ $item->id_peminjaman }} · {{ $item->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }} · {{ $item->buku?->judul_buku ?? 'Buku tidak tersedia' }}
                            </option>
                        @endforeach
                    </select>
                    @error('id_peminjaman')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="rounded-xl bg-[#EEF3FC] p-4">
                    <h3 class="text-sm font-bold">Informasi peminjaman yang tersedia</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-700">
                        @foreach ($peminjamanAktif as $item)
                            <li>
                                <span class="font-semibold">#{{ $item->id_peminjaman }}</span> —
                                {{ $item->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }},
                                {{ $item->buku?->judul_buku ?? 'Buku tidak tersedia' }}.
                                Jatuh tempo: {{ $item->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }};
                                status: {{ ucfirst($item->status_peminjaman) }}.
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="tgl-pengembalian" class="text-sm font-semibold">Tanggal diterima <span class="text-red-600">*</span></label>
                        <input id="tgl-pengembalian" type="date" name="tgl_pengembalian" value="{{ old('tgl_pengembalian', now('Asia/Jakarta')->toDateString()) }}" max="{{ now('Asia/Jakarta')->toDateString() }}" required class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                        @error('tgl_pengembalian')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="kondisi-buku" class="text-sm font-semibold">Kondisi buku <span class="text-red-600">*</span></label>
                        <select id="kondisi-buku" name="kondisi_buku" required class="mt-1 w-full rounded-xl border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-blue-600">
                            <option value="">Pilih kondisi</option>
                            <option value="baik" @selected(old('kondisi_buku') === 'baik')>Baik</option>
                            <option value="rusak" @selected(old('kondisi_buku') === 'rusak')>Rusak</option>
                            <option value="hilang" @selected(old('kondisi_buku') === 'hilang')>Hilang</option>
                        </select>
                        @error('kondisi_buku')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <p class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">Penyimpanan akan langsung menyelesaikan pengembalian, mencatat Petugas, memperbarui stok bila buku berkondisi baik, dan menghitung denda berdasarkan tanggal penerimaan serta pengaturan sistem.</p>

                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Simpan Pengembalian</button>
            </form>
        @endif
    </section>
@endsection
