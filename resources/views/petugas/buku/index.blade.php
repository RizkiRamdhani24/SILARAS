@extends('petugas.layout')

@section('title', 'Kelola Buku')

@php
    use App\Models\Buku;
    use App\Support\PetugasData as D;

    // Daftar buku: memakai $buku dari controller Exel (BukuController@halaman, sudah berpaginasi + pencarian "q").
    // Bila halaman dibuka lewat rute di web.php yang belum memakai controller itu, data diambil langsung dari tabel buku.
    $kata = mb_strtolower(trim((string) request('q', '')));
    $daftar = isset($buku)
        ? $buku
        : Buku::query()
            ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereRaw('LOWER(judul_buku) LIKE ?', ["%{$kata}%"])
                ->orWhereRaw('LOWER(pengarang) LIKE ?', ["%{$kata}%"])
                ->orWhereRaw('LOWER(penerbit) LIKE ?', ["%{$kata}%"])))
            ->orderBy('judul_buku')->paginate(10)->withQueryString();
    $berhalaman = $daftar instanceof \Illuminate\Contracts\Pagination\Paginator;

    $totalJudul = Buku::count();
    $totalEksemplar = (int) Buku::sum('stock');
    $stokKritis = Buku::where('stock', '<=', 1)->count();
    $dipinjam = D::sedangDipinjam();
    $kategori = D::kategoriBuku();

    $bisaTambah = D::bisa('petugas.buku.store');
    $bisaUbah = D::bisa('petugas.kelola-buku.edit');
    $bisaHapus = D::bisa('petugas.kelola-buku.destroy');
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Data Buku &amp; Stok Pustaka</h1>
            <p class="mt-1 text-sm text-slate-600">Data induk buku perpustakaan: tambah judul baru, perbarui pengarang, penerbit, dan stok, atau hapus buku yang belum pernah dipinjam.</p>
        </div>
        @if (D::bisa('petugas.kelola-buku.create'))
            <a href="{{ route('petugas.kelola-buku.create') }}" class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4']) Tambah Buku Baru
            </a>
        @endif
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan koleksi">
        @include('petugas.partials.stat-card', ['label' => 'Total judul buku', 'value' => number_format($totalJudul, 0, ',', '.'), 'icon' => 'book', 'tone' => 'blue', 'footer' => 'Judul terdaftar di katalog'])
        @include('petugas.partials.stat-card', ['label' => 'Total eksemplar', 'value' => number_format($totalEksemplar, 0, ',', '.'), 'icon' => 'dashboard', 'tone' => 'blue', 'footer' => 'Jumlah stok tersedia di rak'])
        @include('petugas.partials.stat-card', ['label' => 'Stok kritis (≤ 1)', 'value' => $stokKritis, 'icon' => 'alert', 'tone' => 'red', 'labelTone' => 'text-red-600', 'footer' => 'Perlu pengadaan atau restock', 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Sedang dipinjam', 'value' => $dipinjam, 'icon' => 'borrow', 'tone' => 'blue', 'footer' => 'Peminjaman aktif dan terlambat'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel-buku">
            <form method="GET" action="{{ url()->current() }}" class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-52 flex-1">
                        <span class="sr-only">Cari judul, pengarang, atau penerbit</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari judul, pengarang, atau penerbit..."
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Cari</button>
                    @if (request()->filled('q'))
                        <a href="{{ url()->current() }}" class="text-sm font-semibold text-blue-700 hover:underline">Reset</a>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <h2 id="judul-tabel-buku" class="sr-only">Daftar buku</h2>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[46rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID buku</th>
                                <th scope="col" class="px-3 py-3">Judul buku</th>
                                <th scope="col" class="px-3 py-3">Pengarang</th>
                                <th scope="col" class="px-3 py-3">Penerbit</th>
                                <th scope="col" class="px-3 py-3">Stok</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftar as $b)
                                <tr @class(['align-top', 'bg-red-50/60' => $b->stock < 1, 'bg-amber-50/60' => $b->stock === 1])>
                                    <td class="px-5 py-4"><span @class(['rounded-md px-2 py-1 text-xs font-bold', 'bg-red-100 text-red-700' => $b->stock < 1, 'bg-blue-50 text-blue-700' => $b->stock >= 1])>{{ D::kode('BK', $b->id_buku) }}</span></td>
                                    <td class="px-3 py-4">
                                        <div class="flex gap-3">
                                            @include('petugas.partials.cover', ['i' => $b->id_buku])
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">{{ $b->judul_buku }}</p>
                                                <p class="mt-1 text-xs text-slate-500">
                                                    @if ($b->kategori) {{ $b->kategori }} @else Tanpa kategori @endif
                                                    @if ($b->rak) • Rak {{ $b->rak }} @endif
                                                    @if ($b->isbn) • ISBN {{ $b->isbn }} @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4">{{ $b->pengarang }}</td>
                                    <td class="px-3 py-4">{{ $b->penerbit ?: '-' }}</td>
                                    <td class="px-3 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-red-100 text-red-700' => $b->stock < 1,
                                            'bg-amber-100 text-amber-800' => $b->stock === 1,
                                            'bg-emerald-50 text-emerald-700' => $b->stock > 1,
                                        ])>{{ $b->stock < 1 ? 'Habis' : $b->stock.' eks' }}</span>
                                    </td>
                                    <td class="px-3 py-4 pr-5">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($bisaUbah)
                                                <a href="{{ route('petugas.kelola-buku.edit', $b->id_buku) }}" class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-blue-50 hover:text-blue-700" aria-label="Ubah {{ $b->judul_buku }}">
                                                    @include('petugas.partials.icon', ['name' => 'pencil', 'class' => 'size-4'])
                                                </a>
                                            @endif
                                            @if ($bisaHapus)
                                                <form method="POST" action="{{ route('petugas.kelola-buku.destroy', $b->id_buku) }}"
                                                      onsubmit="return confirm('Hapus buku &quot;{{ e($b->judul_buku) }}&quot;?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600" aria-label="Hapus {{ $b->judul_buku }}">
                                                        @include('petugas.partials.icon', ['name' => 'trash', 'class' => 'size-4'])
                                                    </button>
                                                </form>
                                            @endif
                                            @unless ($bisaUbah || $bisaHapus)
                                                <span class="text-xs text-slate-400">Menunggu backend buku</span>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                                    @if (request()->filled('q')) Tidak ada buku yang cocok dengan "{{ request('q') }}". @else Belum ada buku. Tambahkan buku pertama lewat formulir di samping. @endif
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($berhalaman && $daftar->hasPages())
                    <div class="border-t border-slate-100 px-5 py-4">{{ $daftar->links() }}</div>
                @endif
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Tambah buku dan kategori">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-form-buku">
                <h2 id="judul-form-buku" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-5 text-blue-700']) Tambah Buku Cepat
                </h2>

                <form method="POST" action="{{ $bisaTambah ? route('petugas.buku.store') : '#' }}" class="mt-4 space-y-4">
                    @csrf
                    <fieldset @disabled(! $bisaTambah) class="space-y-4">
                        <div>
                            <label for="judul_buku" class="text-xs font-semibold">Judul buku <span class="text-red-600">*</span></label>
                            <input id="judul_buku" name="judul_buku" value="{{ old('judul_buku') }}" required maxlength="150" placeholder="Masukkan judul buku lengkap..."
                                   class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pengarang" class="text-xs font-semibold">Pengarang <span class="text-red-600">*</span></label>
                                <input id="pengarang" name="pengarang" value="{{ old('pengarang') }}" required maxlength="100" placeholder="Nama penulis"
                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                            </div>
                            <div>
                                <label for="penerbit" class="text-xs font-semibold">Penerbit</label>
                                <input id="penerbit" name="penerbit" value="{{ old('penerbit') }}" maxlength="100" placeholder="Nama penerbit"
                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="stock" class="text-xs font-semibold">Jumlah stok <span class="text-red-600">*</span></label>
                                <div class="mt-1 flex items-center gap-1 rounded-xl border border-slate-200 p-1 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/30">
                                    <button type="button" data-stok="-1" class="grid size-8 place-items-center rounded-lg bg-[#EEF1F8] text-slate-600 hover:bg-blue-50" aria-label="Kurangi stok">@include('petugas.partials.icon', ['name' => 'minus', 'class' => 'size-4'])</button>
                                    <input id="stock" name="stock" type="number" min="0" required value="{{ old('stock', 1) }}" class="min-w-0 flex-1 border-0 p-0 text-center text-sm focus:ring-0 focus:outline-none">
                                    <button type="button" data-stok="1" class="grid size-8 place-items-center rounded-lg bg-[#EEF1F8] text-slate-600 hover:bg-blue-50" aria-label="Tambah stok">@include('petugas.partials.icon', ['name' => 'plus', 'class' => 'size-4'])</button>
                                </div>
                            </div>
                            <div>
                                <label for="kategori" class="text-xs font-semibold">Kategori</label>
                                <input id="kategori" name="kategori" value="{{ old('kategori') }}" maxlength="50" list="daftar-kategori" placeholder="Mis. Teknologi"
                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                                <datalist id="daftar-kategori">
                                    @foreach ($kategori->keys()->reject(fn ($k) => $k === 'Tanpa kategori') as $nama)
                                        <option value="{{ $nama }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="isbn" class="text-xs font-semibold">ISBN</label>
                                <input id="isbn" name="isbn" value="{{ old('isbn') }}" placeholder="Opsional"
                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                            </div>
                            <div>
                                <label for="rak" class="text-xs font-semibold">Lokasi rak</label>
                                <input id="rak" name="rak" value="{{ old('rak') }}" placeholder="Mis. CS-301"
                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                            </div>
                        </div>
                        <p class="rounded-xl bg-blue-50 p-3 text-xs text-blue-900">Buku yang disimpan langsung muncul di katalog anggota.</p>
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                            @include('petugas.partials.icon', ['name' => 'save', 'class' => 'size-4']) Simpan Data Buku
                        </button>
                    </fieldset>
                    @unless ($bisaTambah)
                        <p class="text-xs text-slate-500">Formulir aktif setelah backend buku (Exel) digabung ke develop.</p>
                    @endunless
                </form>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kategori">
                <h2 id="judul-kategori" class="text-base font-extrabold">Judul per Kategori</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($kategori as $nama => $jumlah)
                        <li class="flex items-center justify-between rounded-xl bg-[#EEF3FC] px-3 py-2">
                            <span>{{ $nama }}</span><b class="text-blue-700">{{ $jumlah }}</b>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">Belum ada buku.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
    // Tombol + / - pada isian stok
    document.querySelectorAll('[data-stok]').forEach((btn) => btn.addEventListener('click', () => {
        const input = document.getElementById('stock');
        input.value = Math.max(0, (parseInt(input.value, 10) || 0) + parseInt(btn.dataset.stok, 10));
    }));
</script>
@endpush
