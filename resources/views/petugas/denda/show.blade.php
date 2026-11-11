@extends('petugas.layout')

@section('title', 'Detail Tagihan Denda')

@section('content')
    @php $loan = $tagihan->peminjaman; @endphp
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Detail Tagihan Denda</h1>
            <p class="mt-1 text-sm text-slate-600">Tagihan #{{ $tagihan->id_tagihan_denda }} · Peminjaman #{{ $tagihan->id_peminjaman }}</p>
        </div>
        <a href="{{ route('petugas.denda') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke daftar denda</a>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100 sm:p-6">
            <h2 class="text-base font-extrabold">Informasi tagihan</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</dt>
                    <dd class="mt-1 font-semibold">{{ $tagihan->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</dd>
                    <dd class="text-xs text-slate-500">ID {{ $tagihan->id_anggota }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Buku</dt>
                    <dd class="mt-1 font-semibold">{{ $loan?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal jatuh tempo</dt>
                    <dd class="mt-1">{{ $loan?->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nominal tagihan</dt>
                    <dd class="mt-1 text-lg font-extrabold text-blue-700">Rp {{ number_format((float) $tagihan->nominal_tagihan, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Status tagihan</dt>
                    <dd class="mt-1">{{ str_replace('_', ' ', ucfirst($tagihan->status_tagihan)) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Rincian denda</dt>
                    <dd class="mt-1 text-sm">Terlambat Rp {{ number_format((float) $tagihan->nominal_terlambat, 0, ',', '.') }}</dd>
                    <dd class="text-sm">Rusak Rp {{ number_format((float) $tagihan->nominal_rusak, 0, ',', '.') }}</dd>
                    <dd class="text-sm">Hilang Rp {{ number_format((float) $tagihan->nominal_hilang, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal tagihan</dt>
                    <dd class="mt-1">{{ $tagihan->tgl_tagihan?->format('d M Y') ?? 'Belum tersedia' }}</dd>
                </div>
                @if ($tagihan->keterangan)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Keterangan</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $tagihan->keterangan }}</dd>
                    </div>
                @endif
            </dl>
            @if ($tagihan->status_tagihan === \App\Models\TagihanDenda::STATUS_BELUM_LUNAS)
                <form method="POST" action="{{ route('petugas.denda.pembayaran.tunai', $tagihan->id_tagihan_denda) }}" class="mt-5">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-semibold text-white">Catat Pembayaran Tunai Penuh</button>
                </form>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="border-b border-slate-100 p-5">
                <h2 class="text-base font-extrabold">Riwayat pembayaran</h2>
                <p class="mt-1 text-sm text-slate-500">Status dan nominal mengikuti catatan pembayaran yang tersimpan.</p>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse ($tagihan->pembayaran as $payment)
                    <div class="space-y-3 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold">Rp {{ number_format((float) $payment->jumlah_pembayaran, 0, ',', '.') }} · {{ ucfirst($payment->metode_pembayaran) }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $payment->waktu_pembayaran?->format('d M Y H:i') ?? 'Waktu tidak tersedia' }}</p>
                            </div>
                            <span class="rounded-full bg-[#EEF3FC] px-2.5 py-1 text-xs font-semibold text-slate-700">{{ str_replace('_', ' ', ucfirst($payment->status_pembayaran)) }}</span>
                        </div>
                        @if ($payment->bukti_pembayaran)
                            <a class="text-sm font-semibold text-blue-700 underline" href="{{ route('petugas.denda.pembayaran.bukti', $payment->id_pembayaran_denda, false) }}" target="_blank" rel="noopener">Lihat bukti pembayaran</a>
                        @endif
                        @if ($payment->catatan_petugas)
                            <p class="text-sm text-slate-600">Catatan: {{ $payment->catatan_petugas }}</p>
                        @endif
                        @if ($payment->status_pembayaran === 'menunggu_verifikasi')
                            <form method="POST" action="{{ route('petugas.denda.pembayaran.verifikasi', $payment->id_pembayaran_denda) }}" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <label class="block text-xs font-semibold text-slate-600" for="catatan-{{ $payment->id_pembayaran_denda }}">Alasan penolakan (wajib saat menolak)</label>
                                <input id="catatan-{{ $payment->id_pembayaran_denda }}" type="text" name="catatan_petugas" maxlength="255" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-blue-600 focus:ring-blue-600">
                                <div class="flex flex-wrap gap-2">
                                    <button name="status_pembayaran" value="diverifikasi" formnovalidate class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">Verifikasi</button>
                                    <button name="status_pembayaran" value="ditolak" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Tolak</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500">Belum ada pembayaran untuk tagihan ini.</p>
                @endforelse
            </div>
        </section>
    </div>
@endsection
