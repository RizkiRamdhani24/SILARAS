@extends('layouts.anggota')

@section('title', 'Notifikasi | SILARAS')

@section('content')
    <section class="mx-auto max-w-4xl space-y-5 px-4 py-8">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold">Notifikasi</h1>
                <p class="mt-1 text-slate-600">Informasi terbaru tentang peminjaman, pengembalian, dan denda.</p>
            </div>
            <a href="{{ route('anggota.dashboard') }}" class="text-sm font-semibold text-blue-700 underline">Kembali ke dashboard</a>
        </header>

        @forelse ($notifikasi as $notification)
            <form method="POST" action="{{ route('anggota.notifikasi.buka', $notification->id_notifikasi) }}">
                @csrf
                <button class="w-full rounded-xl border p-5 text-left shadow-sm transition hover:border-blue-300 hover:bg-blue-50 {{ $notification->is_dibaca ? 'border-slate-200 bg-white' : 'border-blue-200 bg-blue-50' }}" type="submit">
                    <span class="flex items-start justify-between gap-4">
                        <strong class="text-sm text-slate-900">{{ $notification->judul }}</strong>
                        @unless ($notification->is_dibaca)
                            <span class="rounded-full bg-blue-700 px-2 py-1 text-[10px] font-bold text-white">Baru</span>
                        @endunless
                    </span>
                    <span class="mt-2 block text-sm leading-6 text-slate-600">{{ $notification->isi }}</span>
                    <time class="mt-3 block text-xs text-slate-500" datetime="{{ $notification->created_at?->toIso8601String() }}">
                        {{ $notification->created_at?->format('d M Y H:i') }}
                    </time>
                </button>
            </form>
        @empty
            <p class="rounded-xl bg-white p-6 text-center text-sm text-slate-600 shadow-sm">Belum ada notifikasi.</p>
        @endforelse

        @if ($notifikasi->hasPages())
            <div>{{ $notifikasi->links() }}</div>
        @endif
    </section>
@endsection
