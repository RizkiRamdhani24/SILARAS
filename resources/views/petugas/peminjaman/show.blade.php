@extends('petugas.layout')

@section('title', 'Detail Peminjaman')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Detail Peminjaman</h1>
            <p class="mt-1 text-sm text-slate-600">Transaksi #{{ $peminjaman->id_peminjaman }}</p>
        </div>
        <a href="{{ route('petugas.peminjaman.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke daftar</a>
    </div>

    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($notifikasiPenolakan)
        <div role="status" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $notifikasiPenolakan->isi }}</div>
    @endif

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
            <h2 class="text-base font-extrabold">Informasi transaksi</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</dt><dd class="mt-1 font-semibold">{{ $peminjaman->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</dd><dd class="text-xs text-slate-500">ID {{ $peminjaman->id_anggota }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buku</dt><dd class="mt-1 font-semibold">{{ $peminjaman->buku?->judul_buku ?? 'Buku tidak tersedia' }}</dd><dd class="text-xs text-slate-500">{{ $peminjaman->buku?->pengarang ?? 'Pengarang tidak tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal pengajuan</dt><dd class="mt-1">{{ $peminjaman->tgl_peminjaman?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal persetujuan</dt><dd class="mt-1">Tidak tersedia pada skema data saat ini</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Jatuh tempo</dt><dd class="mt-1">{{ $peminjaman->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum ditetapkan' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</dt><dd class="mt-1">{{ ucfirst($peminjaman->status_peminjaman) }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Petugas pemroses</dt><dd class="mt-1">{{ $peminjaman->petugas?->nama_petugas ?? 'Belum tercatat' }}</dd></div>
            </dl>
        </section>

        <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
            <h2 class="text-base font-extrabold">Aksi sesuai status</h2>
            @if ($peminjaman->status_peminjaman === \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI)
                <div class="mt-4 flex flex-wrap gap-2">
                    <form action="{{ route('petugas.peminjaman.setujui', $peminjaman->id_peminjaman) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Setujui peminjaman</button>
                    </form>
                </div>
                <form action="{{ route('petugas.peminjaman.tolak', $peminjaman->id_peminjaman) }}" method="POST" class="mt-5 space-y-3">
                    @csrf
                    @method('PATCH')
                    <label class="block text-sm font-semibold" for="alasan-penolakan">Alasan penolakan</label>
                    <textarea id="alasan-penolakan" name="alasan_penolakan" rows="3" minlength="5" maxlength="500" required class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-600 focus:ring-blue-600">{{ old('alasan_penolakan') }}</textarea>
                    @error('alasan_penolakan')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Tolak pengajuan</button>
                </form>
            @else
                <p class="mt-3 text-sm text-slate-600">Transaksi sudah diproses. Tidak tersedia aksi edit atau hapus untuk status ini.</p>
            @endif
        </section>
    </div>
@endsection
