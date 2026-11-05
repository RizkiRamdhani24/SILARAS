
@extends('petugas.layout')

@section('title', 'Dashboard Petugas')

@php
    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    $petugasLogin = auth('petugas')->user();
    $namaPetugas = $petugasLogin?->nama_petugas ?? 'Petugas';
    $shiftPetugas = $petugasLogin?->shift
        ? ucfirst(strtolower($petugasLogin->shift))
        : '-';

    $adaPeminjaman = \Illuminate\Support\Facades\Schema::hasTable('peminjaman');
    $adaBuku = \Illuminate\Support\Facades\Schema::hasTable('buku');
    $adaAnggota = \Illuminate\Support\Facades\Schema::hasTable('anggota');
    $adaPengembalian = \Illuminate\Support\Facades\Schema::hasTable('pengembalian');

    $pendingLoans = $adaPeminjaman
        ? \App\Models\Peminjaman::where(
            'status_peminjaman',
            \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI
        )->count()
        : 0;

    $totalBooks = $adaBuku ? \App\Models\Buku::count() : 0;
    $totalCopies = $adaBuku ? (int) \App\Models\Buku::sum('stock') : 0;
    $memberCount = $adaAnggota ? \App\Models\Anggota::count() : 0;

    $lateDenda = $adaPengembalian
        ? (float) \App\Models\Pengembalian::where(
            'status_denda',
            \App\Models\Pengembalian::DENDA_BELUM_DIBAYAR
        )->sum('denda')
        : 0;

    $criticalBooks = $adaBuku
        ? \App\Models\Buku::where('stock', '<=', 1)
            ->orderBy('stock')
            ->orderBy('judul_buku')
            ->take(3)
            ->get()
        : collect();

    $antrean = $adaPeminjaman
        ? \App\Models\Peminjaman::with(['anggota', 'buku'])
            ->where(
                'status_peminjaman',
                \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI
            )
            ->orderByDesc('tgl_peminjaman')
            ->limit(3)
            ->get()
            ->map(fn ($loan) => [
                'id' => 'PMJ-' . $loan->id_peminjaman,
                'nama' => $loan->anggota?->nama_anggota ?? 'Anggota',
                'id_anggota' => $loan->anggota?->id_anggota ?? '-',
                'judul' => $loan->buku?->judul_buku ?? 'Judul belum ada',
                'isbn' => $loan->buku?->isbn ?? '-',
                'rak' => $loan->buku?->rak ?? '-',
                'pinjam' => $loan->tgl_peminjaman?->format('d M Y') ?? '-',
                'tempo' => $loan->tgl_jatuh_tempo?->format('d M Y') ?? '-',
            ])
        : collect();

    $kembali = $adaPengembalian
        ? \App\Models\Pengembalian::with([
            'peminjaman.anggota',
            'peminjaman.buku',
        ])
            ->orderByDesc('tgl_pengembalian')
            ->limit(2)
            ->get()
            ->map(function ($return) {
                $daysLate = 0;
                $loan = $return->peminjaman;

                if ($loan && $loan->tgl_jatuh_tempo && $return->tgl_pengembalian) {
                    $daysLate = max(
                        0,
                        $loan->tgl_jatuh_tempo->diffInDays(
                            $return->tgl_pengembalian,
                            false
                        )
                    );
                }

                return [
                    'id' => 'KMB-' . $return->id_pengembalian,
                    'telat' => $daysLate,
                    'judul' => $loan?->buku?->judul_buku ?? 'Judul belum ada',
                    'peminjam' => ($loan?->anggota?->nama_anggota ?? 'Anggota')
                        . ' (' . ($loan?->anggota?->id_anggota ?? '-') . ')',
                    'tempo' => $loan?->tgl_jatuh_tempo
                        ? $loan->tgl_jatuh_tempo->format('d M Y')
                        : '-',
                    'masuk' => $return->tgl_pengembalian
                        ? $return->tgl_pengembalian->format('d M Y')
                        : '-',
                    'denda' => (float) $return->denda,
                ];
            })
        : collect();

    $kritis = $criticalBooks->map(fn ($book) => [
        'judul' => $book->judul_buku,
        'info' => ($book->pengarang ?? 'Penulis tidak tersedia')
            . ' • ' . ($book->penerbit ?? 'Penerbit tidak tersedia'),
        'kode' => 'BK-' . $book->id_buku,
        'stok' => (int) $book->stock,
    ])->all();

    $log = [
        [
            'jam' => 'Sekarang',
            'isi' => 'Antrean peminjaman menunggu validasi: '
                . $pendingLoans . ' transaksi.',
            'bold' => $namaPetugas,
            'meta' => 'petugas / dashboard',
        ],
        [
            'jam' => 'Ringkasan',
            'isi' => 'Denda belum dibayar tercatat: '
                . $rp($lateDenda) . '.',
            'bold' => null,
            'meta' => 'status_denda',
        ],
        [
            'jam' => 'Ringkasan',
            'isi' => 'Jumlah anggota terdaftar: '
                . $memberCount . ' orang.',
            'bold' => null,
            'meta' => 'anggota',
        ],
    ];
@endphp

@section('content')

    {{-- Banner sambutan --}}
    <section class="mt-6 rounded-3xl bg-gradient-to-br from-blue-700 to-blue-600 p-6 text-white shadow-sm sm:p-8" aria-labelledby="judul-sambutan">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="max-w-2xl">
                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1">
                        <span class="size-2 rounded-full bg-emerald-300"></span>
                        Petugas aktif
                    </span>

                    <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1">
                        @include('petugas.partials.icon', ['name' => 'clock', 'class' => 'size-3.5'])
                        Shift: {{ $shiftPetugas }}
                    </span>
                </div>

                <h1 id="judul-sambutan" class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">
                    Selamat bertugas, {{ $namaPetugas }}
                </h1>

                <p class="mt-2 text-sm text-blue-50">
                    Kelola transaksi sirkulasi, validasi peminjaman,
                    pengelolaan stok buku, serta verifikasi pengembalian.
                </p>
            </div>

            <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                @foreach ([
                    ['Kelola Buku', 'petugas/buku', 'book'],
                    ['Kelola Anggota', 'petugas/anggota', 'users'],
                    ['Kelola Denda', 'petugas/denda', 'cash'],
                    ['Proses Pinjam', 'petugas/peminjaman', 'borrow'],
                ] as [$label, $path, $ikon])
                    <a href="{{ url('/' . $path) }}"
                        class="flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-blue-800 shadow-sm hover:bg-blue-50">
                        @include('petugas.partials.icon', ['name' => $ikon, 'class' => 'size-4'])
                        {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan layanan">

        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Validasi pinjam</p>
                <span class="grid size-9 place-items-center rounded-lg bg-amber-50 text-amber-600">
                    @include('petugas.partials.icon', ['name' => 'borrow', 'class' => 'size-5'])
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold">
                {{ $pendingLoans }}
                <span class="text-sm font-medium text-slate-500">Menunggu petugas</span>
            </p>
            <p class="mt-3 inline-flex rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">
                Cek stok &amp; tunggakan
            </p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Proses kembali</p>
                <span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">
                    @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5'])
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold">
                {{ $kembali->count() }}
                <span class="text-sm font-medium text-slate-500">Data terakhir</span>
            </p>
            <p class="mt-3 text-xs text-slate-500">
                Dua pengembalian terakhir
            </p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Hitung denda</p>
                <span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">
                    @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-5'])
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold">{{ $rp($lateDenda) }}</p>
            <p class="mt-3 text-xs text-slate-500">
                Total denda yang belum dibayar
            </p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Kelola buku</p>
                <span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">
                    @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5'])
                </span>
            </div>
            <p class="mt-3 text-2xl font-extrabold">
                {{ number_format($totalBooks, 0, ',', '.') }}
                <span class="text-sm font-medium text-slate-500">Judul</span>
            </p>
            <p class="text-2xl font-extrabold">
                {{ number_format($totalCopies, 0, ',', '.') }}
                <span class="text-sm font-medium text-slate-500">Stok</span>
            </p>
            <p class="mt-2 flex items-center gap-1 text-xs font-medium text-amber-700">
                @include('petugas.partials.icon', ['name' => 'alert', 'class' => 'size-3.5'])
                {{ $criticalBooks->count() }} judul stok kritis (≤1)
            </p>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">Kelola anggota</p>
                <span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">
                    @include('petugas.partials.icon', ['name' => 'users', 'class' => 'size-5'])
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold">
                {{ number_format($memberCount, 0, ',', '.') }}
                <span class="text-sm font-medium text-slate-500">Terdaftar</span>
            </p>
            <p class="mt-3 text-xs text-slate-500">
                Jumlah anggota perpustakaan
            </p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">

        <div class="min-w-0 space-y-6">

            {{-- Antrean validasi --}}
            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-antrean">

                <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">
                            @include('petugas.partials.icon', ['name' => 'return'])
                        </span>

                        <div>
                            <h2 id="judul-antrean" class="text-base font-extrabold">
                                Antrean Validasi Peminjaman Buku
                            </h2>
                            <p class="text-xs text-slate-500">
                                Daftar pengajuan peminjaman yang menunggu validasi petugas.
                            </p>
                        </div>
                    </div>

                    <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        {{ $pendingLoans }} tertunda
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID pinjam</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Judul buku &amp; eksemplar</th>
                                <th scope="col" class="px-3 py-3">Durasi</th>
                                <th scope="col" class="px-3 py-3">Status</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($antrean as $p)
                                <tr class="align-top">
                                    <td class="px-5 py-4 font-bold text-blue-700">
                                        {{ $p['id'] }}
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $p['nama'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            ID Anggota: {{ $p['id_anggota'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $p['judul'] }}</p>
                                        <p class="text-xs text-slate-500">
                                            ISBN: {{ $p['isbn'] }} • Rak: {{ $p['rak'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4 text-xs">
                                        <p>{{ $p['pinjam'] }}</p>
                                        <p class="font-semibold text-amber-700">
                                            → {{ $p['tempo'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                            Menunggu validasi
                                        </span>
                                    </td>

                                    <td class="px-3 py-4 pr-5 text-right">
                                        <a href="{{ url('/petugas/peminjaman') }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                                            Lihat antrean
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center">
                                        <p class="font-semibold text-slate-600">
                                            Belum ada antrean peminjaman
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">
                                            Pengajuan peminjaman yang masuk akan muncul di sini.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Verifikasi pengembalian --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kembali">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">
                            @include('petugas.partials.icon', ['name' => 'return'])
                        </span>

                        <div>
                            <h2 id="judul-kembali" class="text-base font-extrabold">
                                Verifikasi Pengembalian &amp; Kalkulasi Denda
                            </h2>
                            <p class="text-xs text-slate-500">
                                Data pengembalian buku yang tercatat di database.
                            </p>
                        </div>
                    </div>

                    <span class="text-xs text-slate-500">
                        Dua data pengembalian terakhir
                    </span>
                </div>

                <ul class="mt-4 space-y-3">
                    @forelse ($kembali as $k)
                        <li class="flex flex-wrap items-center gap-4 rounded-2xl bg-[#EEF3FC] p-4">

                            @include('petugas.partials.cover', ['i' => 3, 'class' => 'h-12 w-10'])

                            <div class="min-w-0 flex-1 basis-56">
                                <p class="flex flex-wrap items-center gap-2 text-xs">
                                    <b class="text-blue-700">{{ $k['id'] }}</b>

                                    @if ($k['telat'] > 0)
                                        <span class="rounded-md bg-red-100 px-2 py-0.5 font-semibold text-red-700">
                                            Terlambat {{ $k['telat'] }} hari
                                        </span>
                                    @else
                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-700">
                                            Tepat waktu
                                        </span>
                                    @endif
                                </p>

                                <p class="font-bold">{{ $k['judul'] }}</p>

                                <p class="text-xs text-slate-500">
                                    Peminjam: {{ $k['peminjam'] }}
                                    • Jatuh tempo: {{ $k['tempo'] }}
                                    • Tgl masuk: {{ $k['masuk'] }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Kalkulasi denda
                                </p>
                                <p @class([
                                    'text-xl font-extrabold',
                                    'text-red-600' => $k['denda'] > 0,
                                ])>
                                    {{ $rp($k['denda']) }}
                                </p>
                            </div>

                            <a href="{{ url('/petugas/pengembalian') }}"
                                class="flex items-center gap-1.5 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-blue-700 ring-1 ring-blue-200 hover:bg-blue-50">
                                Lihat pengembalian
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-slate-50 p-6 text-center">
                            <p class="font-semibold text-slate-600">
                                Belum ada data pengembalian
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Data pengembalian akan muncul setelah transaksi tercatat.
                            </p>
                        </li>
                    @endforelse
                </ul>
            </section>

            {{-- Tren transaksi --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-tren">

                <div>
                    <h2 id="judul-tren" class="text-base font-extrabold">
                        Tren Transaksi Sirkulasi
                    </h2>
                    <p class="text-xs text-slate-500">
                        Grafik akan ditampilkan setelah data statistik transaksi tersedia.
                    </p>
                </div>

                <div class="mt-5 rounded-xl bg-slate-50 p-6 text-center">
                    <p class="text-sm font-semibold text-slate-600">
                        Statistik transaksi belum tersedia
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Bagian ini belum menggunakan data contoh.
                    </p>
                </div>
            </section>
        </div>

        {{-- Sidebar kanan --}}
        <aside class="min-w-0 space-y-6" aria-label="Stok kritis, shift, dan ringkasan">

            {{-- Stok kritis --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kritis">

                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-kritis" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'bell', 'class' => 'size-5 text-amber-600'])
                        Stok Buku Kritis
                    </h2>

                    <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">
                        Stok ≤ 1
                    </span>
                </div>

                <p class="mt-2 text-xs text-slate-500">
                    Buku dengan stok habis atau menipis.
                </p>

                <ul class="mt-4 space-y-3">
                    @forelse ($kritis as $i => $b)
                        <li class="flex items-center gap-3 rounded-xl bg-[#EEF3FC] p-3">

                            @include('petugas.partials.cover', ['i' => $i, 'class' => 'h-14 w-10'])

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">{{ $b['judul'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $b['info'] }}</p>

                                <p class="mt-1 text-xs">
                                    <span class="text-slate-500">{{ $b['kode'] }}</span>
                                    •
                                    <b class="text-red-600">
                                        Stok: {{ $b['stok'] }}{{ $b['stok'] == 0 ? ' (habis)' : '' }}
                                    </b>
                                </p>
                            </div>

                            <a href="{{ url('/petugas/buku') }}"
                                class="flex shrink-0 items-center gap-1 rounded-lg bg-blue-700 px-2.5 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                                Kelola
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-slate-50 p-5 text-center">
                            <p class="text-sm font-semibold text-slate-600">
                                Tidak ada stok kritis
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                Belum ada buku dengan stok ≤ 1.
                            </p>
                        </li>
                    @endforelse
                </ul>

                <a href="{{ url('/petugas/buku') }}"
                    class="mt-4 block text-center text-xs font-semibold text-blue-700 hover:underline">
                    Buka tabel lengkap kelola buku →
                </a>
            </section>

            {{-- Shift petugas --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-shift">

                <h2 id="judul-shift" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-5 text-blue-700'])
                    Petugas Bertugas
                </h2>

                <div class="mt-4 rounded-xl bg-[#EEF3FC] p-3">
                    <p class="flex items-center gap-2 font-semibold">
                        <span class="size-2 rounded-full bg-emerald-500"></span>
                        {{ $namaPetugas }}
                    </p>

                    <p class="mt-1 text-xs text-slate-600">
                        Shift: {{ $shiftPetugas }}
                    </p>
                </div>
            </section>

            {{-- Ringkasan sistem --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-aktivitas">

                <h2 id="judul-aktivitas" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                    Ringkasan Sistem
                </h2>

                <ul class="mt-4 space-y-4 text-sm">
                    @foreach ($log as $l)
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-blue-50 text-blue-700">
                                @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-4'])
                            </span>

                            <div>
                                <p>
                                    @if ($l['bold'])
                                        <b>{{ $l['bold'] }}</b>
                                    @endif
                                    {{ $l['isi'] }}
                                </p>

                                <p class="mt-0.5 font-mono text-xs text-slate-500">
                                    {{ $l['jam'] }} • {{ $l['meta'] }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
@endsection
