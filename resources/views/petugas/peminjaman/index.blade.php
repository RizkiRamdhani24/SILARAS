@extends('petugas.layout')

@section('title', 'Validasi Peminjaman')

@php
    $petugas = auth('petugas')->user();
    $namaPetugas = $petugas->nama_petugas ?? 'Petugas';
    $shiftPetugas = $petugas->shift ?? '-';

    $antrean = $peminjaman->getCollection()->map(function ($item) {
        return [
            'id' => $item->id_peminjaman,
            'waktu' => $item->tgl_peminjaman?->format('d M Y') ?? '-',
            'nama' => $item->anggota->nama_anggota ?? '-',
            'id_anggota' => $item->anggota->id_anggota ?? '-',
            'judul' => $item->buku->judul_buku ?? '-',
            'kode' => 'BK-' . ($item->buku->id_buku ?? '-'),
            'rak' => $item->buku->rak ?? '-',
            'stok' => $item->buku->stock ?? 0,
        ];
    })->all();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Validasi &amp; Proses Peminjaman Buku
            </h1>

            <p class="mt-1 text-sm text-slate-600">
                Kelola pengajuan peminjaman daring dan periksa ketersediaan stok buku.
            </p>
        </div>

        <a href="#loket"
           class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
            @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4'])
            Pinjam Loket Fisik
        </a>
    </div>

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Pengajuan gagal diproses:</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Ringkasan --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
             aria-label="Ringkasan peminjaman">

        @include('petugas.partials.stat-card', [
            'label' => 'Antrean validasi',
            'value' => $totalAntrean,
            'suffix' => 'Pengajuan',
            'icon' => 'borrow',
            'tone' => 'amber',
            'footer' => 'Data dari database',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Peminjaman hari ini',
            'value' => $totalHariIni,
            'suffix' => 'Buku diproses',
            'icon' => 'book',
            'tone' => 'blue',
            'footer' => 'Berdasarkan tanggal peminjaman',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Stok kritis',
            'value' => $totalStokKritis,
            'suffix' => 'Judul buku',
            'icon' => 'alert',
            'tone' => 'red',
            'labelTone' => 'text-red-600',
            'footer' => 'Stok ≤ 1 eksemplar',
            'footerTone' => 'text-red-600',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Masa peminjaman',
            'value' => '7',
            'suffix' => 'Hari',
            'icon' => 'shield',
            'tone' => 'blue',
            'footer' => 'Dihitung sejak disetujui petugas',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_20rem]">

        {{-- Antrean validasi --}}
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">

            {{-- Pencarian --}}
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <form method="GET"
                      action="{{ route('petugas.peminjaman.index') }}"
                      class="relative min-w-52 flex-1">

                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                        @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                    </span>

                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari nama anggota atau judul buku..."
                        class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    >
                </form>

                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="size-2 rounded-full bg-blue-600"></span>
                    Data antrean berasal dari database.
                </p>
            </div>

            {{-- Tabel antrean --}}
            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-100">

                <div class="flex flex-wrap items-center justify-between gap-3 bg-[#EEF3FC] px-5 py-4">
                    <h2 id="judul-antrean" class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5 text-blue-700'])
                        Antrean Pengajuan Menunggu Validasi Petugas
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[68rem] text-left text-sm">
                        <thead class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr class="border-b border-slate-100">
                                <th scope="col" class="px-3 py-3 pl-5">ID &amp; Waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota pemohon</th>
                                <th scope="col" class="px-3 py-3">Buku diajukan &amp; stok</th>
                                <th scope="col" class="px-3 py-3">Masa pinjam</th>
                                <th scope="col" class="px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($antrean as $i => $p)
                                @php
                                    $habis = $p['stok'] < 1;
                                @endphp

                                <tr class="align-top">

                                    {{-- ID dan waktu --}}
                                    <td class="px-3 py-4 pl-5">
                                        <p class="font-bold text-blue-700">
                                            #{{ $p['id'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $p['waktu'] }} • Daring
                                        </p>

                                        <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                            Menunggu validasi
                                        </span>
                                    </td>

                                    {{-- Anggota --}}
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">
                                            {{ $p['nama'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ID Anggota: {{ $p['id_anggota'] }}
                                        </p>
                                    </td>

                                    {{-- Buku --}}
                                    <td class="px-3 py-4">
                                        <div class="flex gap-3">
                                            @include('petugas.partials.cover', ['i' => $i])

                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">
                                                    {{ $p['judul'] }}
                                                </p>

                                                <p class="text-xs text-slate-500">
                                                    {{ $p['kode'] }} • Rak {{ $p['rak'] }}
                                                </p>

                                                <span @class([
                                                    'mt-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                                                    'bg-red-100 text-red-700' => $habis,
                                                    'bg-amber-100 text-amber-800' => !$habis && $p['stok'] <= 1,
                                                    'bg-emerald-50 text-emerald-700' => $p['stok'] > 1,
                                                ])>
                                                    @if ($habis)
                                                        Stok: 0 (habis)
                                                    @elseif ($p['stok'] <= 1)
                                                        Stok kritis: {{ $p['stok'] }} eks
                                                    @else
                                                        Tersedia (stok: {{ $p['stok'] }})
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Masa pinjam --}}
                                    <td class="px-3 py-4 text-xs text-slate-600">
                                        <p class="font-semibold text-blue-700">
                                            7 hari
                                        </p>
                                        <p class="mt-1">
                                            Dihitung setelah persetujuan
                                        </p>
                                    </td>

                                    {{-- Aksi --}}
                                    <td class="px-3 py-4">
                                        <div class="flex min-w-36 flex-col gap-2">

                                            <form
                                                action="{{ route('petugas.peminjaman.approve', $p['id']) }}"
                                                method="POST"
                                                onsubmit="return confirm('Setujui pengajuan peminjaman buku ini? Stok akan berkurang satu dan masa peminjaman berlaku 7 hari.')"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    @disabled($habis)
                                                    class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                                                >
                                                    Setujui
                                                </button>
                                            </form>

                                            <form
                                                action="{{ route('petugas.peminjaman.reject', $p['id']) }}"
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menolak pengajuan peminjaman ini? Stok buku tidak akan berubah.')"
                                            >
                                                @csrf

                                                <button
                                                    type="submit"
                                                    class="w-full rounded-lg bg-red-50 px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-100"
                                                >
                                                    Tolak
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            @include('petugas.partials.icon', [
                                                'name' => 'book',
                                                'class' => 'size-10 text-slate-300',
                                            ])

                                            <p class="mt-3 font-semibold text-slate-700">
                                                Belum ada pengajuan peminjaman
                                            </p>

                                            <p class="mt-1 text-sm text-slate-500">
                                                Data pengajuan yang menunggu validasi akan muncul di sini.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @include('petugas.partials.pagination', [
                    'from' => $peminjaman->firstItem() ?? 0,
                    'to' => $peminjaman->lastItem() ?? 0,
                    'total' => $peminjaman->total(),
                    'noun' => 'pengajuan menunggu',
                    'last' => $peminjaman->lastPage(),
                ])
            </div>

            {{-- Informasi alur --}}
            <div class="rounded-2xl bg-[#EEF3FC] p-5 text-sm text-slate-600">
                <p class="font-bold text-blue-800">
                    Alur persetujuan peminjaman
                </p>

                <p class="mt-1">
                    Ketika pengajuan disetujui, sistem akan mencatat petugas,
                    mengurangi stok satu eksemplar, dan menetapkan tanggal
                    jatuh tempo tujuh hari setelah persetujuan.
                    Jika ditolak, stok buku tidak berubah.
                </p>
            </div>
        </section>

        {{-- Panel samping --}}
        <aside class="min-w-0 space-y-6" aria-label="Loket dan log validasi">

            {{-- Loket fisik --}}
            <section id="loket"
                     class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">

                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold">
                            Loket Pinjam Cepat Fisik
                        </h2>

                        <p class="text-xs text-slate-500">
                            Layanan meja sirkulasi langsung
                        </p>
                    </div>

                    <span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                        Shift {{ $shiftPetugas }}
                    </span>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="font-semibold text-slate-700">
                        Layanan loket belum tersedia.
                    </p>

                    <p class="mt-1 text-xs">
                        Fitur peminjaman langsung akan dikembangkan terpisah
                        dari proses pengajuan daring.
                    </p>
                </div>
            </section>

            {{-- Log validasi --}}
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100"
                     aria-labelledby="judul-log">

                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                        @include('petugas.partials.icon', [
                            'name' => 'history',
                            'class' => 'size-5 text-blue-700',
                        ])

                        Log validasi {{ \Illuminate\Support\Str::words($namaPetugas, 1, '') }}
                    </h2>

                    <span class="text-xs text-slate-500">
                        Hari ini
                    </span>
                </div>

                <p class="mt-4 rounded-xl bg-slate-50 p-4 text-center text-xs text-slate-500">
                    Log aktivitas belum tersedia.
                </p>

                <a href="{{ url('/petugas/transaksi') }}"
                   class="mt-4 block rounded-xl bg-[#EEF3FC] px-3 py-2.5 text-center text-xs font-semibold text-blue-700 hover:bg-blue-100">
                    Buka seluruh riwayat transaksi sirkulasi →
                </a>
            </section>
        </aside>
    </div>
@endsection
