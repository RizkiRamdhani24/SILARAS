@extends('petugas.layout')

@section('title', 'Kelola Buku')

@php
    // TODO backend (Exel): ganti dengan data dari tabel buku (judul_buku, pengarang, penerbit, kategori, stock).
    // Catatan: ISBN, rak, dan edisi belum ada di tabel buku; tampilan ini memakai data contoh.
    $buku = [
        ['kode' => 'BK-0101', 'judul' => 'Artificial Intelligence: A Modern Approach', 'rak' => 'CS-301', 'isbn' => '978-0136042594', 'pengarang' => 'Stuart Russell, Peter Norvig', 'edisi' => 'Edisi 4 Revisi', 'penerbit' => 'Pearson Education', 'kategori' => 'Komputer', 'stok' => 1],
        ['kode' => 'BK-0102', 'judul' => 'Komunikasi Data dan Jaringan', 'rak' => 'CS-301', 'isbn' => '978-0072967753', 'pengarang' => 'Behrouz A. Forouzan', 'edisi' => 'Edisi Bahasa Indonesia', 'penerbit' => 'McGraw-Hill', 'kategori' => 'Komputer', 'stok' => 0],
        ['kode' => 'BK-0103', 'judul' => 'Clean Architecture', 'rak' => 'CS-102', 'isbn' => '978-0134494166', 'pengarang' => 'Robert C. Martin', 'edisi' => 'Software Craftsmanship Series', 'penerbit' => 'Prentice Hall', 'kategori' => 'Komputer', 'stok' => 1],
        ['kode' => 'BK-0104', 'judul' => 'Database System Concepts', 'rak' => 'CS-301', 'isbn' => '978-0078022159', 'pengarang' => 'Silberschatz, Korth, Sudarshan', 'edisi' => 'Edisi Internasional', 'penerbit' => 'McGraw-Hill Education', 'kategori' => 'Komputer', 'stok' => 4],
        ['kode' => 'BK-0105', 'judul' => 'Filosofi Teras', 'rak' => 'PH-104', 'isbn' => '978-6024125189', 'pengarang' => 'Henry Manampiring', 'edisi' => 'Filsafat Stoa Populer', 'penerbit' => 'Buku Kompas', 'kategori' => 'Filsafat', 'stok' => 6],
        ['kode' => 'BK-0106', 'judul' => 'Laskar Pelangi', 'rak' => 'FK-012', 'isbn' => '978-9791227032', 'pengarang' => 'Andrea Hirata', 'edisi' => 'Koleksi Sastra Indonesia', 'penerbit' => 'Bentang Pustaka', 'kategori' => 'Sastra', 'stok' => 3],
        ['kode' => 'BK-0107', 'judul' => 'Pengantar Psikologi Kognitif', 'rak' => 'PS-202', 'isbn' => '978-0132350884', 'pengarang' => 'Robert L. Solso', 'edisi' => 'Edisi Kedelapan', 'penerbit' => 'Erlangga', 'kategori' => 'Psikologi', 'stok' => 6],
    ];

    $log = [
        ['oleh' => 'Petugas Budi Santoso', 'waktu' => '10 mnt lalu', 'isi' => 'Menambahkan +5 stok fisik pada buku', 'kode' => 'BK-0105', 'nama' => 'Filosofi Teras', 'ikon' => 'plus'],
        ['oleh' => 'Petugas Budi Santoso', 'waktu' => '34 mnt lalu', 'isi' => 'Memperbarui nama pengarang & penerbit untuk buku', 'kode' => 'BK-0102', 'nama' => null, 'ikon' => 'pencil'],
        ['oleh' => 'Petugas Siti Nurhaliza', 'waktu' => '2 jam lalu', 'isi' => 'Registrasi judul baru:', 'kode' => 'BK-0107', 'nama' => 'Pengantar Psikologi Kognitif', 'ikon' => 'book'],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Data Buku &amp; Stok Pustaka</h1>
            <p class="mt-1 text-sm text-slate-600">Manajemen master data buku inventaris SILARAS: tambah judul baru, perbarui data pengarang/penerbit, sesuaikan stok fisik, dan hapus koleksi secara real-time.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: ekspor belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'download', 'class' => 'size-4']) Export Excel / CSV
            </button>
            <button type="button" data-demo-click="Tampilan contoh: cetak barcode belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'qr', 'class' => 'size-4']) Cetak Barcode Buku
            </button>
            <a href="#form-buku" class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4']) Tambah Buku Baru
            </a>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan koleksi">
        @include('petugas.partials.stat-card', ['label' => 'Total judul buku', 'value' => '15.420', 'icon' => 'book', 'tone' => 'blue', 'footer' => 'Judul aktif terdaftar di katalog', 'footerTone' => 'text-blue-700'])
        @include('petugas.partials.stat-card', ['label' => 'Total eksemplar fisik', 'value' => '42.180', 'icon' => 'dashboard', 'tone' => 'blue', 'footer' => 'Total unit buku fisik di rak', 'footerTone' => 'text-blue-700'])
        @include('petugas.partials.stat-card', ['label' => 'Stok kritis (≤ 1)', 'value' => '18', 'icon' => 'alert', 'tone' => 'red', 'valueTone' => 'text-red-600', 'labelTone' => 'text-red-600', 'footer' => 'Perlu pengadaan / restok segera', 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Sirkulasi dipinjam', 'value' => '342', 'icon' => 'borrow', 'tone' => 'blue', 'footer' => 'Sedang dalam peminjaman aktif', 'footerTone' => 'text-blue-700'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar buku</h2>
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-48 flex-1">
                        <span class="sr-only">Cari judul, pengarang, atau ID buku</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" data-search="#tabel-buku" placeholder="Cari judul, pengarang, ID buku..."
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <div data-tabs="#tabel-buku" class="flex flex-wrap gap-2 text-sm font-semibold">
                        @foreach (['all' => 'Semua kategori', 'Komputer' => 'Komputer', 'Filsafat' => 'Filsafat', 'Sastra' => 'Sastra', 'Psikologi' => 'Psikologi'] as $nilai => $label)
                            <button type="button" data-tab="{{ $nilai }}" data-active="{{ $nilai === 'all' ? 'true' : 'false' }}" aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                                    class="rounded-xl bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    Filter aktif:
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">Koleksi fisik terdaftar</span>
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">Stok aktif</span>
                    <span class="ml-auto"><b class="text-blue-700" id="jumlah-dipilih">0</b> buku terpilih</span>
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-buku" class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="w-10 py-3 pl-5"><input type="checkbox" data-check-all="#tabel-buku" class="size-4 rounded border-slate-300" aria-label="Pilih semua buku"></th>
                                <th scope="col" class="px-3 py-3">ID buku</th>
                                <th scope="col" class="px-3 py-3">Cover &amp; judul buku</th>
                                <th scope="col" class="px-3 py-3">Pengarang</th>
                                <th scope="col" class="px-3 py-3">Penerbit</th>
                                <th scope="col" class="px-3 py-3 text-center">Stok</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($buku as $i => $b)
                                <tr @class(['align-middle', 'bg-red-50/60' => $b['stok'] == 0]) data-cat="{{ $b['kategori'] }}">
                                    <td class="py-4 pl-5"><input type="checkbox" data-row-check class="size-4 rounded border-slate-300" aria-label="Pilih {{ $b['judul'] }}"></td>
                                    <td class="px-3 py-4">
                                        <span @class(['rounded-md px-2 py-1 text-xs font-bold', 'bg-red-100 text-red-700' => $b['stok'] == 0, 'bg-blue-50 text-blue-700' => $b['stok'] > 0])>{{ $b['kode'] }}</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-3">
                                            @include('petugas.partials.cover', ['i' => $i])
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">{{ $b['judul'] }}</p>
                                                <p class="text-xs text-slate-500">Rak {{ $b['rak'] }} • ISBN {{ $b['isbn'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4"><p>{{ $b['pengarang'] }}</p><p class="text-xs text-slate-500">{{ $b['edisi'] }}</p></td>
                                    <td class="px-3 py-4">{{ $b['penerbit'] }}</td>
                                    <td class="px-3 py-4 text-center">
                                        <span @class([
                                            'inline-flex min-w-8 justify-center rounded-md px-2 py-1 text-xs font-bold',
                                            'bg-red-100 text-red-700' => $b['stok'] <= 1,
                                            'bg-emerald-50 text-emerald-700' => $b['stok'] > 1,
                                        ])>{{ $b['stok'] }}</span>
                                    </td>
                                    <td class="px-3 py-4 pr-5">
                                        <div class="flex justify-end gap-1">
                                            <button type="button" data-edit data-judul="{{ $b['judul'] }}" data-pengarang="{{ $b['pengarang'] }}" data-penerbit="{{ $b['penerbit'] }}" data-stok="{{ $b['stok'] }}" data-kategori="{{ $b['kategori'] }}" data-isbn="{{ $b['isbn'] }}" data-rak="{{ $b['rak'] }}"
                                                    class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-700" aria-label="Ubah {{ $b['judul'] }}">
                                                @include('petugas.partials.icon', ['name' => 'pencil', 'class' => 'size-4'])
                                            </button>
                                            <button type="button" data-demo-click="Tampilan contoh: hapus {{ $b['kode'] }} belum terhubung ke backend."
                                                    class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600" aria-label="Hapus {{ $b['judul'] }}">
                                                @include('petugas.partials.icon', ['name' => 'trash', 'class' => 'size-4'])
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('petugas.partials.pagination', ['from' => 1, 'to' => 7, 'total' => '15.420', 'noun' => 'data buku', 'last' => 1542])
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Formulir buku dan log stok">
            <section id="form-buku" class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-form">
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judul-form" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5 text-blue-700']) Form Cepat Input / Edit Buku
                    </h2>
                    <span id="mode-form" class="shrink-0 rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Mode tambah</span>
                </div>

                {{-- TODO backend (Exel): arahkan ke route simpan/ubah buku, mis. route('petugas.buku.store') --}}
                <form method="POST" action="#" id="form-buku-el" data-demo="Tampilan contoh: penyimpanan buku belum terhubung ke backend." class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label for="judul_buku" class="text-sm font-semibold">Judul buku <span class="text-red-600">*</span></label>
                        <input id="judul_buku" name="judul_buku" required placeholder="Masukkan judul buku lengkap..." class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="pengarang" class="text-sm font-semibold">Pengarang <span class="text-red-600">*</span></label>
                            <input id="pengarang" name="pengarang" required placeholder="Nama penulis" class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                        <div>
                            <label for="penerbit" class="text-sm font-semibold">Penerbit <span class="text-red-600">*</span></label>
                            <input id="penerbit" name="penerbit" required placeholder="Nama penerbit" class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="stock" class="text-sm font-semibold">Jumlah stok <span class="text-red-600">*</span></label>
                            <div class="mt-1 flex items-center gap-1 rounded-xl bg-[#EEF1F8] p-1">
                                <button type="button" data-step="-1" class="grid size-9 place-items-center rounded-lg bg-white text-blue-700 shadow-sm" aria-label="Kurangi stok">@include('petugas.partials.icon', ['name' => 'minus', 'class' => 'size-4'])</button>
                                <input id="stock" name="stock" type="number" min="0" value="5" class="w-full border-0 bg-transparent p-0 text-center text-sm font-bold focus:ring-0 focus:outline-none">
                                <button type="button" data-step="1" class="grid size-9 place-items-center rounded-lg bg-white text-blue-700 shadow-sm" aria-label="Tambah stok">@include('petugas.partials.icon', ['name' => 'plus', 'class' => 'size-4'])</button>
                            </div>
                        </div>
                        <div>
                            <label for="kategori" class="text-sm font-semibold">Kategori subjek</label>
                            <select id="kategori" name="kategori" class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-3 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                                <option>Komputer</option><option>Filsafat</option><option>Sastra</option><option>Psikologi</option><option>Manajemen</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-semibold">Nomor ISBN &amp; lokasi rak</p>
                        <div class="mt-1 grid grid-cols-2 gap-3">
                            <input id="isbn" name="isbn" aria-label="Nomor ISBN" placeholder="ISBN (opsional)" class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <input id="rak" name="rak" aria-label="Kode rak" placeholder="Kode rak (mis. CS-301)" class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                        </div>
                    </div>
                    <p class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900"><b>Postcondition:</b> data buku dan stok fisik akan tersinkronisasi otomatis pada katalog pencarian mahasiswa SILARAS.</p>
                    <div class="flex gap-2">
                        <button type="submit" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                            @include('petugas.partials.icon', ['name' => 'save', 'class' => 'size-4']) Simpan Data Buku
                        </button>
                        <button type="reset" id="batal-form" class="rounded-xl bg-[#EEF3FC] px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-blue-100">Batal</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-log" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700']) Log Perubahan Stok Terakhir
                    </h2>
                    <span class="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">Sistem sinkron</span>
                </div>
                <ul class="mt-4 space-y-3 text-xs">
                    @foreach ($log as $l)
                        <li class="flex gap-3 rounded-xl bg-[#EEF3FC] p-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-white text-blue-700">@include('petugas.partials.icon', ['name' => $l['ikon'], 'class' => 'size-4'])</span>
                            <div>
                                <p class="flex flex-wrap items-center gap-2"><b class="text-sm">{{ $l['oleh'] }}</b><span class="text-slate-500">{{ $l['waktu'] }}</span></p>
                                <p class="mt-0.5 text-slate-600">{{ $l['isi'] }} <b class="text-blue-700">{{ $l['kode'] }}</b>@if ($l['nama']) ({{ $l['nama'] }})@endif.</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ url('/petugas/transaksi') }}" class="mt-4 block text-center text-xs font-semibold text-blue-700 hover:underline">Lihat seluruh log transaksi stok →</a>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-buku-el');
    const mode = document.getElementById('mode-form');
    const stok = document.getElementById('stock');

    // Tombol +/- pada stok
    form.querySelectorAll('[data-step]').forEach((btn) => btn.addEventListener('click', () => {
        stok.value = Math.max(0, (parseInt(stok.value, 10) || 0) + parseInt(btn.dataset.step, 10));
    }));

    // Tombol ubah: isi form dengan data baris
    document.querySelectorAll('[data-edit]').forEach((btn) => btn.addEventListener('click', () => {
        const d = btn.dataset;
        form.judul_buku.value = d.judul;
        form.pengarang.value = d.pengarang;
        form.penerbit.value = d.penerbit;
        form.stock.value = d.stok;
        form.kategori.value = d.kategori;
        form.isbn.value = d.isbn;
        form.rak.value = d.rak;
        mode.textContent = 'Mode ubah';
        document.getElementById('form-buku').scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
        form.judul_buku.focus();
    }));

    form.addEventListener('reset', () => { mode.textContent = 'Mode tambah'; });

    // Jumlah buku terpilih
    const hitung = () => { document.getElementById('jumlah-dipilih').textContent = document.querySelectorAll('#tabel-buku [data-row-check]:checked').length; };
    document.getElementById('tabel-buku').addEventListener('change', hitung);
})();
</script>
@endpush
