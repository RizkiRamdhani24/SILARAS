@extends('layouts.anggota')

@section('title', 'Tagihan Denda | SILARAS')

@section('content')
    <section class="mx-auto max-w-5xl space-y-5 px-4 py-8">
        <header>
            <h1 class="text-2xl font-bold">Tagihan Denda</h1>
            <p class="mt-1 text-slate-600">Setiap tagihan memuat rincian denda untuk satu peminjaman.</p>
        </header>

        @foreach (['success', 'error'] as $type)
            @if (session($type))
                <div role="{{ $type === 'error' ? 'alert' : 'status' }}" class="rounded-lg bg-slate-50 p-4">{{ session($type) }}</div>
            @endif
        @endforeach

        @forelse ($tagihan as $bill)
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="font-semibold">{{ $bill->peminjaman?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</h2>
                        <p class="text-sm text-slate-500">Peminjaman #{{ $bill->id_peminjaman }}</p>
                        <p class="mt-2 text-sm">Terlambat Rp {{ number_format((float) $bill->nominal_terlambat, 0, ',', '.') }} · Rusak Rp {{ number_format((float) $bill->nominal_rusak, 0, ',', '.') }} · Hilang Rp {{ number_format((float) $bill->nominal_hilang, 0, ',', '.') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold">Rp {{ number_format((float) $bill->nominal_tagihan, 0, ',', '.') }}</p>
                        <p class="text-sm">{{ str_replace('_', ' ', ucfirst($bill->status_tagihan)) }}</p>
                        <a href="{{ route('anggota.denda.show', $bill->id_tagihan_denda) }}" class="mt-2 inline-block text-sm font-semibold text-blue-700 underline">Detail / bayar</a>
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-xl bg-white p-5 text-slate-600 shadow-sm">Belum ada tagihan denda.</p>
        @endforelse
    </section>
@endsection
