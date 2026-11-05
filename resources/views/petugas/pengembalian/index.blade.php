```php
@extends('petugas.layout')

@section('title', 'Validasi Pengembalian')

@php
    use App\Models\Pengembalian;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Storage;
    use Carbon\Carbon;

    $tabelPengembalianAda = Schema::hasTable('pengembalian');

    $namaPetugas = auth('petugas')->user()?->nama_petugas ?? 'Petugas';

    // Ambil pengembalian yang menunggu validasi beserta data peminjaman.
    $antrean = $tabelPengembalianAda
        ? Pengembalian::query()
            ->with([
                'peminjaman.anggota',
                'peminjaman.buku',
                'petugas',
            ])
            ->where('status_pengembalian', 'menunggu validasi')
            ->latest('id_pengembalian')
            ->get()
            ->map(function ($return) {
                $loan = $return->peminjaman;
                $anggota = $loan?->anggota;
                $buku = $loan?->buku;

                $tanggalTempo = $loan?->tgl_jatuh_tempo
                    ? Carbon::parse($loan->tgl_jatuh_tempo)->startOfDay()
                    : null;

                $tanggalKembali = $return->tgl_pengembalian
                    ? Carbon::parse($return->tgl_pengembalian)->startOfDay()
                    : today();

                $tanggalAcuan = $return->tgl_pengembalian
                    ? $tanggalKembali
                    : today();

                $telat = $tanggalTempo
                    ? max(0, (int) $tanggalTempo->diffInDays($tanggalAcuan, false))
                    : 0;

                $lebihAwal = $tanggalTempo
                    && $return->tgl_pengembalian
                    && $tanggalKembali->lt($tanggalTempo);

                return [
                    'id' => 'KMB-' . $return->id_pengembalian,
                    'id_pengembalian' => $return->id_pengembalian,
                    'waktu' => $return->created_at?->format('d M Y, H:i') ?? '-',

                    'nama' => $anggota?->nama_anggota ?? 'Anggota tidak ditemukan',
                    'id_anggota' => $anggota?->id_anggota ?? '-',

                    'judul' => $buku?->judul_buku ?? 'Buku tidak ditemukan',
                    'kode' => $buku?->id_buku
                        ? 'BK-' . $buku->id_buku
                        : '-',

                    'tempo' => $tanggalTempo
                        ? $tanggalTempo->format('d M Y')
                        : '-',

                    'tanggal_kembali' => $return->tgl_pengembalian
                        ? Carbon::parse($return->tgl_pengembalian)->format('d M Y')
                        : '-',

                    'telat' => $telat,
                    'awal' => (bool) $lebihAwal,

                    'kondisi' => $return->kondisi_buku ?? 'belum diperiksa',
                    'denda' => (float) ($return->denda ?? 0),
                    'status_denda' => $return->status_denda ?? 'tidak ada',
                ];
            })
            ->all()
        : [];

    // Ringkasan data pengembalian dari database.
    $totalAntrean = $tabelPengembalianAda
        ? Pengembalian::where('status_pengembalian', 'menunggu validasi')->count()
        : 0;

    $totalDiterimaHariIni = $tabelPengembalianAda
        ? Pengembalian::where('status_pengembalian', 'selesai')
            ->whereDate('tgl_pengembalian', today())
            ->count()
        : 0;

    $totalDendaLunas = $tabelPengembalianAda
        ? (float) Pengembalian::where('status_denda', 'lunas')->sum('denda')
        : 0;

    $totalSelesai = $tabelPengembalianAda
        ? Pengembalian::where('status_pengembalian', 'selesai')->count()
        : 0;

    $totalTanpaDenda = $tabelPengembalianAda
        ? Pengembalian::where('status_pengembalian', 'selesai')
            ->where('denda', 0)
            ->count()
        : 0;

    $persentaseTanpaDenda = $totalSelesai > 0
        ? round(($totalTanpaDenda / $totalSelesai) * 100, 1)
        : 0;

    // SOP tetap ditampilkan sebagai panduan petugas.
    $sop = [
        [
            'Penerimaan dan pemeriksaan fisik',
            'Petugas memeriksa kondisi buku, termasuk halaman, sampul, dan kelengkapan buku.',
        ],
        [
            'Pemeriksaan keterlambatan',
            'Petugas memeriksa tanggal pengembalian dibandingkan dengan tanggal jatuh tempo.',
        ],
        [
            'Verifikasi denda',
            'Nominal denda mengikuti data transaksi pengembalian yang tersimpan di database.',
        ],
        [
            'Penyelesaian transaksi',
            'Setelah validasi, status pengembalian dan stok buku diperbarui melalui proses backend.',
        ],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Validasi Pengembalian Buku
            </h1>

            <p class="mt-1 text-sm text-slate-600">
                Periksa pengajuan pengembalian, kondisi fisik buku, keterlambatan,
                dan informasi denda anggota.
            </p>
        </div>

        <a
            href="{{ route('petugas.dashboard') }}"
            class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50"
        >
            @include('petugas.partials.icon', ['name' => 'dashboard', 'class' => 'size-4'])
            Kembali ke Dashboard
        </a>
    </div>

    @if (session('success'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Terjadi kesalahan.</p>

            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section
        class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan pengembalian"
    >
        @include('petugas.partials.stat-card', [
            'label' => 'Antrean pengembalian',
            'value' => number_format($totalAntrean, 0, ',', '.'),
            'suffix' => 'Pengajuan',
            'icon' => 'download',
            'tone' => 'blue',
            'footer' => 'Menunggu validasi petugas',
            'footerTone' => 'text-blue-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Buku diterima hari ini',
            'value' => number_format($totalDiterimaHariIni, 0, ',', '.'),
            'suffix' => 'Transaksi',
            'icon' => 'book',
            'tone' => 'blue',
            'footer' => 'Pengembalian yang selesai hari ini',
            'footerTone' => 'text-blue-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Total denda lunas',
            'value' => 'Rp ' . number_format($totalDendaLunas, 0, ',', '.'),
            'icon' => 'cash',
            'tone' => 'amber',
            'valueTone' => 'text-amber-700',
            'footer' => 'Akumulasi denda berstatus lunas',
            'footerTone' => 'text-amber-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Pengembalian tanpa denda',
            'value' => $persentaseTanpaDenda . '%',
            'suffix' => 'Dari transaksi selesai',
            'icon' => 'shield',
            'tone' => 'blue',
            'footer' => number_format($totalTanpaDenda, 0, ',', '.') . ' transaksi tanpa denda',
            'footerTone' => 'text-blue-700',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_21rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-52 flex-1">
                        <span class="sr-only">
                            Cari ID transaksi, anggota, atau judul buku
                        </span>

                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                            @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                        </span>

                        <input
                            type="search"
                            data-search="#tabel-kembali"
                            placeholder="Cari ID transaksi, anggota, judul buku..."
                            class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        >
                    </label>

                    <span class="rounded-xl bg-blue-50 px-3 py-2.5 text-xs font-semibold text-blue-700">
                        Menunggu validasi
                    </span>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <h2
                        id="judul-antrean"
                        class="flex items-center gap-2 text-sm font-bold"
                    >
                        @include('petugas.partials.icon', [
                            'name' => 'return',
                            'class' => 'size-5 text-blue-700',
                        ])

                        Antrean Pengembalian Buku
                    </h2>

                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        {{ count($antrean) }} pengajuan
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table
                        id="tabel-kembali"
                        class="w-full min-w-[55rem] text-left text-sm"
                    >
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="w-10 py-3 pl-5">
                                    <input
                                        type="checkbox"
                                        data-check-all="#tabel-kembali"
                                        class="size-4 rounded border-slate-300"
                                        aria-label="Pilih semua pengembalian"
                                    >
                                </th>

                                <th scope="col" class="px-3 py-3">
                                    ID dan waktu
                                </th>

                                <th scope="col" class="px-3 py-3">
                                    Anggota
                                </th>

                                <th scope="col" class="px-3 py-3">
                                    Buku dan kondisi
                                </th>

                                <th scope="col" class="px-3 py-3">
                                    Jatuh tempo
                                </th>

                                <th scope="col" class="px-3 py-3">
                                    Denda
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($antrean as $r)
                                <tr
                                    class="align-top"
                                    data-id="{{ $r['id'] }}"
                                    data-status="{{ $r['telat'] > 0 ? 'terlambat' : 'tepat waktu' }}"
                                >
                                    <td class="py-4 pl-5">
                                        <input
                                            type="checkbox"
                                            data-row-check
                                            class="size-4 rounded border-slate-300"
                                            aria-label="Pilih {{ $r['id'] }}"
                                        >
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-bold text-blue-700">
                                            {{ $r['id'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $r['waktu'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold">
                                            {{ $r['nama'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ID Anggota: {{ $r['id_anggota'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold leading-snug">
                                            {{ $r['judul'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $r['kode'] }}
                                        </p>

                                        @if ($r['kondisi'] === 'baik')
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                                @include('petugas.partials.icon', [
                                                    'name' => 'check-circle',
                                                    'class' => 'size-3.5',
                                                ])
                                                Kondisi baik
                                            </span>
                                        @elseif ($r['kondisi'] === 'rusak')
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                                @include('petugas.partials.icon', [
                                                    'name' => 'wrench',
                                                    'class' => 'size-3.5',
                                                ])
                                                Kondisi rusak
                                            </span>
                                        @elseif ($r['kondisi'] === 'hilang')
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                                @include('petugas.partials.icon', [
                                                    'name' => 'alert',
                                                    'class' => 'size-3.5',
                                                ])
                                                Buku hilang
                                            </span>
                                        @else
                                            <span class="mt-2 inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                                                Belum diperiksa
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-4 text-xs">
                                        <p class="text-slate-600">
                                            Tempo: {{ $r['tempo'] }}
                                        </p>

                                        @if ($r['telat'] > 0)
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-red-100 px-2 py-1 font-semibold text-red-700">
                                                @include('petugas.partials.icon', [
                                                    'name' => 'clock',
                                                    'class' => 'size-3.5',
                                                ])
                                                Terlambat {{ $r['telat'] }} hari
                                            </span>
                                        @elseif ($r['awal'])
                                            <span class="mt-2 inline-flex rounded-md bg-blue-50 px-2 py-1 font-semibold text-blue-700">
                                                Dikembalikan lebih awal
                                            </span>
                                        @else
                                            <span class="mt-2 inline-flex rounded-md bg-emerald-50 px-2 py-1 font-semibold text-emerald-700">
                                                Tepat waktu
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-3 py-4">
                                        @if ($r['denda'] > 0)
                                            <p class="font-bold text-red-600">
                                                Rp {{ number_format($r['denda'], 0, ',', '.') }}
                                            </p>
                                        @else
                                            <p class="font-bold text-blue-700">
                                                Rp 0
                                            </p>
                                        @endif

                                        @if ($r['status_denda'] === 'lunas')
                                            <span class="mt-1 inline-flex rounded-md bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">
                                                Lunas
                                            </span>
                                        @elseif ($r['status_denda'] === 'belum dibayar')
                                            <span class="mt-1 inline-flex rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                                Belum dibayar
                                            </span>
                                        @else
                                            <span class="mt-1 inline-flex rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                                Tidak ada denda
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-500">
                                            @include('petugas.partials.icon', [
                                                'name' => 'return',
                                                'class' => 'size-8',
                                            ])

                                            <p class="font-semibold">
                                                Belum ada antrean pengembalian
                                            </p>

                                            <p class="text-xs">
                                                Pengajuan pengembalian yang menunggu validasi akan muncul di sini.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                    Menampilkan {{ count($antrean) }} pengajuan pengembalian.
                </div>
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Informasi pengembalian">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-center gap-3">
                    <span class="grid size-10 place-items-center rounded-xl bg-blue-700 text-white">
                        @include('petugas.partials.icon', ['name' => 'return'])
                    </span>

                    <div>
                        <h2 class="text-sm font-extrabold leading-tight">
                            Informasi Validasi
                        </h2>

                        <p class="text-xs text-slate-500">
                            Proses pengembalian buku
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="rounded-xl bg-blue-50 p-3">
                        <p class="font-semibold text-blue-800">
                            Antrean online
                        </p>

                        <p class="mt-1 text-xs">
                            Pengajuan dari anggota akan masuk ke daftar antrean setelah tersimpan di database.
                        </p>
                    </div>

                    <div class="rounded-xl bg-[#EEF3FC] p-3">
                        <p class="font-semibold text-slate-800">
                            Pemeriksaan kondisi buku
                        </p>

                        <p class="mt-1 text-xs">
                            Kondisi buku dicatat sebagai baik, rusak, atau hilang sesuai hasil pemeriksaan petugas.
                        </p>
                    </div>

                    <div class="rounded-xl bg-[#EEF3FC] p-3">
                        <p class="font-semibold text-slate-800">
                            Informasi denda
                        </p>

                        <p class="mt-1 text-xs">
                            Denda dan status pembayarannya ditampilkan berdasarkan catatan transaksi pengembalian.
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                    <p class="font-bold">
                        Aksi validasi belum diaktifkan
                    </p>

                    <p class="mt-1">
                        Tombol konfirmasi akan dihubungkan setelah endpoint backend pengembalian tersedia.
                        Hal ini untuk mencegah perubahan stok dan status transaksi yang tidak konsisten.
                    </p>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="flex items-center gap-2 text-sm font-extrabold">
                        @include('petugas.partials.icon', [
                            'name' => 'user',
                            'class' => 'size-5 text-blue-700',
                        ])

                        Petugas Aktif
                    </h2>
                </div>

                <div class="mt-4 flex items-center gap-3 rounded-xl bg-[#EEF3FC] p-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-full bg-blue-700 text-sm font-bold text-white">
                        {{ strtoupper(substr($namaPetugas, 0, 1)) }}
                    </span>

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">
                            {{ $namaPetugas }}
                        </p>

                        <p class="text-xs text-slate-500">
                            Petugas perpustakaan
                        </p>
                    </div>
                </div>
            </section>
        </aside>
    </div>

    <section
        class="mt-6 rounded-2xl bg-[#EEF3FC] p-5"
        aria-labelledby="judul-sop"
    >
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2
                id="judul-sop"
                class="flex items-center gap-2 text-sm font-bold text-blue-800"
            >
                @include('petugas.partials.icon', [
                    'name' => 'return',
                    'class' => 'size-5',
                ])

                Alur Validasi Pengembalian
            </h2>

            <span class="rounded-full bg-blue-700 px-3 py-1 text-xs font-semibold text-white">
                Standar prosedur
            </span>
        </div>

        <ol class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($sop as $n => [$judul, $isi])
                <li class="rounded-xl bg-white p-4">
                    <p class="flex items-center gap-2 text-sm font-bold">
                        <span class="grid size-6 shrink-0 place-items-center rounded-full bg-blue-100 text-xs text-blue-700">
                            {{ $n + 1 }}
                        </span>

                        {{ $judul }}
                    </p>

                    <p class="mt-2 text-xs text-slate-600">
                        {{ $isi }}
                    </p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection
```