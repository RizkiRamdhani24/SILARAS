@extends('petugas.layout')

@section('title', 'Riwayat Transaksi')

@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');

    // TODO backend (Herlyan): ganti dengan riwayat dari tabel peminjaman + pengembalian (+ pembayaran denda).
    $trx = [
        ['id' => 'TRX-2024-1042', 'ref' => 'PKM-2024-0412', 'jenis' => 'Pengembalian', 'cat' => 'pengembalian', 'waktu' => '21 Okt 2024 • 10:14 WIB', 'nama' => 'Farhan Maulana', 'nim' => '2201082012 (AG-0312)', 'judul' => 'Rekayasa Perangkat Lunak Berorientasi Objek', 'buku' => 'BK-0089 • Roger S. Pressman', 'petugas' => 'Budi Santoso', 'kode' => 'PTG-01 (shift pagi)', 'nilai' => 6000, 'ket' => 'Telat 3 hari (tunai)'],
        ['id' => 'TRX-2024-1041', 'ref' => 'PMJ-2024-0891', 'jenis' => 'Peminjaman', 'cat' => 'peminjaman', 'waktu' => '21 Okt 2024 • 09:48 WIB', 'nama' => 'Annisa Rahmawati', 'nim' => '2102041005 (AG-0204)', 'judul' => 'Basis Data Relasional & SQL', 'buku' => 'BK-0142 • Abdul Kadir', 'petugas' => 'Budi Santoso', 'kode' => 'PTG-01 (shift pagi)', 'nilai' => 0, 'ket' => 'Jatuh tempo 28 Okt'],
        ['id' => 'TRX-2024-1040', 'ref' => 'PKM-2024-0411', 'jenis' => 'Pengembalian', 'cat' => 'pengembalian', 'waktu' => '21 Okt 2024 • 09:25 WIB', 'nama' => 'Deni Setiawan', 'nim' => '2304015099 (AG-0518)', 'judul' => 'Algoritma & Struktur Data C++', 'buku' => 'BK-0034 • Rinaldi Munir', 'petugas' => 'Siti Nurhaliza', 'kode' => 'PTG-02 (shift pagi)', 'nilai' => 0, 'ket' => 'Tepat waktu'],
        ['id' => 'TRX-2024-1039', 'ref' => 'DND-2024-0089', 'jenis' => 'Bayar denda', 'cat' => 'denda', 'waktu' => '21 Okt 2024 • 08:52 WIB', 'nama' => 'Rizky Prasetyo', 'nim' => '2203011084 (AG-0199)', 'judul' => 'Jaringan Komputer & Keamanan', 'buku' => 'BK-0211 • Andrew S. Tanenbaum', 'petugas' => 'Budi Santoso', 'kode' => 'PTG-01 (shift pagi)', 'nilai' => 10000, 'ket' => 'Telat 5 hari (tunai)'],
        ['id' => 'TRX-2024-1038', 'ref' => 'PMJ-2024-0890', 'jenis' => 'Peminjaman', 'cat' => 'peminjaman', 'waktu' => '21 Okt 2024 • 08:35 WIB', 'nama' => 'Tri Wahyuni', 'nim' => '2001053041 (AG-0082)', 'judul' => 'Metodologi Penelitian Komputer', 'buku' => 'BK-0051 • Jogiyanto HM', 'petugas' => 'Siti Nurhaliza', 'kode' => 'PTG-02 (shift pagi)', 'nilai' => 0, 'ket' => 'Jatuh tempo 28 Okt'],
        ['id' => 'TRX-2024-1037', 'ref' => 'PKM-2024-0410', 'jenis' => 'Pengembalian', 'cat' => 'pengembalian', 'waktu' => '21 Okt 2024 • 08:18 WIB', 'nama' => 'Maya Anggraini', 'nim' => '2202024019 (AG-0411)', 'judul' => 'Statistika Terapan Sains Data', 'buku' => 'BK-0095 • Sudjana', 'petugas' => 'Budi Santoso', 'kode' => 'PTG-01 (shift pagi)', 'nilai' => 0, 'ket' => 'Tepat waktu'],
    ];

    $jenisKelas = [
        'Pengembalian' => 'bg-blue-100 text-blue-700',
        'Peminjaman' => 'bg-indigo-100 text-indigo-700',
        'Bayar denda' => 'bg-amber-100 text-amber-800',
    ];

    $shift = [
        ['nama' => 'Budi Santoso (PTG-01)', 'label' => 'Shift pagi (aktif)', 'aktif' => true, 'info' => ['Pinjam: 42', 'Kembali: 28', 'Denda: Rp 145k']],
        ['nama' => 'Siti Nurhaliza (PTG-02)', 'label' => 'Shift pagi (aktif)', 'aktif' => true, 'info' => ['Pinjam: 38', 'Kembali: 24', 'Denda: Rp 90k']],
        ['nama' => 'Hendra Gunawan (PTG-03)', 'label' => 'Shift sore (mendatang)', 'aktif' => false, 'info' => []],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Riwayat Transaksi &amp; Audit Sirkulasi</h1>
            <p class="mt-1 text-sm text-slate-600">Pusat rekaman historis seluruh transaksi peminjaman, pengembalian buku, dan rekonsiliasi denda yang divalidasi oleh petugas loket sirkulasi secara terpusat.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: filter periode belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                @include('petugas.partials.icon', ['name' => 'calendar', 'class' => 'size-4']) Filter Periode Tanggal
            </button>
            <button type="button" data-demo-click="Tampilan contoh: cetak rekap belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-100">
                @include('petugas.partials.icon', ['name' => 'printer', 'class' => 'size-4']) Cetak Rekap Bulanan
            </button>
            <button type="button" data-demo-click="Tampilan contoh: ekspor log belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'download', 'class' => 'size-4']) Ekspor Log CSV/Excel
            </button>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan transaksi">
        @include('petugas.partials.stat-card', ['label' => 'Total transaksi', 'value' => '1.428', 'icon' => 'dashboard', 'tone' => 'blue', 'footer' => '+246 bulan ini • 100% tercatat di database', 'footerTone' => 'text-blue-700'])
        @include('petugas.partials.stat-card', ['label' => 'Peminjaman keseluruhan', 'value' => '892', 'icon' => 'borrow', 'tone' => 'blue', 'footer' => '812 selesai dipinjam • 80 berjalan'])
        @include('petugas.partials.stat-card', ['label' => 'Pengembalian buku', 'value' => '812', 'icon' => 'return', 'tone' => 'blue', 'footer' => '745 tepat waktu • 67 terlambat'])
        @include('petugas.partials.stat-card', ['label' => 'Kas denda terkumpul', 'value' => $rp(1840000), 'icon' => 'cash', 'tone' => 'blue', 'valueTone' => 'text-blue-700', 'footer' => 'Lunas di kasir • 0 tunggakan', 'footerTone' => 'text-blue-700'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar transaksi</h2>
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div data-tabs="#tabel-transaksi" class="flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach (['all' => 'Semua transaksi (1.428)', 'peminjaman' => 'Peminjaman (892)', 'pengembalian' => 'Pengembalian (812)', 'denda' => 'Pembayaran denda (67)'] as $nilai => $label)
                        <button type="button" data-tab="{{ $nilai }}" data-active="{{ $nilai === 'all' ? 'true' : 'false' }}" aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                                class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <label class="relative min-w-52 flex-1">
                        <span class="sr-only">Cari ID transaksi, nama/NIM, judul, atau ID petugas</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" data-search="#tabel-transaksi" placeholder="Cari ID transaksi, nama/NIM, judul, atau ID petugas"
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <label><span class="sr-only">Periode</span>
                        <select class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none"><option>Bulan Oktober 2024</option><option>September 2024</option></select>
                    </label>
                    <label><span class="sr-only">Status</span>
                        <select class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none"><option>Semua status</option><option>Selesai</option><option>Berjalan</option></select>
                    </label>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-transaksi" class="w-full min-w-[56rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID transaksi &amp; ref</th>
                                <th scope="col" class="px-3 py-3">Jenis &amp; waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota (NIM/ID)</th>
                                <th scope="col" class="px-3 py-3">Buku terkait</th>
                                <th scope="col" class="px-3 py-3">Petugas validasi</th>
                                <th scope="col" class="px-3 py-3 pr-5">Denda / nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($trx as $i => $t)
                                <tr class="cursor-pointer align-top hover:bg-[#F7F9FD]" data-cat="{{ $t['cat'] }}" data-inspect="{{ $i }}" tabindex="0" aria-label="Inspeksi {{ $t['id'] }}">
                                    <td class="px-5 py-4"><p class="font-bold text-blue-700">{{ $t['id'] }}</p><p class="text-xs text-slate-500">Ref: {{ $t['ref'] }}</p></td>
                                    <td class="px-3 py-4"><span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $jenisKelas[$t['jenis']] }}">{{ $t['jenis'] }}</span><p class="mt-1 text-xs text-slate-500">{{ $t['waktu'] }}</p></td>
                                    <td class="px-3 py-4"><p class="font-semibold">{{ $t['nama'] }}</p><p class="text-xs text-slate-500">NIM: {{ $t['nim'] }}</p></td>
                                    <td class="px-3 py-4"><p>{{ $t['judul'] }}</p><p class="text-xs text-slate-500">ID: {{ $t['buku'] }}</p></td>
                                    <td class="px-3 py-4"><p class="font-medium">{{ $t['petugas'] }}</p><p class="text-xs text-slate-500">{{ $t['kode'] }}</p></td>
                                    <td class="px-3 py-4 pr-5">
                                        <p @class(['font-bold', 'text-red-600' => $t['nilai'] > 0])>{{ $rp($t['nilai']) }}</p>
                                        <p class="text-xs text-slate-500">{{ $t['ket'] }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('petugas.partials.pagination', ['from' => 1, 'to' => 6, 'total' => '1.428', 'noun' => 'riwayat transaksi', 'last' => 48])
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Inspeksi dan aktivitas shift">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-inspeksi">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-inspeksi" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-5 text-blue-700']) Inspeksi Transaksi Terpilih
                    </h2>
                    <span id="in-id" class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700"></span>
                </div>
                <p class="mt-1 text-xs text-slate-500">Klik salah satu baris pada tabel untuk melihat rinciannya.</p>

                <div class="mt-4 rounded-xl bg-[#EEF3FC] p-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Peminjam</p>
                    <p id="in-nama" class="font-bold"></p>
                    <p id="in-nim" class="text-xs text-slate-500"></p>
                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Buku dipinjam</p>
                    <p id="in-judul" class="font-semibold"></p>
                    <p id="in-buku" class="text-xs text-slate-500"></p>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Jenis transaksi</dt><dd id="in-jenis" class="font-semibold"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Waktu</dt><dd id="in-waktu" class="text-right font-semibold"></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Keterangan</dt><dd id="in-ket" class="text-right font-semibold"></dd></div>
                </dl>

                <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm">
                    <div class="flex items-center justify-between gap-3 text-xs"><span class="font-semibold text-red-700">Total dibayarkan</span><b id="in-nilai" class="text-lg text-red-700"></b></div>
                </div>

                <p class="mt-4 rounded-xl bg-[#F3F5FA] p-3 text-xs text-slate-600">Petugas validasi loket: <b id="in-petugas" class="text-[#0F1B3D]"></b> <span id="in-kode"></span></p>

                <button type="button" data-demo-click="Tampilan contoh: cetak salinan belum terhubung ke backend."
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                    @include('petugas.partials.icon', ['name' => 'printer', 'class' => 'size-4']) Cetak Salinan Bukti Transaksi
                </button>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-shift">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-shift" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-5 text-blue-700']) Aktivitas Shift Petugas
                    </h2>
                    <span class="text-xs text-slate-500">Hari ini</span>
                </div>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($shift as $s)
                        <li @class(['rounded-xl p-3', 'bg-[#EEF3FC]' => $s['aktif'], 'bg-[#F3F5FA]' => ! $s['aktif']])>
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold">{{ $s['nama'] }}</p>
                                <span @class(['rounded-md px-2 py-0.5 text-xs font-semibold', 'bg-blue-100 text-blue-700' => $s['aktif'], 'bg-slate-200 text-slate-500' => ! $s['aktif']])>{{ $s['label'] }}</span>
                            </div>
                            @if ($s['info'])
                                <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">@foreach ($s['info'] as $info)<span>{{ $info }}</span>@endforeach</p>
                            @else
                                <p class="mt-1 text-xs text-slate-500">Jadwal bertugas pukul 15:30 - 21:00 WIB</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    // Data baris untuk panel inspeksi (diambil dari PHP agar tidak perlu parsing HTML)
    const data = @json($trx);
    const rupiah = (n) => 'Rp ' + n.toLocaleString('id-ID');
    const set = (id, teks) => { document.getElementById(id).textContent = teks; };

    const tampilkan = (i) => {
        const t = data[i];
        set('in-id', t.id); set('in-nama', t.nama); set('in-nim', 'NIM: ' + t.nim);
        set('in-judul', t.judul); set('in-buku', 'ID: ' + t.buku);
        set('in-jenis', t.jenis); set('in-waktu', t.waktu); set('in-ket', t.ket);
        set('in-nilai', rupiah(t.nilai)); set('in-petugas', t.petugas); set('in-kode', '(' + t.kode + ')');
        document.querySelectorAll('#tabel-transaksi tbody tr').forEach((tr) => {
            tr.classList.toggle('bg-blue-50/60', tr.dataset.inspect === String(i));
        });
    };

    document.querySelectorAll('#tabel-transaksi tbody tr').forEach((tr) => {
        tr.addEventListener('click', () => tampilkan(tr.dataset.inspect));
        tr.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); tampilkan(tr.dataset.inspect); }
        });
    });
    tampilkan(0);
})();
</script>
@endpush
