
@extends('petugas.layout')

@section('title', 'Denda')

@php
    use App\Models\Pengembalian;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Str;

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    $petugasLogin = auth('petugas')->user();
    $namaPetugas = $petugasLogin->nama_petugas ?? 'Petugas';
    $shiftPetugas = ucfirst(strtolower($petugasLogin->shift ?? '-'));

    /*
    |--------------------------------------------------------------------------
    | Ambil data denda dari database
    |--------------------------------------------------------------------------
    | Nominal denda dan status pembayaran mengikuti database.
    | Tidak menggunakan tarif contoh Rp2.000/hari.
    */

    $denda = collect();

    if (Schema::hasTable('pengembalian')) {
        $denda = Pengembalian::query()
            ->with(['peminjaman.anggota', 'peminjaman.buku'])
            ->where('denda', '>', 0)
            ->orderByDesc('id_pengembalian')
            ->get()
            ->map(function ($return) {
                $loan = $return->peminjaman;

                $lateDays = 0;

                if (
                    $loan &&
                    $loan->tgl_jatuh_tempo &&
                    $return->tgl_pengembalian
                ) {
                    $lateDays = max(
                        0,
                        (int) $loan->tgl_jatuh_tempo
                            ->diffInDays($return->tgl_pengembalian, false)
                    );
                }

                return [
                    'id' => 'DND-' . $return->id_pengembalian,
                    'ref' => 'KMB-' . $return->id_pengembalian,
                    'nama' => $loan?->anggota?->nama_anggota ?? 'Anggota',
                    'id_anggota' => $loan?->anggota?->id_anggota ?? '-',
                    'buku' => $loan?->buku?->judul_buku ?? 'Judul tidak tersedia',
                    'tempo' => $loan?->tgl_jatuh_tempo
                        ? $loan->tgl_jatuh_tempo->format('d M Y')
                        : '-',
                    'tanggal_kembali' => $return->tgl_pengembalian
                        ? $return->tgl_pengembalian->format('d M Y')
                        : '-',
                    'telat' => $lateDays,
                    'nominal' => (float) $return->denda,
                    'status' => $return->status_denda,
                    'lunas' => $return->status_denda === Pengembalian::DENDA_LUNAS,
                    'status_pengembalian' => $return->status_pengembalian,
                ];
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Ringkasan denda
    |--------------------------------------------------------------------------
    */

    $dendaBelumBayar = $denda->where('status', Pengembalian::DENDA_BELUM_DIBAYAR);

    $dendaLunas = $denda->where('status', Pengembalian::DENDA_LUNAS);

    $totalBelumBayar = $dendaBelumBayar->sum('nominal');
    $totalLunas = $dendaLunas->sum('nominal');

    $jumlahBelumBayar = $dendaBelumBayar->count();
    $jumlahLunas = $dendaLunas->count();

    $anggotaTertahan = $dendaBelumBayar
        ->pluck('id_anggota')
        ->filter()
        ->unique()
        ->count();

    $jumlahSemua = $denda->count();

    $alur = [
        [
            'Hitung denda',
            'Nominal denda disimpan pada transaksi pengembalian. Halaman ini menampilkan nominal yang tercatat di database.',
            'Modul pengembalian',
            'Sumber: tabel pengembalian',
        ],
        [
            'Catat tunggakan',
            'Denda dengan status belum dibayar ditampilkan sebagai tunggakan anggota.',
            'Status: belum dibayar',
            'Perlu penyelesaian pembayaran',
        ],
        [
            'Pembayaran kasir',
            'Pembayaran perlu diproses melalui backend kasir agar status denda dapat diperbarui secara konsisten.',
            'Modul pembayaran',
            'Menunggu integrasi backend',
        ],
        [
            'Pelunasan',
            'Setelah pembayaran berhasil dicatat, status denda dapat berubah menjadi lunas.',
            'Status: lunas',
            'Mengikuti data database',
        ],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Manajemen Denda
            </h1>

            <p class="mt-1 text-sm text-slate-600">
                Rekap nominal denda, status pembayaran, dan tunggakan anggota berdasarkan transaksi pengembalian.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100">
                @include('petugas.partials.icon', ['name' => 'user', 'class' => 'size-4'])
                {{ $namaPetugas }}
            </span>

            <span class="inline-flex items-center rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700">
                Shift {{ $shiftPetugas }}
            </span>
        </div>
    </div>

    {{-- Ringkasan --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan denda">
        @include('petugas.partials.stat-card', [
            'label' => 'Total denda belum dibayar',
            'value' => $rp($totalBelumBayar),
            'icon' => 'borrow',
            'tone' => 'red',
            'valueTone' => 'text-red-600',
            'footer' => $jumlahBelumBayar . ' transaksi menunggak',
            'footerTone' => 'text-red-600',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Total denda lunas',
            'value' => $rp($totalLunas),
            'icon' => 'cash',
            'tone' => 'blue',
            'valueTone' => 'text-blue-700',
            'footer' => $jumlahLunas . ' transaksi sudah lunas',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Jumlah transaksi denda',
            'value' => $jumlahSemua,
            'suffix' => 'Transaksi',
            'icon' => 'receipt',
            'tone' => 'blue',
            'footer' => 'Berdasarkan catatan pengembalian',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Anggota dengan tunggakan',
            'value' => $anggotaTertahan,
            'suffix' => 'Anggota',
            'icon' => 'ban',
            'tone' => 'amber',
            'footer' => 'Memiliki denda belum dibayar',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Daftar denda --}}
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar denda anggota</h2>

            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div data-tabs="#tabel-denda" class="flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach ([
                        'all' => 'Semua (' . $jumlahSemua . ')',
                        'belum' => 'Belum dibayar (' . $jumlahBelumBayar . ')',
                        'lunas' => 'Lunas (' . $jumlahLunas . ')',
                    ] as $nilai => $label)
                        <button
                            type="button"
                            data-tab="{{ $nilai }}"
                            data-active="{{ $nilai === 'all' ? 'true' : 'false' }}"
                            aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                            class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <label class="relative min-w-48 flex-1">
                    <span class="sr-only">Cari ID denda, anggota, atau judul buku</span>

                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                        @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                    </span>

                    <input
                        type="search"
                        data-search="#tabel-denda"
                        placeholder="Cari ID denda, anggota, judul buku..."
                        class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                    >
                </label>

                <p class="flex w-full items-center gap-2 text-xs text-slate-500">
                    @include('petugas.partials.icon', ['name' => 'clock', 'class' => 'size-4'])
                    Data diambil dari transaksi pengembalian.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <h2 class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-5 text-blue-700'])
                        Daftar Denda Anggota
                    </h2>

                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">
                        {{ $jumlahSemua }} data
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table id="tabel-denda" class="w-full min-w-[50rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID Denda</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Buku</th>
                                <th scope="col" class="px-3 py-3">Nominal</th>
                                <th scope="col" class="px-3 py-3">Status Denda</th>
                                <th scope="col" class="px-3 py-3 pr-5">Tanggal</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($denda as $d)
                                <tr
                                    class="align-top"
                                    data-cat="{{ $d['lunas'] ? 'lunas' : 'belum' }}"
                                >
                                    <td class="px-5 py-4">
                                        <p class="font-mono text-xs font-bold">
                                            {{ $d['id'] }}
                                        </p>

                                        <p class="font-mono text-xs text-slate-500">
                                            Ref: {{ $d['ref'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold">
                                            {{ $d['nama'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ID Anggota: {{ $d['id_anggota'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="max-w-56 font-medium leading-snug">
                                            {{ $d['buku'] }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Jatuh tempo: {{ $d['tempo'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            Dikembalikan: {{ $d['tanggal_kembali'] }}
                                        </p>

                                        @if ($d['telat'] > 0)
                                            <span class="mt-2 inline-flex rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">
                                                Terlambat {{ $d['telat'] }} hari
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-bold {{ $d['lunas'] ? 'text-[#0F1B3D]' : 'text-red-600' }}">
                                            {{ $rp($d['nominal']) }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        @if ($d['lunas'])
                                            <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">
                                                @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-3.5'])
                                                Lunas
                                            </span>
                                        @elseif ($d['status'] === Pengembalian::DENDA_BELUM_DIBAYAR)
                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                                                <span class="size-1.5 rounded-full bg-red-600"></span>
                                                Belum dibayar
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                                {{ ucfirst($d['status'] ?? 'Tidak ada') }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-4 pr-5 text-xs text-slate-500">
                                        {{ $d['tanggal_kembali'] }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                                            @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-6'])
                                        </div>

                                        <p class="mt-3 font-semibold text-slate-700">
                                            Belum ada data denda
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Data denda akan muncul setelah tercatat pada transaksi pengembalian.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    Menampilkan {{ $denda->count() }} catatan denda.
                </div>
            </div>
        </section>

        {{-- Informasi pembayaran --}}
        <aside class="min-w-0 space-y-6" aria-label="Informasi pembayaran denda">
            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="bg-blue-700 p-5 text-white">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-white/15">
                            @include('petugas.partials.icon', ['name' => 'cash'])
                        </span>

                        <div>
                            <h2 class="text-base font-extrabold">
                                Informasi Pembayaran
                            </h2>

                            <p class="text-xs text-blue-100">
                                Modul pembayaran denda
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5">
                    <div class="rounded-xl bg-red-50 p-4">
                        <p class="text-xs font-semibold text-red-700">
                            Total tunggakan
                        </p>

                        <p class="mt-1 text-2xl font-extrabold text-red-600">
                            {{ $rp($totalBelumBayar) }}
                        </p>

                        <p class="mt-1 text-xs text-red-600">
                            {{ $jumlahBelumBayar }} transaksi belum dibayar
                        </p>
                    </div>

                    <div class="rounded-xl bg-blue-50 p-4">
                        <p class="text-xs font-semibold text-blue-700">
                            Total sudah lunas
                        </p>

                        <p class="mt-1 text-2xl font-extrabold text-blue-700">
                            {{ $rp($totalLunas) }}
                        </p>

                        <p class="mt-1 text-xs text-blue-700">
                            {{ $jumlahLunas }} transaksi lunas
                        </p>
                    </div>

                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="flex items-start gap-2">
                            @include('petugas.partials.icon', ['name' => 'info', 'class' => 'size-5 shrink-0 text-amber-700'])

                            <div>
                                <p class="text-sm font-bold text-amber-900">
                                    Pembayaran belum terhubung
                                </p>

                                <p class="mt-1 text-xs leading-relaxed text-amber-800">
                                    Form kasir dan konfirmasi pelunasan belum diaktifkan karena endpoint pembayaran perlu disediakan oleh backend denda.
                                </p>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs leading-relaxed text-slate-500">
                        Status pembayaran pada halaman ini hanya mengikuti kolom
                        <b>status_denda</b> dari tabel pengembalian. Halaman ini tidak mengubah status pembayaran atau stok buku.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <h2 class="flex items-center gap-2 text-sm font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                    Ringkasan Status Denda
                </h2>

                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-[#EEF3FC] p-3">
                        <span class="text-xs font-medium text-slate-600">
                            Belum dibayar
                        </span>

                        <b class="text-sm text-red-600">
                            {{ $jumlahBelumBayar }} transaksi
                        </b>
                    </div>

                    <div class="flex items-center justify-between gap-3 rounded-xl bg-[#EEF3FC] p-3">
                        <span class="text-xs font-medium text-slate-600">
                            Lunas
                        </span>

                        <b class="text-sm text-blue-700">
                            {{ $jumlahLunas }} transaksi
                        </b>
                    </div>

                    <div class="flex items-center justify-between gap-3 rounded-xl bg-[#EEF3FC] p-3">
                        <span class="text-xs font-medium text-slate-600">
                            Total catatan
                        </span>

                        <b class="text-sm text-slate-800">
                            {{ $jumlahSemua }} transaksi
                        </b>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    {{-- SOP --}}
    <section class="mt-6 rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-alur">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="judul-alur" class="text-base font-extrabold">
                    Alur Integrasi Hitung &amp; Bayar Denda
                </h2>

                <p class="text-xs text-slate-500">
                    Gambaran prosedur pengelolaan denda pada sistem perpustakaan.
                </p>
            </div>

            <span class="text-xs font-semibold text-blue-700">
                Terhubung dengan modul pengembalian
            </span>
        </div>

        <ol class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($alur as $n => [$judul, $isi, $label, $akhir])
                <li class="flex flex-col rounded-xl bg-[#EEF3FC] p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="grid size-7 place-items-center rounded-lg bg-blue-700 text-xs font-bold text-white">
                            {{ $n + 1 }}
                        </span>

                        <span class="rounded-md bg-white px-2 py-1 font-mono text-[11px] font-semibold text-blue-700">
                            {{ $label }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm font-bold">
                        {{ $judul }}
                    </p>

                    <p class="mt-1 flex-1 text-xs text-slate-600">
                        {{ $isi }}
                    </p>

                    <p class="mt-3 border-t border-slate-200 pt-3 text-xs font-semibold text-blue-700">
                        {{ $akhir }}
                    </p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
