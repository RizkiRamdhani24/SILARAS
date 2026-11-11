@extends('petugas.layout')

@section('title', 'Pengelolaan Denda')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Pengelolaan Denda</h1>
            <p class="mt-1 text-sm text-slate-600">Pantau tagihan denda dan verifikasi pembayaran anggota.</p>
        </div>
        <form method="POST" action="{{ route('petugas.denda.tarif.update') }}" class="flex items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
            @csrf
            <label class="text-sm font-semibold">Tarif per hari (Rp)
                <input type="number" name="tarif_denda_per_hari" min="0" step="1" value="{{ (int) $tarifDendaPerHari }}" required class="mt-1 block w-40 rounded-lg border-slate-200 px-3 py-2">
            </label>
            <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">Simpan Tarif</button>
        </form>
    </div>

    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <div class="border-b border-slate-100 p-5">
            <h2 class="text-base font-extrabold">Pembayaran menunggu verifikasi</h2>
            <p class="mt-1 text-sm text-slate-500">Verifikasi hanya tersedia untuk pembayaran yang masih menunggu pemeriksaan.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[58rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Anggota</th>
                        <th scope="col" class="px-5 py-3">Peminjaman / buku</th>
                        <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                        <th scope="col" class="px-5 py-3">Tagihan</th>
                        <th scope="col" class="px-5 py-3">Pembayaran</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pembayaranMenungguVerifikasi as $payment)
                        @php
                            $bill = $payment->tagihanDenda;
                            $loan = $bill?->peminjaman;
                        @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $payment->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">ID {{ $payment->id_anggota }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $loan?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Peminjaman #{{ $bill?->id_peminjaman ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $loan?->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }}</td>
                            <td class="px-5 py-4 font-bold">Rp {{ number_format((float) ($bill?->nominal_tagihan ?? 0), 0, ',', '.') }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">Rp {{ number_format((float) $payment->jumlah_pembayaran, 0, ',', '.') }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ ucfirst($payment->metode_pembayaran) }} · {{ $payment->waktu_pembayaran?->format('d M Y H:i') ?? 'Waktu tidak tersedia' }}</p>
                                @if ($payment->bukti_pembayaran)
                                    <a class="mt-1 inline-block text-xs font-semibold text-blue-700 underline" href="{{ route('petugas.denda.pembayaran.bukti', $payment->id_pembayaran_denda, false) }}" target="_blank" rel="noopener">Lihat bukti pembayaran</a>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('petugas.denda.pembayaran.verifikasi', $payment->id_pembayaran_denda) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status_pembayaran" value="diverifikasi">
                                        <button type="submit" class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Verifikasi</button>
                                    </form>
                                    <form method="POST" action="{{ route('petugas.denda.pembayaran.verifikasi', $payment->id_pembayaran_denda) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status_pembayaran" value="ditolak">
                                        <input type="text" name="catatan_petugas" required maxlength="255" placeholder="Alasan penolakan" class="mb-2 w-40 rounded-lg border-slate-200 px-2 py-1 text-xs">
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada pembayaran yang menunggu verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pembayaranMenungguVerifikasi->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $pembayaranMenungguVerifikasi->links() }}</div>
        @endif
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5">
            <div>
                <h2 class="text-base font-extrabold">Daftar tagihan</h2>
                <p class="mt-1 text-sm text-slate-500">Nominal dan status mengikuti catatan tagihan di database.</p>
            </div>
            <form method="GET" action="{{ route('petugas.denda') }}" class="flex flex-wrap gap-2">
                <label class="sr-only" for="cari-tagihan">Cari tagihan</label>
                <input id="cari-tagihan" type="search" name="q" value="{{ $search }}" placeholder="Cari anggota, buku, ID..."
                       class="w-56 rounded-xl border-0 bg-[#EEF1F8] px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <label class="sr-only" for="status-tagihan">Status tagihan</label>
                <select id="status-tagihan" name="status" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua status</option>
                    <option value="belum_lunas" @selected($selectedStatus === 'belum_lunas')>Belum lunas</option>
                    <option value="lunas" @selected($selectedStatus === 'lunas')>Lunas</option>
                    <option value="tidak ada" @selected($selectedStatus === 'tidak ada')>Tidak ada denda</option>
                </select>
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[58rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Anggota</th>
                        <th scope="col" class="px-5 py-3">Peminjaman / buku</th>
                        <th scope="col" class="px-5 py-3">Jatuh tempo</th>
                        <th scope="col" class="px-5 py-3">Nominal tagihan</th>
                        <th scope="col" class="px-5 py-3">Status tagihan</th>
                        <th scope="col" class="px-5 py-3">Status pembayaran</th>
                        <th scope="col" class="px-5 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tagihan as $bill)
                        @php $loan = $bill->peminjaman; @endphp
                        <tr class="align-top">
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $bill->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">ID {{ $bill->id_anggota }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-semibold">{{ $loan?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Peminjaman #{{ $bill->id_peminjaman }}</p>
                            </td>
                            <td class="px-5 py-4">{{ $loan?->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }}</td>
                            <td class="px-5 py-4 font-bold">Rp {{ number_format((float) $bill->nominal_tagihan, 0, ',', '.') }}</td>
                            <td class="px-5 py-4">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $bill->status_tagihan === 'lunas',
                                    'bg-amber-50 text-amber-700' => $bill->status_tagihan !== 'lunas',
                                ])>{{ str_replace('_', ' ', ucfirst($bill->status_tagihan)) }}</span>
                            </td>
                            <td class="px-5 py-4">
                                @forelse ($bill->pembayaran as $payment)
                                    <p class="font-medium">{{ str_replace('_', ' ', ucfirst($payment->status_pembayaran)) }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Rp {{ number_format((float) $payment->jumlah_pembayaran, 0, ',', '.') }} · {{ ucfirst($payment->metode_pembayaran) }}</p>
                                @empty
                                    <span class="text-slate-500">Belum ada pembayaran</span>
                                @endforelse
                            </td>
                            <td class="px-5 py-4">
                                <a href="{{ route('petugas.denda.show', $bill->id_tagihan_denda) }}" class="font-semibold text-blue-700 hover:underline">Lihat detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada tagihan yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tagihan->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $tagihan->links() }}</div>
        @endif
    </section>
@endsection
