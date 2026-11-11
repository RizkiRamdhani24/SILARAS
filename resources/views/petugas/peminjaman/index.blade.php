@extends('petugas.layout')

@section('title', 'Pengelolaan Peminjaman')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Pengelolaan Peminjaman</h1>
            <p class="mt-1 text-sm text-slate-600">Periksa pengajuan anggota dan kelola status peminjaman melalui alur yang tersedia.</p>
        </div>
        <span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">{{ $jumlahMenunggu }} pengajuan menunggu validasi</span>
    </div>

    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5">
            <div>
                <h2 class="text-base font-extrabold">Daftar pengajuan dan peminjaman</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $peminjaman->total() }} catatan ditampilkan sesuai filter.</p>
            </div>
            <form method="GET" action="{{ route('petugas.peminjaman.index') }}" class="flex flex-wrap gap-2">
                <label class="sr-only" for="cari-peminjaman">Cari anggota atau buku</label>
                <input id="cari-peminjaman" type="search" name="q" value="{{ $search }}" placeholder="Cari anggota atau judul buku..."
                       class="w-60 rounded-xl border-0 bg-[#EEF1F8] px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <label class="sr-only" for="status-peminjaman">Status peminjaman</label>
                <select id="status-peminjaman" name="status" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua status</option>
                    <option value="menunggu validasi" @selected($selectedStatus === 'menunggu validasi')>Menunggu validasi</option>
                    <option value="dipinjam" @selected($selectedStatus === 'dipinjam')>Dipinjam</option>
                    <option value="terlambat" @selected($selectedStatus === 'terlambat')>Terlambat</option>
                    <option value="pengembalian diajukan" @selected($selectedStatus === 'pengembalian diajukan')>Pengembalian diajukan</option>
                    <option value="dikembalikan" @selected($selectedStatus === 'dikembalikan')>Dikembalikan</option>
                    <option value="ditolak" @selected($selectedStatus === 'ditolak')>Ditolak</option>
                    <option value="dibatalkan" @selected($selectedStatus === 'dibatalkan')>Dibatalkan</option>
                    <option value="hilang" @selected($selectedStatus === 'hilang')>Hilang</option>
                </select>
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[66rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Anggota</th>
                        <th scope="col" class="px-5 py-3">Buku</th>
                        <th scope="col" class="px-5 py-3">Tanggal pengajuan</th>
                        <th scope="col" class="px-5 py-3">Tanggal jatuh tempo</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($peminjaman as $item)
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $item->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Peminjaman #{{ $item->id_peminjaman }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $item->buku?->judul_buku ?? 'Buku tidak tersedia' }}</td>
                            <td class="px-5 py-4">{{ $item->tgl_peminjaman?->format('d M Y') ?? '-' }}</td>
                            <td class="px-5 py-4">{{ $item->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum ditetapkan' }}</td>
                            <td class="px-5 py-4">
                                @php
                                    $loanStatusTone = match ($item->status_peminjaman) {
                                        'menunggu validasi' => 'bg-amber-50 text-amber-800',
                                        'dipinjam' => 'bg-blue-50 text-blue-800',
                                        'terlambat' => 'bg-red-50 text-red-800',
                                        'dikembalikan' => 'bg-emerald-50 text-emerald-800',
                                        'ditolak' => 'bg-slate-100 text-slate-700',
                                        'dibatalkan' => 'bg-slate-100 text-slate-700',
                                        'hilang' => 'bg-red-100 text-red-800',
                                        default => 'bg-indigo-50 text-indigo-800',
                                    };
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $loanStatusTone }}">{{ ucfirst($item->status_peminjaman) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($item->status_peminjaman === \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI)
                                    <div class="flex flex-wrap gap-2">
                                        <form action="{{ route('petugas.peminjaman.setujui', $item->id_peminjaman) }}" method="POST" data-confirm="Setujui pengajuan peminjaman ini?" data-confirm-title="Setujui peminjaman">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Setujui</button>
                                        </form>
                                        <form action="{{ route('petugas.peminjaman.tolak', $item->id_peminjaman) }}" method="POST" class="flex flex-wrap items-center gap-2" data-confirm="Tolak pengajuan peminjaman ini?" data-confirm-title="Tolak peminjaman" data-confirm-variant="danger" data-confirm-label="Ya, tolak">
                                            @csrf
                                            @method('PATCH')
                                            <label class="sr-only" for="alasan-{{ $item->id_peminjaman }}">Alasan penolakan</label>
                                            <input id="alasan-{{ $item->id_peminjaman }}" name="alasan_penolakan" type="text" required minlength="5" maxlength="500" placeholder="Alasan penolakan"
                                                   class="w-40 rounded-lg border-slate-200 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-blue-600">
                                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Tolak</button>
                                        </form>
                                    </div>
                                @endif
                                <a href="{{ route('petugas.peminjaman.show', $item->id_peminjaman) }}" class="mt-2 inline-block text-xs font-semibold text-blue-700 hover:underline">Detail transaksi</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada peminjaman yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($peminjaman->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $peminjaman->links() }}</div>
        @endif
    </section>
@endsection
