@extends('petugas.layout')

@section('title', 'Validasi Pengembalian')

@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $namaPetugas = auth('petugas')->user()->nama_petugas ?? 'Eom Seonghyeon';
    $tarif = 2000; // TODO backend (Herlyan): tarif denda per hari dari konfigurasi

    // TODO backend (Herlyan): ganti dengan data pengembalian berstatus "menunggu validasi" beserta relasi peminjaman, anggota, buku.
    $antrean = [
        ['id' => 'KMB-2024-0412', 'waktu' => '24 Okt, 08:42 WIB', 'nama' => 'Farhan Maulana', 'nim' => '19020340', 'prodi' => 'Manajemen Keuangan', 'judul' => 'Algoritma Pemrograman C++', 'kode' => 'BK-0104', 'kondisi' => 'baik', 'catatan' => null, 'tempo' => '21 Okt 2024', 'telat' => 3, 'awal' => false, 'denda' => 6000, 'aksi' => 'tagih'],
        ['id' => 'KMB-2024-0413', 'waktu' => '24 Okt, 08:48 WIB', 'nama' => 'Nadia Putri Rahayu', 'nim' => '22010899', 'prodi' => 'Ilmu Komunikasi', 'judul' => 'Pengantar Psikologi Kognitif', 'kode' => 'BK-0205', 'kondisi' => 'baik', 'catatan' => null, 'tempo' => '24 Okt 2024', 'telat' => 0, 'awal' => false, 'denda' => 0, 'aksi' => 'terima'],
        ['id' => 'KMB-2024-0414', 'waktu' => '24 Okt, 08:52 WIB', 'nama' => 'Dimas Anggoro', 'nim' => '20010188', 'prodi' => 'Sistem Informasi', 'judul' => 'Clean Architecture', 'kode' => 'BK-0105', 'kondisi' => 'rusak', 'catatan' => 'Halaman sobek', 'tempo' => '19 Okt 2024', 'telat' => 5, 'awal' => false, 'denda' => 10000, 'aksi' => 'sesuaikan'],
        ['id' => 'KMB-2024-0415', 'waktu' => '24 Okt, 09:05 WIB', 'nama' => 'Arya Pratama', 'nim' => '20050212', 'prodi' => 'Sistem Informasi', 'judul' => 'Database System Concepts', 'kode' => 'BK-0104', 'kondisi' => 'baik', 'catatan' => null, 'tempo' => '26 Okt 2024', 'telat' => 0, 'awal' => true, 'denda' => 0, 'aksi' => 'terima'],
        ['id' => 'KMB-2024-0416', 'waktu' => '24 Okt, 09:12 WIB', 'nama' => 'Siti Rahmawati', 'nim' => '19820514', 'prodi' => 'Psikologi', 'judul' => 'Filosofi Teras', 'kode' => 'BK-0102', 'kondisi' => 'baik', 'catatan' => null, 'tempo' => '24 Okt 2024', 'telat' => 0, 'awal' => false, 'denda' => 0, 'aksi' => 'terima'],
    ];

    $log = [
        ['jam' => '09:12 WIB', 'ok' => true, 'isi' => 'KMB-2024-0416 diselesaikan. Buku "Filosofi Teras" kondisi baik, denda Rp 0, stok buku +1.'],
        ['jam' => '08:52 WIB', 'ok' => false, 'isi' => 'Pencatatan KMB-2024-0414: denda Rp 10.000 + status rusak.'],
        ['jam' => '08:35 WIB', 'ok' => true, 'isi' => 'KMB-2024-0410 diselesaikan, denda Rp 4.000 lunas kasir, stok buku bertambah.'],
    ];

    $sop = [
        ['Penerimaan & cek fisik', 'Petugas memeriksa integritas buku: halaman, sampul, barcode (baik, rusak, hilang).'],
        ['Hitung denda otomatis (UC-09)', 'Sistem membandingkan tanggal aktual dengan jatuh tempo. Terlambat otomatis dikali Rp 2.000/hari.'],
        ['Update status transaksi', 'Status peminjaman diperbarui menjadi selesai/dikembalikan dan bukti verifikasi dicetak.'],
        ['Sinkronisasi stok real-time', 'Buku kondisi baik otomatis menambah stok rak (+1). Kondisi rusak dicatat ke log UC-05.'],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Validasi Pengembalian Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Verifikasi fisik buku (baik, rusak, atau hilang), kalkulasi denda otomatis per hari keterlambatan, serta pembaruan stok real-time.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: cetak bukti belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'printer', 'class' => 'size-4']) Cetak Bukti Pengembalian
            </button>
            <button type="button" data-demo-click="Tampilan contoh: ekspor laporan belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'download', 'class' => 'size-4']) Ekspor Laporan Harian
            </button>
            <a href="#loket" class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'qr', 'class' => 'size-4']) Proses Pengembalian Meja Fisik
            </a>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pengembalian">
        @include('petugas.partials.stat-card', ['label' => 'Antrean pengembalian online', 'value' => '8', 'suffix' => 'Pengajuan', 'icon' => 'download', 'tone' => 'blue', 'footer' => '3 perlu cek fisik • UC-04'])
        @include('petugas.partials.stat-card', ['label' => 'Buku diterima hari ini', 'value' => '28', 'suffix' => 'Eksemplar', 'icon' => 'book', 'tone' => 'blue', 'footer' => '+26 baik • 2 rusak/hilang'])
        @include('petugas.partials.stat-card', ['label' => 'Total denda terkumpul', 'value' => 'Rp 145.000', 'icon' => 'cash', 'tone' => 'amber', 'valueTone' => 'text-red-600', 'footer' => 'Tarif Rp 2.000/hari • 5 transaksi didenda'])
        @include('petugas.partials.stat-card', ['label' => 'Tingkat tepat waktu', 'value' => '91.8%', 'suffix' => 'Bebas denda (Rp 0)', 'icon' => 'shield', 'tone' => 'blue', 'footer' => 'Sirkulasi lancar'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_21rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">
            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <label class="relative min-w-52 flex-1">
                    <span class="sr-only">Cari ID transaksi, NIM, atau judul buku</span>
                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                    <input type="search" data-search="#tabel-kembali" placeholder="Cari ID transaksi, NIM, judul buku..."
                           class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </label>
                <label><span class="sr-only">Status</span>
                    <select data-filter-status class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        <option>Semua status</option><option>Terlambat</option><option>Tepat waktu</option>
                    </select>
                </label>
                <label><span class="sr-only">Tanggal</span>
                    <select class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        <option>Semua tanggal</option><option>Hari ini</option><option>7 hari terakhir</option>
                    </select>
                </label>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-center gap-3 px-5 py-4">
                    <h2 id="judul-antrean" class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5 text-blue-700'])
                        Antrean &amp; Verifikasi Pengembalian Buku
                    </h2>
                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700">5 baru</span>
                </div>

                <div class="overflow-x-auto">
                    <table id="tabel-kembali" class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="w-10 py-3 pl-5"><input type="checkbox" data-check-all="#tabel-kembali" class="size-4 rounded border-slate-300" aria-label="Pilih semua pengembalian"></th>
                                <th scope="col" class="px-3 py-3">ID &amp; waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota peminjam</th>
                                <th scope="col" class="px-3 py-3">Buku &amp; kondisi fisik</th>
                                <th scope="col" class="px-3 py-3">Status jatuh tempo</th>
                                <th scope="col" class="px-3 py-3">Kalkulasi denda</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($antrean as $r)
                                <tr class="align-top" data-id="{{ $r['id'] }}">
                                    <td class="py-4 pl-5"><input type="checkbox" data-row-check class="size-4 rounded border-slate-300" aria-label="Pilih {{ $r['id'] }}"></td>
                                    <td class="px-3 py-4">
                                        <p class="font-bold text-blue-700">{{ $r['id'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $r['waktu'] }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $r['nama'] }}</p>
                                        <p class="text-xs text-slate-500">NIM {{ $r['nim'] }} • {{ $r['prodi'] }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold leading-snug">{{ $r['judul'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $r['kode'] }}</p>
                                        @if ($r['kondisi'] === 'baik')
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                                @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-3.5']) Fisik: baik
                                            </span>
                                        @else
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">
                                                @include('petugas.partials.icon', ['name' => 'wrench', 'class' => 'size-3.5']) Rusak: {{ $r['catatan'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-xs">
                                        <p class="text-slate-600">Tempo: {{ $r['tempo'] }}</p>
                                        @if ($r['telat'] > 0)
                                            <span class="mt-2 inline-flex items-center gap-1 rounded-md bg-red-100 px-2 py-1 font-semibold text-red-700">
                                                @include('petugas.partials.icon', ['name' => 'clock', 'class' => 'size-3.5']) Terlambat {{ $r['telat'] }} hari
                                            </span>
                                        @elseif ($r['awal'])
                                            <span class="mt-2 inline-flex rounded-md bg-blue-50 px-2 py-1 font-semibold text-blue-700">Lebih cepat (H-2)</span>
                                        @else
                                            <span class="mt-2 inline-flex rounded-md bg-emerald-50 px-2 py-1 font-semibold text-emerald-700">Tepat waktu</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        @if ($r['denda'] > 0)
                                            <p class="font-bold text-red-600">{{ $rp($r['denda']) }}</p>
                                            <p class="text-xs text-slate-500">{{ $r['telat'] }} hr × {{ $rp($tarif) }}</p>
                                            <span class="mt-1 inline-flex rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">{{ $r['kondisi'] === 'rusak' ? '+ catatan rusak (kompensasi)' : 'Belum bayar' }}</span>
                                        @else
                                            <p class="font-bold text-blue-700">Rp 0</p>
                                            <span class="mt-1 inline-flex rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">Bebas denda</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 pr-5 text-right">
                                        @if ($r['aksi'] === 'tagih')
                                            <button type="button" data-demo-click="Contoh: {{ $r['id'] }} diterima, denda {{ $rp($r['denda']) }} ditagih. Terhubung ke backend nanti."
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                                                @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-3.5']) Terima &amp; Tagih Denda
                                            </button>
                                        @elseif ($r['aksi'] === 'sesuaikan')
                                            <button type="button" data-demo-click="Contoh: buku {{ $r['kode'] }} dicatat rusak untuk penyesuaian katalog (UC-05)."
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-800 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-900">
                                                @include('petugas.partials.icon', ['name' => 'wrench', 'class' => 'size-3.5']) Sesuaikan Buku (UC-05)
                                            </button>
                                        @else
                                            <button type="button" data-demo-click="Contoh: {{ $r['id'] }} diterima, stok buku +1."
                                                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold text-blue-700 ring-1 ring-blue-300 hover:bg-blue-50">
                                                @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-3.5']) Terima Buku (+1 Stok)
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @include('petugas.partials.pagination', ['from' => 1, 'to' => 5, 'total' => 8, 'noun' => 'antrean pengembalian', 'last' => 2])
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Loket penerimaan dan log">
            <section id="loket" class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-blue-700 text-white">@include('petugas.partials.icon', ['name' => 'return'])</span>
                        <div>
                            <h2 class="text-sm font-extrabold leading-tight">Loket Penerimaan Langsung (Meja Fisik)</h2>
                            <p class="text-xs text-slate-500">Proses pengembalian mandiri</p>
                        </div>
                    </div>
                    <span class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Loket 1</span>
                </div>

                {{-- TODO backend: arahkan ke route pengembalian dari Herlyan, mis. route('petugas.pengembalian.store') --}}
                <form method="POST" action="#" id="form-kembali" data-demo="Tampilan contoh: konfirmasi pengembalian belum terhubung ke backend."
                      data-tarif="{{ $tarif }}" data-hari="3" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="kode-buku" class="text-xs font-semibold">Scan barcode / ID buku</label>
                        <input id="kode-buku" name="kode_buku" value="BK-0104" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                    </div>
                    <div>
                        <label for="kode-anggota" class="text-xs font-semibold">Scan KTM / NIM anggota</label>
                        <input id="kode-anggota" name="kode_anggota" value="AG-0847 - Farhan Maulana" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                    </div>

                    <fieldset>
                        <legend class="text-xs font-semibold">Kondisi fisik buku</legend>
                        <div class="mt-2 grid grid-cols-3 gap-1 rounded-xl bg-[#EEF3FC] p-1 text-sm font-semibold">
                            @foreach (['baik' => 'Baik (+1)', 'rusak' => 'Rusak', 'hilang' => 'Hilang'] as $nilai => $label)
                                <label class="cursor-pointer rounded-lg py-2 text-center text-slate-600 has-[:checked]:bg-white has-[:checked]:text-blue-700 has-[:checked]:shadow-sm has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-blue-600">
                                    <input type="radio" name="kondisi" value="{{ $nilai }}" @checked($nilai === 'baik') class="sr-only">{{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <p id="catatan-kondisi" class="mt-2 text-xs text-slate-500">Buku kondisi baik menambah stok rak (+1).</p>
                    </fieldset>

                    <div class="rounded-xl bg-[#EEF3FC] p-4 text-xs">
                        <div class="flex justify-between gap-2"><span class="text-slate-500">Tgl pinjam</span><b>14 Okt 2024</b></div>
                        <div class="mt-1 flex justify-between gap-2"><span class="text-slate-500">Jatuh tempo</span><b>21 Okt 2024</b></div>
                        <p id="hitung-denda" class="mt-3 font-semibold text-red-600">Terlambat: 3 hari × {{ $rp($tarif) }} = {{ $rp(3 * $tarif) }}</p>
                        <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3 text-sm">
                            <b>Total denda:</b>
                            <b id="total-denda" class="text-lg text-red-600">{{ $rp(3 * $tarif) }}</b>
                        </div>
                    </div>

                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                        @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-4']) Konfirmasi Pengembalian &amp; Cetak Bukti
                    </button>
                </form>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                        Log validasi pengembalian hari ini
                    </h2>
                    <span class="text-xs text-slate-500">{{ \Illuminate\Support\Str::words($namaPetugas, 1, '') }}</span>
                </div>
                <ul class="mt-4 space-y-4 text-xs">
                    @foreach ($log as $l)
                        <li class="flex gap-3">
                            <span @class(['mt-1 size-2 shrink-0 rounded-full', 'bg-blue-600' => $l['ok'], 'bg-amber-600' => ! $l['ok']])></span>
                            <div>
                                <p class="font-mono text-slate-500">{{ $l['jam'] }}</p>
                                <p class="mt-0.5 text-slate-700">{{ $l['isi'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>

    <section class="mt-6 rounded-2xl bg-[#EEF3FC] p-5" aria-labelledby="judul-sop">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 id="judul-sop" class="flex items-center gap-2 text-sm font-bold text-blue-800">
                @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5'])
                Alur validasi SOP sesuai activity diagram (UC-08 &amp; UC-09)
            </h2>
            <span class="rounded-full bg-blue-700 px-3 py-1 text-xs font-semibold text-white">Standar prosedur</span>
        </div>
        <ol class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($sop as $n => [$judul, $isi])
                <li class="rounded-xl bg-white p-4">
                    <p class="flex items-center gap-2 text-sm font-bold">
                        <span class="grid size-6 place-items-center rounded-full bg-blue-100 text-xs text-blue-700">{{ $n + 1 }}</span>{{ $judul }}
                    </p>
                    <p class="mt-2 text-xs text-slate-600">{{ $isi }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-kembali');
    const catatan = document.getElementById('catatan-kondisi');
    const teks = {
        baik: 'Buku kondisi baik menambah stok rak (+1).',
        rusak: 'Buku rusak dialihkan ke penyesuaian katalog (UC-05) dan tidak menambah stok.',
        hilang: 'Buku hilang dialihkan ke penyesuaian katalog (UC-05) dan tidak menambah stok.',
    };
    form.querySelectorAll('input[name="kondisi"]').forEach((radio) =>
        radio.addEventListener('change', () => { catatan.textContent = teks[radio.value]; }));
})();
</script>
@endpush
