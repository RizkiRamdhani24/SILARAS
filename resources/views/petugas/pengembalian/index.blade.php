@extends('petugas.layout')

@section('title', 'Pengelolaan Pengembalian')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Pengelolaan Pengembalian</h1>
            <p class="mt-1 text-sm text-slate-600">Periksa pengajuan pengembalian anggota dan konfirmasi kondisi buku.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="rounded-xl bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800">{{ $jumlahMenunggu }} pengajuan menunggu validasi</span>
            <a href="{{ route('petugas.pengembalian.input') }}" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Input Pengembalian</a>
        </div>
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
                <h2 class="text-base font-extrabold">Pengajuan pengembalian</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $pengembalian->total() }} catatan ditampilkan sesuai filter.</p>
            </div>
            <form method="GET" action="{{ route('petugas.pengembalian.index') }}" class="flex flex-wrap gap-2">
                <label class="sr-only" for="cari-pengembalian">Cari anggota atau buku</label>
                <input id="cari-pengembalian" type="search" name="q" value="{{ $search }}" placeholder="Cari anggota atau judul buku..."
                       class="w-60 rounded-xl border-0 bg-[#EEF1F8] px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <label class="sr-only" for="status-pengembalian">Status pengembalian</label>
                <select id="status-pengembalian" name="status" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua status</option>
                    <option value="menunggu validasi" @selected($selectedStatus === 'menunggu validasi')>Menunggu validasi</option>
                    <option value="selesai" @selected($selectedStatus === 'selesai')>Selesai</option>
                    <option value="dibatalkan" @selected($selectedStatus === 'dibatalkan')>Dibatalkan</option>
                </select>
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[70rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Anggota</th>
                        <th scope="col" class="px-5 py-3">Buku</th>
                        <th scope="col" class="px-5 py-3">Tanggal pengajuan</th>
                        <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                        <th scope="col" class="px-5 py-3">Keterlambatan</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Konfirmasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengembalian as $item)
                        @php
                            $loan = $item->peminjaman;
                                $tanggalPerhitungan = $item->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI
                                    ? $item->tgl_pengembalian
                                    : today();
                                $lateDays = $loan?->tgl_jatuh_tempo && $tanggalPerhitungan && $tanggalPerhitungan->gt($loan->tgl_jatuh_tempo)
                                    ? (int) $loan->tgl_jatuh_tempo->diffInDays($tanggalPerhitungan)
                                    : 0;
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $loan?->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Pengembalian #{{ $item->id_pengembalian }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $loan?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</td>
                            <td class="px-5 py-4">
                                @if ($item->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI)
                                    {{ $item->tgl_pengembalian?->format('d M Y') ?? 'Belum tersedia' }}
                                @else
                                    {{ $item->created_at?->format('d M Y H:i') ?? 'Belum tersedia' }}
                                    <span class="mt-1 block text-xs text-slate-500">Tanggal pengajuan</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">{{ $loan?->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }}</td>
                            <td class="px-5 py-4">
                                @if ($lateDays > 0)
                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">{{ $lateDays }} hari terlambat</span>
                                @else
                                    <span class="text-slate-500">Tidak terlambat</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-amber-50 text-amber-800' => $item->status_pengembalian === \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI,
                                    'bg-emerald-50 text-emerald-800' => $item->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI,
                                    'bg-slate-100 text-slate-700' => $item->status_pengembalian === \App\Models\Pengembalian::STATUS_DIBATALKAN,
                                ])>{{ ucfirst($item->status_pengembalian) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @if ($item->status_pengembalian === \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI)
                                    <form action="{{ route('petugas.pengembalian.setujui', $item->id_pengembalian) }}" method="POST" class="flex min-w-48 flex-col items-start gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="w-full text-xs font-medium text-slate-600" for="tanggal-kembali-{{ $item->id_pengembalian }}">Tanggal diterima</label>
                                        <input id="tanggal-kembali-{{ $item->id_pengembalian }}" type="date" name="tgl_pengembalian" value="{{ now('Asia/Jakarta')->toDateString() }}" max="{{ now('Asia/Jakarta')->toDateString() }}" class="w-full rounded-lg border-slate-200 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-blue-600">
                                        <label class="sr-only" for="kondisi-buku-{{ $item->id_pengembalian }}">Kondisi buku</label>
                                        <select id="kondisi-buku-{{ $item->id_pengembalian }}" name="kondisi_buku" required class="w-full rounded-lg border-slate-200 px-2.5 py-2 text-xs focus:border-blue-600 focus:ring-blue-600">
                                            <option value="">Pilih kondisi buku</option>
                                            <option value="baik">Baik</option>
                                            <option value="rusak">Rusak</option>
                                            <option value="hilang">Hilang</option>
                                        </select>
                                        <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Konfirmasi pengembalian</button>
                                    </form>
                                @elseif ($item->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI)
                                    <div class="text-xs text-slate-500">
                                        <p>Sudah dikonfirmasi</p>
                                        @if ($item->kondisi_buku)
                                            <p class="mt-1">Kondisi: {{ ucfirst($item->kondisi_buku) }}</p>
                                        @endif
                                        <p class="mt-1">Denda tercatat: Rp {{ number_format((float) $item->denda, 0, ',', '.') }}</p>
                                    </div>
                                @else
                                    <p class="text-xs text-slate-500">Pengajuan ini dibatalkan oleh Anggota.</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pengajuan pengembalian yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pengembalian->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $pengembalian->links() }}</div>
        @endif
    </section>
@endsection
