@extends('petugas.layout')

@section('title', 'Dashboard Petugas')

@php
    use App\Models\Anggota;
    use App\Models\Buku;
    use App\Models\Peminjaman;
    use App\Models\Pengembalian;
    use App\Support\PetugasData as D;

    [$namaPetugas, $shiftPetugas] = D::identitasPetugas();
    $rp = fn ($n) => D::rupiah($n);
    $tarif = D::tarifDenda();

    // Semua angka di halaman ini dihitung dari tabel peminjaman, pengembalian, buku, anggota, dan petugas.
    $antrean = Peminjaman::with(['anggota', 'buku'])
        ->where('status_peminjaman', Peminjaman::STATUS_MENUNGGU_VALIDASI)
        ->orderBy('tgl_peminjaman')->orderBy('id_peminjaman')->limit(5)->get();
    $jumlahAntrean = D::antreanPeminjaman();

    $kembaliMenunggu = Pengembalian::with(['peminjaman.anggota', 'peminjaman.buku'])
        ->where('status_pengembalian', Pengembalian::STATUS_MENUNGGU_VALIDASI)
        ->orderBy('tgl_pengembalian')->orderBy('id_pengembalian')->limit(3)->get();
    $jumlahKembali = Pengembalian::where('status_pengembalian', Pengembalian::STATUS_MENUNGGU_VALIDASI)->count();
    $selesaiHariIni = Pengembalian::where('status_pengembalian', Pengembalian::STATUS_SELESAI)->whereDate('updated_at', today())->count();

    $dendaBelumBayar = (float) Pengembalian::where('status_denda', Pengembalian::DENDA_BELUM_DIBAYAR)->sum('denda');
    $jumlahDenda = Pengembalian::where('status_denda', Pengembalian::DENDA_BELUM_DIBAYAR)->count();

    $totalJudul = Buku::count();
    $totalStok = (int) Buku::sum('stock');
    $jumlahKritis = Buku::where('stock', '<=', 1)->count();
    $kritis = Buku::where('stock', '<=', 1)->orderBy('stock')->orderBy('judul_buku')->limit(4)->get();

    $totalAnggota = Anggota::count();
    $anggotaAktif = Anggota::where('status_anggota', Anggota::STATUS_AKTIF)->count();

    $tren = D::tren(7);
    $maks = max(1, $tren->max(fn ($t) => max($t['pinjam'], $t['kembali'])));
    $aktivitas = D::aktivitas(5);
    $perShift = D::petugasPerShift();
@endphp

@section('content')
    {{-- Banner sambutan --}}
    <section class="mt-6 rounded-3xl bg-gradient-to-br from-blue-700 to-blue-600 p-6 text-white shadow-sm sm:p-8" aria-labelledby="judul-sambutan">
        <div class="flex flex-wrap items-start justify-between gap-6">
            <div class="max-w-2xl">
                <div class="flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1"><span class="size-2 rounded-full bg-emerald-300"></span>Petugas aktif</span>
                    @if ($shiftPetugas)
                        <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1">
                            @include('petugas.partials.icon', ['name' => 'clock', 'class' => 'size-3.5']) Shift: {{ $shiftPetugas }}
                        </span>
                    @endif
                </div>
                <h1 id="judul-sambutan" class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">Selamat bertugas, {{ $namaPetugas }}</h1>
                <p class="mt-2 text-sm text-blue-50">
                    @if ($jumlahAntrean > 0)
                        Ada {{ $jumlahAntrean }} pengajuan peminjaman menunggu validasi dan {{ $jumlahKembali }} pengembalian menunggu verifikasi.
                    @else
                        Tidak ada pengajuan peminjaman yang menunggu. Ada {{ $jumlahKembali }} pengembalian menunggu verifikasi.
                    @endif
                </p>
            </div>
            <div class="grid w-full gap-3 sm:w-auto sm:grid-cols-2">
                @foreach ([['Kelola Buku', 'buku', 'book'], ['Kelola Anggota', 'anggota', 'users'], ['Kelola Denda', 'denda', 'cash'], ['Proses Pinjam', 'peminjaman', 'borrow']] as [$label, $halaman, $ikon])
                    <a href="{{ D::link($halaman) }}" class="flex items-center gap-2 rounded-xl bg-white px-4 py-3 text-sm font-semibold text-blue-800 shadow-sm hover:bg-blue-50">
                        @include('petugas.partials.icon', ['name' => $ikon, 'class' => 'size-4']) {{ $label }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Ringkasan --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5" aria-label="Ringkasan layanan">
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">Validasi pinjam</p><span class="grid size-9 place-items-center rounded-lg bg-amber-50 text-amber-600">@include('petugas.partials.icon', ['name' => 'borrow', 'class' => 'size-5'])</span></div>
            <p class="mt-3 text-3xl font-extrabold">{{ $jumlahAntrean }} <span class="text-sm font-medium text-slate-500">Menunggu petugas</span></p>
            <p class="mt-3 text-xs text-slate-500">Cek stok sebelum menyetujui</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">Proses kembali</p><span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5'])</span></div>
            <p class="mt-3 text-3xl font-extrabold">{{ $jumlahKembali }} <span class="text-sm font-medium text-slate-500">Menunggu verifikasi</span></p>
            <p class="mt-3 text-xs text-slate-500">{{ $selesaiHariIni }} selesai divalidasi hari ini</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">Denda belum dibayar</p><span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-5'])</span></div>
            <p class="mt-3 text-3xl font-extrabold">{{ $rp($dendaBelumBayar) }}</p>
            <p class="mt-3 flex justify-between gap-2 text-xs"><span class="text-blue-700">Tarif {{ $rp($tarif) }}/hari</span><span class="text-blue-700">{{ $jumlahDenda }} denda aktif</span></p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">Kelola buku</p><span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5'])</span></div>
            <p class="mt-3 text-2xl font-extrabold">{{ number_format($totalJudul, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">Judul</span></p>
            <p class="text-2xl font-extrabold">{{ number_format($totalStok, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">Stok</span></p>
            <p class="mt-2 flex items-center gap-1 text-xs font-medium {{ $jumlahKritis ? 'text-amber-700' : 'text-slate-500' }}">@include('petugas.partials.icon', ['name' => 'alert', 'class' => 'size-3.5']) {{ $jumlahKritis }} judul stok kritis (≤1)</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">Kelola anggota</p><span class="grid size-9 place-items-center rounded-lg bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'users', 'class' => 'size-5'])</span></div>
            <p class="mt-3 text-3xl font-extrabold">{{ number_format($totalAnggota, 0, ',', '.') }} <span class="text-sm font-medium text-slate-500">Terdaftar</span></p>
            <p class="mt-3 text-xs text-slate-500">{{ $anggotaAktif }} berstatus aktif</p>
        </div>
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-6">
            {{-- Antrean validasi --}}
            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-antrean">
                <div class="flex flex-wrap items-start justify-between gap-3 p-5">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'return'])</span>
                        <div>
                            <h2 id="judul-antrean" class="text-base font-extrabold">Antrean Validasi Peminjaman Buku</h2>
                            <p class="text-xs text-slate-500">Menyetujui akan mengubah status menjadi "dipinjam" dan mengurangi stok buku.</p>
                        </div>
                    </div>
                    <a href="{{ D::link('peminjaman') }}" class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-200">{{ $jumlahAntrean }} tertunda</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[44rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID pinjam</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Judul buku</th>
                                <th scope="col" class="px-3 py-3">Diajukan</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($antrean as $p)
                                <tr class="align-top">
                                    <td class="px-5 py-4 font-bold text-blue-700">{{ D::kode('PMJ', $p->id_peminjaman) }}</td>
                                    <td class="px-3 py-4"><p class="font-semibold">{{ $p->anggota?->nama_anggota ?? '-' }}</p><p class="text-xs text-slate-500">{{ $p->anggota?->email_anggota }}</p></td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $p->buku?->judul_buku ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">
                                            @if ($p->buku?->rak) Rak {{ $p->buku->rak }} • @endif
                                            @if ($p->buku?->isbn) ISBN {{ $p->buku->isbn }} • @endif
                                            <span @class(['font-semibold', 'text-red-600' => ($p->buku?->stock ?? 0) < 1])>Stok {{ $p->buku?->stock ?? 0 }}</span>
                                        </p>
                                    </td>
                                    <td class="px-3 py-4 text-xs"><p>{{ D::tanggal($p->tgl_peminjaman) }}</p><p class="font-semibold text-amber-700">Jatuh tempo +{{ D::lamaPinjam() }} hari</p></td>
                                    <td class="px-3 py-4 pr-5">@include('petugas.partials.aksi-pinjam', ['p' => $p])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500">Tidak ada pengajuan yang menunggu validasi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Verifikasi pengembalian --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kembali">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'return'])</span>
                        <div>
                            <h2 id="judul-kembali" class="text-base font-extrabold">Verifikasi Pengembalian &amp; Kalkulasi Denda</h2>
                            <p class="text-xs text-slate-500">Estimasi denda dihitung dari tanggal jatuh tempo; angka final ditetapkan saat petugas memvalidasi.</p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500">Tarif: {{ $rp($tarif) }} / hari terlambat</span>
                </div>
                <ul class="mt-4 space-y-3">
                    @forelse ($kembaliMenunggu as $k)
                        @php
                            $pinjam = $k->peminjaman;
                            $telat = D::hariTerlambat($pinjam?->tgl_jatuh_tempo, $k->tgl_pengembalian);
                            $estimasi = $telat * $tarif;
                        @endphp
                        <li class="flex flex-wrap items-center gap-4 rounded-2xl bg-[#EEF3FC] p-4">
                            @include('petugas.partials.cover', ['i' => $k->id_pengembalian, 'class' => 'h-12 w-10'])
                            <div class="min-w-0 flex-1 basis-56">
                                <p class="flex flex-wrap items-center gap-2 text-xs">
                                    <b class="text-blue-700">{{ D::kode('KMB', $k->id_pengembalian) }}</b>
                                    @if ($telat > 0)
                                        <span class="rounded-md bg-red-100 px-2 py-0.5 font-semibold text-red-700">Terlambat {{ $telat }} hari</span>
                                    @else
                                        <span class="rounded-md bg-emerald-100 px-2 py-0.5 font-semibold text-emerald-700">Tepat waktu</span>
                                    @endif
                                </p>
                                <p class="font-bold">{{ $pinjam?->buku?->judul_buku ?? '-' }}</p>
                                <p class="text-xs text-slate-500">Peminjam: {{ $pinjam?->anggota?->nama_anggota ?? '-' }} • Jatuh tempo: {{ D::tanggal($pinjam?->tgl_jatuh_tempo) }} • Diajukan: {{ D::tanggal($k->tgl_pengembalian) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estimasi denda</p>
                                <p @class(['text-xl font-extrabold', 'text-red-600' => $estimasi > 0])>{{ $rp($estimasi) }}</p>
                            </div>
                            <a href="{{ D::link('pengembalian') }}" class="flex items-center gap-1.5 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">
                                @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-4']) Validasi
                            </a>
                        </li>
                    @empty
                        <li class="rounded-2xl bg-[#EEF3FC] p-6 text-center text-sm text-slate-500">Tidak ada pengembalian yang menunggu verifikasi.</li>
                    @endforelse
                </ul>
            </section>

            {{-- Tren transaksi --}}
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-tren">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="judul-tren" class="text-base font-extrabold">Tren Transaksi Sirkulasi 7 Hari Terakhir</h2>
                        <p class="text-xs text-slate-500">Jumlah pengajuan peminjaman dan pengajuan pengembalian per hari</p>
                    </div>
                    <p class="flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-blue-600"></span>Peminjaman</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-blue-900"></span>Pengembalian</span>
                    </p>
                </div>
                <div class="mt-6 flex h-44 items-end justify-between gap-2" role="img" aria-label="Grafik batang peminjaman dan pengembalian 7 hari terakhir">
                    @foreach ($tren as $t)
                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="flex h-36 items-end gap-1">
                                <span class="w-4 rounded-t bg-blue-600 sm:w-5" style="height: {{ max(2, round($t['pinjam'] / $maks * 100)) }}%" title="Peminjaman {{ $t['label'] }}: {{ $t['pinjam'] }}"></span>
                                <span class="w-4 rounded-t bg-blue-900 sm:w-5" style="height: {{ max(2, round($t['kembali'] / $maks * 100)) }}%" title="Pengembalian {{ $t['label'] }}: {{ $t['kembali'] }}"></span>
                            </div>
                            <span @class(['text-xs', 'font-bold text-blue-700' => $t['hariIni'], 'text-slate-500' => ! $t['hariIni']])>{{ $t['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="min-w-0 space-y-6" aria-label="Stok kritis, petugas, dan aktivitas">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kritis">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-kritis" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'bell', 'class' => 'size-5 text-amber-600']) Stok Buku Kritis
                    </h2>
                    <span class="rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">Stok ≤ 1</span>
                </div>
                <p class="mt-2 text-xs text-slate-500">Buku dengan stok habis tidak bisa disetujui peminjamannya.</p>
                <ul class="mt-4 space-y-3">
                    @forelse ($kritis as $b)
                        <li class="flex items-center gap-3 rounded-xl bg-[#EEF3FC] p-3">
                            @include('petugas.partials.cover', ['i' => $b->id_buku, 'class' => 'h-14 w-10'])
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-bold">{{ $b->judul_buku }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $b->pengarang }}@if ($b->penerbit) • {{ $b->penerbit }}@endif</p>
                                <p class="mt-1 text-xs"><span class="text-slate-500">{{ D::kode('BK', $b->id_buku) }}</span> • <b class="text-red-600">Stok: {{ $b->stock }}{{ $b->stock < 1 ? ' (habis)' : '' }}</b></p>
                            </div>
                            @if (D::bisa('petugas.kelola-buku.edit'))
                                <a href="{{ route('petugas.kelola-buku.edit', $b->id_buku) }}" class="flex shrink-0 items-center gap-1 rounded-lg bg-blue-700 px-2.5 py-2 text-xs font-semibold text-white hover:bg-blue-800" aria-label="Ubah stok {{ $b->judul_buku }}">
                                    @include('petugas.partials.icon', ['name' => 'pencil', 'class' => 'size-3.5']) Ubah
                                </a>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-xl bg-[#EEF3FC] p-4 text-center text-sm text-slate-500">Tidak ada buku dengan stok kritis.</li>
                    @endforelse
                </ul>
                <a href="{{ D::link('buku') }}" class="mt-4 block text-center text-xs font-semibold text-blue-700 hover:underline">Buka tabel lengkap kelola buku →</a>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-shift">
                <h2 id="judul-shift" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-5 text-blue-700']) Petugas per Shift
                </h2>
                <div class="mt-4 space-y-3 text-sm">
                    @forelse ($perShift as $shift => $daftar)
                        <div class="rounded-xl bg-[#EEF3FC] p-3">
                            <p class="flex items-center justify-between font-semibold">
                                <span class="flex items-center gap-2"><span class="size-2 rounded-full {{ $shiftPetugas === $shift ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>Shift {{ $shift }}</span>
                                @if ($shiftPetugas === $shift)<span class="text-xs font-bold text-blue-700">Shift Anda</span>@endif
                            </p>
                            <p class="mt-1 text-xs text-slate-600">{{ $daftar->pluck('nama_petugas')->implode(' • ') }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl bg-[#EEF3FC] p-4 text-center text-sm text-slate-500">Belum ada petugas terdaftar.</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-aktivitas">
                <h2 id="judul-aktivitas" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700']) Aktivitas Terbaru
                </h2>
                <ul class="mt-4 space-y-4 text-sm">
                    @forelse ($aktivitas as $a)
                        <li class="flex gap-3">
                            <span class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-full bg-blue-50 text-blue-700">@include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-4'])</span>
                            <div>
                                <p>@if ($a['oleh'])<b>{{ $a['oleh'] }}</b> • @endif{{ $a['isi'] }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ $a['waktu']->translatedFormat('d M, H:i') }} • {{ $a['meta'] }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada aktivitas peminjaman atau pengembalian.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
@endsection
