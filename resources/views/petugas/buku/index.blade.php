@extends('petugas.layout')

@section('title', 'Kelola Buku')

@php
    use App\Models\Buku;
    use App\Models\Peminjaman;
    use Illuminate\Support\Facades\Schema;
    use Illuminate\Support\Facades\Storage;

    $tabelBukuAda = Schema::hasTable('buku');
    $tabelPeminjamanAda = Schema::hasTable('peminjaman');

    $buku = $tabelBukuAda
        ? Buku::query()->orderBy('judul_buku')->get()->map(function ($book) {
            return [
                'id' => $book->id_buku,
                'kode' => 'BK-' . $book->id_buku,
                'judul' => $book->judul_buku,
                'rak' => $book->rak ?? '-',
                'isbn' => $book->isbn ?? '-',
                'pengarang' => $book->pengarang,
                'penerbit' => $book->penerbit ?? '-',
                'kategori' => $book->kategori ?: 'Umum',
                'stok' => (int) $book->stock,
                'cover' => $book->cover,
                'cover_url' => $book->cover
                    ? Storage::disk('public')->url($book->cover)
                    : null,
                'pdf' => $book->pdf,
            ];
        })->all()
        : [];

    $totalJudul = count($buku);
    $totalStok = collect($buku)->sum('stok');
    $totalStokKritis = collect($buku)->where('stok', '<=', 1)->count();

    $totalDipinjam = $tabelPeminjamanAda
        ? Peminjaman::query()
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            ])
            ->count()
        : 0;

    $kategoriBuku = collect($buku)
        ->pluck('kategori')
        ->filter()
        ->unique()
        ->sort()
        ->values();

    $kategoriFilter = $kategoriBuku->mapWithKeys(fn ($kategori) => [
        $kategori => $kategori,
    ])->all();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Kelola Data Buku &amp; Stok Pustaka
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Kelola koleksi buku perpustakaan, perbarui informasi buku, atur stok fisik,
                serta unggah cover dan file PDF buku.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="#form-buku"
                class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
            >
                @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4'])
                Tambah Buku Baru
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-bold">Data buku belum berhasil disimpan.</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan koleksi">
        @include('petugas.partials.stat-card', [
            'label' => 'Total judul buku',
            'value' => number_format($totalJudul, 0, ',', '.'),
            'icon' => 'book',
            'tone' => 'blue',
            'footer' => 'Judul buku terdaftar di katalog',
            'footerTone' => 'text-blue-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Total eksemplar fisik',
            'value' => number_format($totalStok, 0, ',', '.'),
            'icon' => 'dashboard',
            'tone' => 'blue',
            'footer' => 'Jumlah seluruh stok buku',
            'footerTone' => 'text-blue-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Stok kritis (≤ 1)',
            'value' => number_format($totalStokKritis, 0, ',', '.'),
            'icon' => 'alert',
            'tone' => 'red',
            'valueTone' => 'text-red-600',
            'labelTone' => 'text-red-600',
            'footer' => 'Buku yang perlu diperhatikan stoknya',
            'footerTone' => 'text-red-600',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Sirkulasi dipinjam',
            'value' => number_format($totalDipinjam, 0, ',', '.'),
            'icon' => 'borrow',
            'tone' => 'blue',
            'footer' => 'Transaksi peminjaman yang masih berjalan',
            'footerTone' => 'text-blue-700',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar buku</h2>

            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-48 flex-1">
                        <span class="sr-only">Cari judul, pengarang, atau ID buku</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                            @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                        </span>

                        <input
                            type="search"
                            data-search="#tabel-buku"
                            placeholder="Cari judul, pengarang, ID buku..."
                            class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        >
                    </label>

                    <div data-tabs="#tabel-buku" class="flex flex-wrap gap-2 text-sm font-semibold">
                        <button
                            type="button"
                            data-tab="all"
                            data-active="true"
                            aria-pressed="true"
                            class="rounded-xl bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white"
                        >
                            Semua kategori
                        </button>

                        @foreach ($kategoriFilter as $nilai => $label)
                            <button
                                type="button"
                                data-tab="{{ $nilai }}"
                                data-active="false"
                                aria-pressed="false"
                                class="rounded-xl bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <p class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    Filter aktif:
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">
                        Koleksi terdaftar
                    </span>
                    <span class="rounded-full bg-blue-50 px-2.5 py-1 font-semibold text-blue-700">
                        Stok buku
                    </span>
                    <span class="ml-auto">
                        <b class="text-blue-700" id="jumlah-dipilih">0</b> buku terpilih
                    </span>
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-buku" class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="w-10 py-3 pl-5">
                                    <input
                                        type="checkbox"
                                        data-check-all="#tabel-buku"
                                        class="size-4 rounded border-slate-300"
                                        aria-label="Pilih semua buku"
                                    >
                                </th>
                                <th scope="col" class="px-3 py-3">ID buku</th>
                                <th scope="col" class="px-3 py-3">Cover &amp; judul buku</th>
                                <th scope="col" class="px-3 py-3">Pengarang</th>
                                <th scope="col" class="px-3 py-3">Penerbit</th>
                                <th scope="col" class="px-3 py-3 text-center">Stok</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($buku as $b)
                                <tr
                                    @class([
                                        'align-middle',
                                        'bg-red-50/60' => $b['stok'] == 0,
                                    ])
                                    data-cat="{{ $b['kategori'] }}"
                                >
                                    <td class="py-4 pl-5">
                                        <input
                                            type="checkbox"
                                            data-row-check
                                            class="size-4 rounded border-slate-300"
                                            aria-label="Pilih {{ $b['judul'] }}"
                                        >
                                    </td>

                                    <td class="px-3 py-4">
                                        <span
                                            @class([
                                                'rounded-md px-2 py-1 text-xs font-bold',
                                                'bg-red-100 text-red-700' => $b['stok'] == 0,
                                                'bg-blue-50 text-blue-700' => $b['stok'] > 0,
                                            ])
                                        >
                                            {{ $b['kode'] }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($b['cover_url'])
                                                <img
                                                    src="{{ $b['cover_url'] }}"
                                                    alt="Cover {{ $b['judul'] }}"
                                                    class="h-14 w-11 shrink-0 rounded-lg border border-slate-200 object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="grid h-14 w-11 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-700">
                                                    @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5'])
                                                </div>
                                            @endif

                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">
                                                    {{ $b['judul'] }}
                                                </p>
                                                <p class="text-xs text-slate-500">
                                                    Rak {{ $b['rak'] }} • ISBN {{ $b['isbn'] }}
                                                </p>
                                                @if ($b['pdf'])
                                                    <span class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-emerald-700">
                                                        @include('petugas.partials.icon', ['name' => 'file-text', 'class' => 'size-3'])
                                                        PDF tersedia
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-3 py-4">
                                        {{ $b['pengarang'] }}
                                    </td>

                                    <td class="px-3 py-4">
                                        {{ $b['penerbit'] }}
                                    </td>

                                    <td class="px-3 py-4 text-center">
                                        <span
                                            @class([
                                                'inline-flex min-w-8 justify-center rounded-md px-2 py-1 text-xs font-bold',
                                                'bg-red-100 text-red-700' => $b['stok'] <= 1,
                                                'bg-emerald-50 text-emerald-700' => $b['stok'] > 1,
                                            ])
                                        >
                                            {{ $b['stok'] }}
                                        </span>
                                    </td>

                                    <td class="px-3 py-4 pr-5">
                                        <div class="flex justify-end gap-1">
                                            <button
                                                type="button"
                                                data-edit
                                                data-update-url="{{ route('petugas.buku.update', $b['id']) }}"
                                                data-judul="{{ $b['judul'] }}"
                                                data-pengarang="{{ $b['pengarang'] }}"
                                                data-penerbit="{{ $b['penerbit'] === '-' ? '' : $b['penerbit'] }}"
                                                data-stok="{{ $b['stok'] }}"
                                                data-kategori="{{ $b['kategori'] }}"
                                                data-isbn="{{ $b['isbn'] === '-' ? '' : $b['isbn'] }}"
                                                data-rak="{{ $b['rak'] === '-' ? '' : $b['rak'] }}"
                                                class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-blue-50 hover:text-blue-700"
                                                aria-label="Ubah {{ $b['judul'] }}"
                                            >
                                                @include('petugas.partials.icon', ['name' => 'pencil', 'class' => 'size-4'])
                                            </button>

                                            <form
                                                method="POST"
                                                action="{{ route('petugas.buku.destroy', $b['id']) }}"
                                                onsubmit="return confirm('Yakin ingin menghapus buku ini?')"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600"
                                                    aria-label="Hapus {{ $b['judul'] }}"
                                                >
                                                    @include('petugas.partials.icon', ['name' => 'trash', 'class' => 'size-4'])
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-500">
                                            @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-8'])
                                            <p class="font-semibold">Belum ada data buku</p>
                                            <p class="text-xs">
                                                Tambahkan buku melalui formulir di sebelah kanan.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                    Menampilkan {{ count($buku) }} judul buku.
                </div>
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Formulir buku">
            <section
                id="form-buku"
                class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100"
                aria-labelledby="judul-form"
            >
                <div class="flex items-start justify-between gap-3">
                    <h2 id="judul-form" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5 text-blue-700'])
                        Form Input / Edit Buku
                    </h2>

                    <span
                        id="mode-form"
                        class="shrink-0 rounded-lg bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700"
                    >
                        Mode tambah
                    </span>
                </div>

                <form
                    method="POST"
                    action="{{ route('petugas.buku.store') }}"
                    enctype="multipart/form-data"
                    id="form-buku-el"
                    class="mt-4 space-y-4"
                >
                    @csrf

                    <input type="hidden" name="_method" id="form-method" value="POST">

                    <div>
                        <label for="judul_buku" class="text-sm font-semibold">
                            Judul buku <span class="text-red-600">*</span>
                        </label>
                        <input
                            id="judul_buku"
                            name="judul_buku"
                            value="{{ old('judul_buku') }}"
                            required
                            placeholder="Masukkan judul buku lengkap..."
                            class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="pengarang" class="text-sm font-semibold">
                                Pengarang <span class="text-red-600">*</span>
                            </label>
                            <input
                                id="pengarang"
                                name="pengarang"
                                value="{{ old('pengarang') }}"
                                required
                                placeholder="Nama penulis"
                                class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            >
                        </div>

                        <div>
                            <label for="penerbit" class="text-sm font-semibold">Penerbit</label>
                            <input
                                id="penerbit"
                                name="penerbit"
                                value="{{ old('penerbit') }}"
                                placeholder="Nama penerbit"
                                class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="stock" class="text-sm font-semibold">
                                Jumlah stok <span class="text-red-600">*</span>
                            </label>

                            <div class="mt-1 flex items-center gap-1 rounded-xl bg-[#EEF1F8] p-1">
                                <button
                                    type="button"
                                    data-step="-1"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-blue-700 shadow-sm"
                                    aria-label="Kurangi stok"
                                >
                                    @include('petugas.partials.icon', ['name' => 'minus', 'class' => 'size-4'])
                                </button>

                                <input
                                    id="stock"
                                    name="stock"
                                    type="number"
                                    min="0"
                                    value="{{ old('stock', 0) }}"
                                    required
                                    class="w-full border-0 bg-transparent p-0 text-center text-sm font-bold focus:ring-0 focus:outline-none"
                                >

                                <button
                                    type="button"
                                    data-step="1"
                                    class="grid size-9 place-items-center rounded-lg bg-white text-blue-700 shadow-sm"
                                    aria-label="Tambah stok"
                                >
                                    @include('petugas.partials.icon', ['name' => 'plus', 'class' => 'size-4'])
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="kategori" class="text-sm font-semibold">Kategori</label>
                            <input
                                id="kategori"
                                name="kategori"
                                list="daftar-kategori"
                                value="{{ old('kategori') }}"
                                placeholder="Contoh: Komputer"
                                class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-3 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            >

                            <datalist id="daftar-kategori">
                                @foreach ($kategoriBuku as $kategori)
                                    <option value="{{ $kategori }}">
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-semibold">Nomor ISBN &amp; lokasi rak</p>

                        <div class="mt-1 grid grid-cols-2 gap-3">
                            <input
                                id="isbn"
                                name="isbn"
                                value="{{ old('isbn') }}"
                                aria-label="Nomor ISBN"
                                placeholder="ISBN (opsional)"
                                class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            >

                            <input
                                id="rak"
                                name="rak"
                                value="{{ old('rak') }}"
                                aria-label="Kode rak"
                                placeholder="Kode rak"
                                class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="cover" class="text-sm font-semibold">Cover Buku</label>
                        <input
                            type="file"
                            id="cover"
                            name="cover"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm"
                        >
                        <p class="mt-1 text-xs text-slate-500">
                            JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB.
                            Kosongkan saat edit jika tidak ingin mengganti cover.
                        </p>
                    </div>

                    <div>
                        <label for="pdf" class="text-sm font-semibold">File PDF Buku</label>
                        <input
                            type="file"
                            id="pdf"
                            name="pdf"
                            accept=".pdf,application/pdf"
                            class="mt-1 w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm"
                        >
                        <p class="mt-1 text-xs text-slate-500">
                            PDF maksimal 20 MB. Kosongkan saat edit jika tidak ingin mengganti PDF.
                        </p>
                    </div>

                    <p class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900">
                        <b>Informasi:</b> Data buku akan disimpan ke database dan dapat digunakan
                        pada katalog serta proses peminjaman SILARAS.
                    </p>

                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800"
                        >
                            @include('petugas.partials.icon', ['name' => 'save', 'class' => 'size-4'])
                            Simpan Data Buku
                        </button>

                        <button
                            type="reset"
                            id="batal-form"
                            class="rounded-xl bg-[#EEF3FC] px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-blue-100"
                        >
                            Batal
                        </button>
                    </div>
                </form>
            </section>

            <section
                class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100"
                aria-labelledby="judul-info"
            >
                <div class="flex items-center gap-2">
                    @include('petugas.partials.icon', ['name' => 'info', 'class' => 'size-5 text-blue-700'])
                    <h2 id="judul-info" class="text-base font-extrabold">
                        Informasi Penyimpanan
                    </h2>
                </div>

                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    <div class="rounded-xl bg-[#EEF3FC] p-3">
                        <p class="font-semibold text-slate-800">Cover buku</p>
                        <p class="mt-1 break-all text-xs">
                            storage/app/public/buku/cover/
                        </p>
                    </div>

                    <div class="rounded-xl bg-[#EEF3FC] p-3">
                        <p class="font-semibold text-slate-800">PDF buku</p>
                        <p class="mt-1 break-all text-xs">
                            storage/app/public/buku/pdf/
                        </p>
                    </div>

                    <p class="text-xs">
                        File disimpan melalui public storage Laravel. Database menyimpan
                        lokasi relatif file, bukan isi file secara langsung.
                    </p>
                </div>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-buku-el');

    if (!form) {
        return;
    }

    const mode = document.getElementById('mode-form');
    const stok = document.getElementById('stock');
    const formMethod = document.getElementById('form-method');
    const urlTambah = "{{ route('petugas.buku.store') }}";

    // Tombol tambah dan kurangi stok.
    form.querySelectorAll('[data-step]').forEach((button) => {
        button.addEventListener('click', () => {
            const perubahan = Number.parseInt(button.dataset.step, 10) || 0;
            const nilaiSekarang = Number.parseInt(stok.value, 10) || 0;

            stok.value = Math.max(0, nilaiSekarang + perubahan);
        });
    });

    // Tombol edit buku.
    document.querySelectorAll('[data-edit]').forEach((button) => {
        button.addEventListener('click', () => {
            const data = button.dataset;

            form.action = data.updateUrl;
            formMethod.value = 'PUT';

            form.elements.namedItem('judul_buku').value = data.judul;
            form.elements.namedItem('pengarang').value = data.pengarang;
            form.elements.namedItem('penerbit').value = data.penerbit;
            form.elements.namedItem('stock').value = data.stok;
            form.elements.namedItem('kategori').value = data.kategori;
            form.elements.namedItem('isbn').value = data.isbn;
            form.elements.namedItem('rak').value = data.rak;

            // Browser tidak mengizinkan pengisian otomatis input file.
            form.elements.namedItem('cover').value = '';
            form.elements.namedItem('pdf').value = '';

            mode.textContent = 'Mode ubah';

            document.getElementById('form-buku').scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                    ? 'auto'
                    : 'smooth',
                block: 'start',
            });

            form.elements.namedItem('judul_buku').focus();
        });
    });

    // Reset form kembali ke mode tambah.
    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            mode.textContent = 'Mode tambah';
            form.action = urlTambah;
            formMethod.value = 'POST';
        }, 0);
    });

    // Hitung jumlah buku yang dipilih.
    const tabel = document.getElementById('tabel-buku');
    const jumlahDipilih = document.getElementById('jumlah-dipilih');

    const hitungPilihan = () => {
        jumlahDipilih.textContent = tabel.querySelectorAll('[data-row-check]:checked').length;
    };

    tabel.addEventListener('change', hitungPilihan);
})();
</script>
@endpush
