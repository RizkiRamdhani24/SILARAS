@extends('petugas.layout')

@section('title', 'Validasi Peminjaman')

@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $namaPetugas = auth('petugas')->user()->nama_petugas ?? 'Eom Seonghyeon';
    $shiftPetugas = ucfirst(strtolower(auth('petugas')->user()->shift ?? 'pagi'));

    // TODO backend (Exel): ganti dengan data peminjaman berstatus "menunggu validasi" beserta relasi anggota & buku.
    $antrean = [
        ['id' => 'PMJ-2024-0891', 'waktu' => '08:42 WIB', 'nama' => 'Arya Pratama', 'nim' => '21040119', 'prodi' => 'TI-S1', 'kuota' => '1/3', 'denda' => 0, 'judul' => 'Database System Concepts', 'kode' => 'BK-0104', 'rak' => 'CS-301', 'stok' => 4, 'pinjam' => '24 Okt 2024', 'tempo' => '31 Okt 2024'],
        ['id' => 'PMJ-2024-0892', 'waktu' => '08:49 WIB', 'nama' => 'Farhan Maulana', 'nim' => '21040055', 'prodi' => 'Manajemen', 'kuota' => '2/3', 'denda' => 6000, 'judul' => 'Clean Architecture', 'kode' => 'BK-0105', 'rak' => 'CS-102', 'stok' => 2, 'pinjam' => '24 Okt 2024', 'tempo' => '31 Okt 2024'],
        ['id' => 'PMJ-2024-0893', 'waktu' => '09:05 WIB', 'nama' => 'Siti Rahmawati', 'nim' => '22050214', 'prodi' => 'Psikologi', 'kuota' => '0/3', 'denda' => 0, 'judul' => 'Komunikasi Data dan Jaringan', 'kode' => 'BK-0205', 'rak' => 'CS-210', 'stok' => 0, 'pinjam' => '24 Okt 2024', 'tempo' => '31 Okt 2024'],
        ['id' => 'PMJ-2024-0894', 'waktu' => '09:12 WIB', 'nama' => 'Dimas Anggoro', 'nim' => '20010188', 'prodi' => 'Sistem Informasi', 'kuota' => '1/3', 'denda' => 0, 'judul' => 'Filosofi Teras', 'kode' => 'BK-0102', 'rak' => 'PH-104', 'stok' => 6, 'pinjam' => '24 Okt 2024', 'tempo' => '31 Okt 2024'],
        ['id' => 'PMJ-2024-0895', 'waktu' => '09:18 WIB', 'nama' => 'Indah Cahyani', 'nim' => '22010992', 'prodi' => 'Teknik Informatika', 'kuota' => '2/3', 'denda' => 0, 'judul' => 'Artificial Intelligence: A Modern Approach', 'kode' => 'BK-0101', 'rak' => 'CS-301', 'stok' => 1, 'pinjam' => '24 Okt 2024', 'tempo' => '31 Okt 2024'],
    ];

    $log = [
        ['id' => 'PMJ-2024-0890', 'hasil' => 'Disetujui', 'ok' => true, 'waktu' => '8 mnt lalu', 'isi' => 'Buku "Algoritma Pemrograman" dipinjam Nadia Putri (NIM: 22050110). Stok otomatis -1.', 'catatan' => 'Jatuh tempo: 31 Okt 2024 • Slip tercetak'],
        ['id' => 'PMJ-2024-0889', 'hasil' => 'Ditolak', 'ok' => false, 'waktu' => '25 mnt lalu', 'isi' => 'Permohonan Rian Pratama ditolak sistem. Alasan: kuota pinjam telah mencapai batas maksimal (3 buku).', 'catatan' => 'Notifikasi terkirim ke email anggota'],
        ['id' => 'PMJ-2024-0888', 'hasil' => 'Disetujui', 'ok' => true, 'waktu' => '42 mnt lalu', 'isi' => 'Buku "Laskar Pelangi" dipinjam Reza Pahlevi (NIM: 21020411). Sisa stok: 3 eks.', 'catatan' => 'Jatuh tempo: 31 Okt 2024'],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Validasi &amp; Proses Peminjaman Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Verifikasi pengajuan peminjaman daring, audit ketersediaan stok fisik real-time, validasi kuota civitas dan status tunggakan denda sebelum pemotongan stok otomatis.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: ekspor log belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'download', 'class' => 'size-4']) Export Log
            </button>
            <a href="#loket" class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4']) Pinjam Loket Fisik
            </a>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan peminjaman">
        @include('petugas.partials.stat-card', ['label' => 'Antrean validasi', 'value' => '14', 'suffix' => 'Pengajuan', 'icon' => 'borrow', 'tone' => 'amber', 'footer' => '4 prioritas hari ini • UC-02'])
        @include('petugas.partials.stat-card', ['label' => 'Peminjaman hari ini', 'value' => '38', 'suffix' => 'Buku disetujui', 'icon' => 'book', 'tone' => 'blue', 'footer' => '+12 dari kemarin • Stok terpotong: -38'])
        @include('petugas.partials.stat-card', ['label' => 'Stok kritis terkait', 'value' => '3', 'suffix' => 'Judul diajukan', 'icon' => 'alert', 'tone' => 'red', 'labelTone' => 'text-red-600', 'footer' => 'Stok ≤ 1 eksemplar • Perlu cek fisik', 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Validasi kelayakan', 'value' => '98.2%', 'suffix' => 'Lolos syarat', 'icon' => 'shield', 'tone' => 'blue', 'footer' => '2 ditolak tunggakan • Maks 3 buku/mhs'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_20rem]">
        {{-- Antrean validasi --}}
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-52 flex-1">
                        <span class="sr-only">Cari ID pinjam, NIM, atau nama</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" data-search="#tabel-pinjam" placeholder="Cari ID pinjam, NIM, nama..."
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <label class="text-sm">
                        <span class="sr-only">Status</span>
                        <select class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <option>Status: Menunggu Validasi (14)</option>
                            <option>Status: Disetujui</option>
                            <option>Status: Ditolak</option>
                        </select>
                    </label>
                    <label class="text-sm">
                        <span class="sr-only">Fakultas</span>
                        <select class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <option>Semua Fakultas</option>
                            <option>Teknik &amp; Informatika</option>
                            <option>Ekonomi &amp; Bisnis</option>
                            <option>Psikologi</option>
                        </select>
                    </label>
                </div>
                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="size-2 rounded-full bg-blue-600"></span> Real-time sinkronisasi katalog aktif
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-[#EEF3FC] px-5 py-4">
                    <h2 id="judul-antrean" class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5 text-blue-700'])
                        Antrean Pengajuan Menunggu Validasi Petugas (Modul UC-07)
                    </h2>
                    <button type="button" id="setujui-semua" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-blue-700 shadow-sm ring-1 ring-blue-100 hover:bg-blue-50">
                        Setujui Semua yang Layak
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="tabel-pinjam" class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr class="border-b border-slate-100">
                                <th scope="col" class="w-10 py-3 pl-5"><input type="checkbox" data-check-all="#tabel-pinjam" class="size-4 rounded border-slate-300" aria-label="Pilih semua pengajuan"></th>
                                <th scope="col" class="px-3 py-3">ID &amp; Waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota pemohon</th>
                                <th scope="col" class="px-3 py-3">Buku diajukan &amp; stok</th>
                                <th scope="col" class="px-3 py-3">Durasi</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($antrean as $i => $p)
                                @php
                                    $tunggakan = $p['denda'] > 0;
                                    $habis = $p['stok'] < 1;
                                    $diblokir = $tunggakan || $habis;
                                    $status = $tunggakan ? 'Ada tunggakan' : ($habis ? 'Stok habis' : 'Menunggu validasi');
                                @endphp
                                <tr @class(['align-top', 'bg-red-50/50' => $diblokir]) data-id="{{ $p['id'] }}">
                                    <td class="py-4 pl-5">
                                        <input type="checkbox" data-row-check @disabled($diblokir) class="size-4 rounded border-slate-300" aria-label="Pilih {{ $p['id'] }}">
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-bold text-blue-700">{{ $p['id'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $p['waktu'] }} • Daring</p>
                                        <span data-status @class([
                                            'mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-red-100 text-red-700' => $diblokir,
                                            'bg-amber-100 text-amber-800' => ! $diblokir,
                                        ])>{{ $status }}</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $p['nama'] }}</p>
                                        <p class="text-xs text-slate-500">NIM: {{ $p['nim'] }} • {{ $p['prodi'] }}</p>
                                        <div class="mt-2 flex flex-wrap gap-1.5 text-xs font-medium">
                                            <span class="rounded-md bg-blue-50 px-2 py-1 text-blue-700">Kuota: {{ $p['kuota'] }}</span>
                                            <span @class(['rounded-md px-2 py-1', 'bg-red-100 text-red-700' => $tunggakan, 'bg-slate-100 text-slate-600' => ! $tunggakan])>Denda: {{ $rp($p['denda']) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex gap-3">
                                            @include('petugas.partials.cover', ['i' => $i])
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">{{ $p['judul'] }}</p>
                                                <p class="text-xs text-slate-500">{{ $p['kode'] }} • Rak {{ $p['rak'] }}</p>
                                                <span @class([
                                                    'mt-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                                                    'bg-red-100 text-red-700' => $habis,
                                                    'bg-amber-100 text-amber-800' => ! $habis && $p['stok'] <= 1,
                                                    'bg-emerald-50 text-emerald-700' => $p['stok'] > 1,
                                                ])>
                                                    @if ($habis) Stok: 0 (habis dipinjam)
                                                    @elseif ($p['stok'] <= 1) Stok kritis: {{ $p['stok'] }} eks
                                                    @else Tersedia (stok: {{ $p['stok'] }})
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-xs text-slate-600">
                                        <p>{{ $p['pinjam'] }}</p>
                                        <p class="font-semibold text-amber-700">→ {{ $p['tempo'] }} (7 hari)</p>
                                        @if ($diblokir)
                                            <p class="mt-2 font-semibold text-red-700">Blokir otomatis UC-07: {{ $tunggakan ? 'tunggakan denda aktif' : 'stok habis' }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 pr-5">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" data-approve @disabled($diblokir)
                                                    class="flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                                                @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-3.5']) Setujui
                                            </button>
                                            <button type="button" data-reject
                                                    class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40"
                                                    aria-label="Tolak {{ $p['id'] }}">
                                                @include('petugas.partials.icon', ['name' => 'x', 'class' => 'size-4'])
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('petugas.partials.pagination', ['from' => 1, 'to' => 5, 'total' => 14, 'noun' => 'pengajuan menunggu', 'last' => 3])
            </div>

            <div class="rounded-2xl bg-[#EEF3FC] p-5 text-sm text-slate-600">
                <p class="font-bold text-blue-800">Alur sinkronisasi transaksi &amp; stok fisik</p>
                <p class="mt-1">Persetujuan transaksi memicu tiga operasi sekaligus: (1) mencatat <code class="rounded bg-white px-1 py-0.5 text-xs">id_petugas</code> pada tabel peminjaman, (2) mengurangi stok pada tabel buku, dan (3) menerbitkan slip jatuh tempo (7 hari). Penolakan wajib mencantumkan alasan agar anggota menerima notifikasi.</p>
            </div>
        </section>

        {{-- Panel samping --}}
        <aside class="min-w-0 space-y-6" aria-label="Loket dan log validasi">
            <section id="loket" class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-extrabold">Loket Pinjam Cepat Fisik</h2>
                        <p class="text-xs text-slate-500">Layanan meja sirkulasi langsung</p>
                    </div>
                    <span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Shift {{ $shiftPetugas }}</span>
                </div>

                {{-- TODO backend: arahkan ke route peminjaman langsung dari Exel, mis. route('petugas.peminjaman.store') --}}
                <form method="POST" action="#" data-demo="Tampilan contoh: peminjaman loket belum terhubung ke backend." class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="kode-anggota" class="text-xs font-semibold">Scan KTM / ID anggota</label>
                        <div class="mt-1 flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/30">
                            @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-4 text-slate-400'])
                            <input id="kode-anggota" name="kode_anggota" value="AG-0847" class="min-w-0 flex-1 border-0 p-0 text-sm focus:ring-0 focus:outline-none">
                            <button type="button" data-demo-click="Tampilan contoh: pengecekan akun belum terhubung." class="text-xs font-semibold text-blue-700">Cek akun</button>
                        </div>
                        <div class="mt-2 flex items-center gap-3 rounded-xl bg-[#EEF3FC] p-3 text-sm">
                            <span class="grid size-9 place-items-center rounded-full bg-blue-100 text-xs font-bold text-blue-700">BH</span>
                            <div class="flex-1">
                                <p class="font-semibold">Budi Hartono (TI-S1)</p>
                                <p class="text-xs text-slate-500">Kuota: 2/3 • Denda: Rp 0</p>
                            </div>
                            @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-5 text-blue-700'])
                        </div>
                    </div>

                    <div>
                        <label for="kode-buku" class="text-xs font-semibold">Scan barcode buku / ID buku</label>
                        <div class="mt-1 flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/30">
                            @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-4 text-slate-400'])
                            <input id="kode-buku" name="kode_buku" value="BK-0105" class="min-w-0 flex-1 border-0 p-0 text-sm focus:ring-0 focus:outline-none">
                            <span class="text-xs font-semibold text-blue-700">Validasi</span>
                        </div>
                        <p class="mt-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs text-emerald-800">Clean Architecture • Rak CS-102 • Sisa stok: 2 eks <b>(tersedia)</b></p>
                    </div>

                    <fieldset>
                        <legend class="text-xs font-semibold">Durasi peminjaman</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="durasi" value="7" checked class="mt-1 text-blue-700 focus:ring-blue-600">
                                <span><b class="block">Standar (7 hari)</b><span class="text-xs text-slate-500">31 Okt 2024</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="durasi" value="14" class="mt-1 text-blue-700 focus:ring-blue-600">
                                <span><b class="block">Riset (14 hari)</b><span class="text-xs text-slate-500">07 Nov 2024</span></span>
                            </label>
                        </div>
                    </fieldset>

                    <p class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900"><b>Syarat UC-07:</b> maksimum 3 buku per anggota. Peminjaman otomatis ditolak jika terdapat denda aktif &gt; Rp 0 atau stok = 0.</p>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                        @include('petugas.partials.icon', ['name' => 'borrow', 'class' => 'size-4']) Terbitkan Peminjaman
                    </button>
                </form>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                        Log validasi {{ \Illuminate\Support\Str::words($namaPetugas, 1, '') }}
                    </h2>
                    <span class="text-xs text-slate-500">Hari ini</span>
                </div>
                <ul class="mt-4 space-y-3">
                    @foreach ($log as $l)
                        <li @class(['rounded-xl p-3 text-xs', 'bg-[#EEF3FC]' => $l['ok'], 'bg-red-50' => ! $l['ok']])>
                            <div class="flex items-center justify-between gap-2">
                                <p @class(['font-bold', 'text-blue-700' => $l['ok'], 'text-red-700' => ! $l['ok']])>{{ $l['id'] }} • {{ $l['hasil'] }}</p>
                                <span class="text-slate-500">{{ $l['waktu'] }}</span>
                            </div>
                            <p class="mt-1 text-slate-700">{{ $l['isi'] }}</p>
                            <p class="mt-1 text-slate-500">{{ $l['catatan'] }}</p>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ url('/petugas/transaksi') }}" class="mt-4 block rounded-xl bg-[#EEF3FC] px-3 py-2.5 text-center text-xs font-semibold text-blue-700 hover:bg-blue-100">Buka seluruh riwayat transaksi sirkulasi →</a>
            </section>
        </aside>
    </div>

    {{-- Dialog penolakan: alasan wajib diisi --}}
    <dialog id="reject-dialog" class="m-auto w-[min(28rem,calc(100%-2rem))] rounded-2xl p-0 shadow-xl backdrop:bg-slate-900/40">
        <form method="dialog" class="p-6">
            <h2 class="text-lg font-extrabold">Tolak pengajuan <span id="reject-id" class="text-blue-700"></span></h2>
            <p class="mt-1 text-sm text-slate-600">Alasan penolakan wajib diisi dan akan dikirim sebagai notifikasi ke akun anggota.</p>
            <label for="reject-reason" class="mt-4 block text-sm font-semibold">Alasan penolakan</label>
            <textarea id="reject-reason" required rows="3" class="mt-1 w-full rounded-xl border border-slate-200 p-3 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none"></textarea>
            <div class="mt-5 flex justify-end gap-2">
                <button value="cancel" formnovalidate class="rounded-xl bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Batal</button>
                <button value="confirm" class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700">Tolak pengajuan</button>
            </div>
        </form>
    </dialog>
@endsection

@push('scripts')
<script>
(() => {
    const dialog = document.getElementById('reject-dialog');
    const alasan = document.getElementById('reject-reason');
    let barisAktif = null;

    const tandai = (tr, teks, kelas) => {
        const badge = tr.querySelector('[data-status]');
        badge.textContent = teks;
        badge.className = 'mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ' + kelas;
        tr.querySelectorAll('[data-approve], [data-reject]').forEach((b) => { b.disabled = true; });
        const cek = tr.querySelector('[data-row-check]');
        if (cek) { cek.checked = false; cek.disabled = true; }
    };
    const setujui = (tr) => tandai(tr, 'Disetujui', 'bg-emerald-100 text-emerald-700');

    document.querySelectorAll('[data-approve]').forEach((btn) => btn.addEventListener('click', () => {
        const tr = btn.closest('tr');
        setujui(tr);
        window.silarasToast('Contoh: ' + tr.dataset.id + ' disetujui. Stok akan dikurangi saat terhubung ke backend.');
    }));

    document.querySelectorAll('[data-reject]').forEach((btn) => btn.addEventListener('click', () => {
        barisAktif = btn.closest('tr');
        document.getElementById('reject-id').textContent = barisAktif.dataset.id;
        alasan.value = '';
        dialog.showModal();
        alasan.focus();
    }));

    dialog.addEventListener('close', () => {
        if (dialog.returnValue === 'confirm' && barisAktif) {
            tandai(barisAktif, 'Ditolak', 'bg-red-100 text-red-700');
            window.silarasToast('Contoh: ' + barisAktif.dataset.id + ' ditolak, notifikasi alasan akan dikirim ke anggota.');
        }
        barisAktif = null;
    });

    document.getElementById('setujui-semua').addEventListener('click', () => {
        const tabel = document.getElementById('tabel-pinjam');
        const dipilih = [...tabel.querySelectorAll('[data-row-check]:checked')];
        const target = dipilih.length
            ? dipilih.map((c) => c.closest('tr'))
            : [...tabel.querySelectorAll('tbody tr')].filter((tr) => !tr.hidden && !tr.querySelector('[data-approve]').disabled);
        target.forEach(setujui);
        window.silarasToast(target.length ? 'Contoh: ' + target.length + ' pengajuan layak disetujui.' : 'Tidak ada pengajuan layak yang bisa disetujui.');
    });
})();
</script>
@endpush
