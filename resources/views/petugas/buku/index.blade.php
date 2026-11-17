@extends('petugas.layout')

@section('title', 'Pengelolaan Buku')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 pt-6">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Pengelolaan Buku</h1>
        <p class="mt-1 text-sm text-slate-600">Kelola koleksi buku yang tersimpan di perpustakaan SILARAS.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('petugas.kelola-buku.template') }}" class="inline-flex items-center rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Template CSV (Excel)</a>
        <a href="{{ route('petugas.kelola-buku.create') }}" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Tambah Buku</a>
    </div>
</div>

@if (session('success'))
<div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
@endif
@if (session('error'))
<div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
@endif

<section class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
    <h2 class="text-base font-extrabold">Impor buku dari CSV</h2>
    <p class="mt-1 text-sm text-slate-500">Gunakan template CSV yang dapat dibuka dan diedit di Excel. Impor hanya menambah buku baru; jika ada baris tidak valid atau duplikat, seluruh file ditolak.</p>
    <form method="POST" action="{{ route('petugas.kelola-buku.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
        @csrf
        <label class="grid gap-1 text-sm font-medium text-slate-700">
            Pilih file CSV
            <input type="file" name="file" accept=".csv,text/csv" required class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </label>
        <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Impor CSV</button>
    </form>
    @foreach ($errors->get('file') as $error)
    <p class="mt-2 text-sm text-red-700">{{ $error }}</p>
    @endforeach
</section>

<section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5">
        <div>
            <h2 class="text-base font-extrabold">Daftar koleksi buku</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $buku->total() }} hasil dari {{ $totalBuku }} buku.</p>
        </div>
        <form id="bulk-book-form" method="POST" action="{{ route('petugas.kelola-buku.bulk-destroy') }}" data-confirm="Hapus permanen buku yang dipilih? Penghapusan dibatalkan bila ada buku yang memiliki riwayat peminjaman." data-confirm-title="Hapus buku terpilih" data-confirm-variant="danger" data-confirm-label="Ya, hapus">
            @csrf
            <input type="hidden" name="q" value="{{ $search }}">
            <input type="hidden" name="kategori" value="{{ $selectedCategory }}">
            <input type="hidden" name="ketersediaan" value="{{ $selectedAvailability }}">
            <div class="flex flex-wrap items-center justify-end gap-3">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="all_filtered" value="1" class="rounded border-slate-300">
                    Pilih semua {{ $buku->total() }} hasil filter
                </label>
                <button type="submit" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Hapus permanen</button>
            </div>
        </form>
        <form method="GET" action="{{ route('petugas.buku') }}" class="flex flex-wrap gap-2">
            <label class="sr-only" for="cari-buku">Cari buku</label>
            <input id="cari-buku" type="search" name="q" value="{{ $search }}" placeholder="Cari judul, pengarang, ISBN..."
                class="w-60 rounded-xl border-0 bg-[#EEF1F8] px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
            <label class="sr-only" for="kategori-buku">Kategori buku</label>
            <select id="kategori-buku" name="kategori" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                <option value="{{ $category }}" @selected($selectedCategory===$category)>{{ $category }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="ketersediaan-buku">Ketersediaan</label>
            <select id="ketersediaan-buku" name="ketersediaan" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Semua stok</option>
                <option value="tersedia" @selected($selectedAvailability==='tersedia' )>Tersedia</option>
                <option value="habis" @selected($selectedAvailability==='habis' )>Stok habis</option>
            </select>
            <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[62rem] text-left text-sm">
            <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th scope="col" class="px-5 py-3"><input id="select-visible-books" type="checkbox" aria-label="Pilih buku yang tampil" class="rounded border-slate-300"></th>
                    <th scope="col" class="px-5 py-3">Judul buku</th>
                    <th scope="col" class="px-5 py-3">Pengarang / penerbit</th>
                    <th scope="col" class="px-5 py-3">Kategori</th>
                    <th scope="col" class="px-5 py-3">ISBN</th>
                    <th scope="col" class="px-5 py-3">Stok</th>
                    <th scope="col" class="px-5 py-3">Rak</th>
                    <th scope="col" class="px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($buku as $item)
                <tr>
                    <td class="px-5 py-4">
                        <input type="checkbox" name="ids[]" value="{{ $item->id_buku }}" form="bulk-book-form" aria-label="Pilih {{ $item->judul_buku }}" class="book-row-checkbox rounded border-slate-300">
                    </td>
                    <td class="px-5 py-4 font-semibold">
                        {{ $item->judul_buku }}
                        @if ($item->diarsipkan)
                        <span class="ml-2 rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">Diarsipkan</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <p>{{ $item->pengarang }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $item->penerbit ?: 'Penerbit tidak tersedia' }}</p>
                    </td>
                    <td class="px-5 py-4">{{ $item->kategori ?: '-' }}</td>
                    <td class="px-5 py-4">{{ $item->isbn ?: '-' }}</td>
                    <td class="px-5 py-4">
                        <span @class([ 'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold' , 'bg-emerald-50 text-emerald-700'=> $item->stock > 0,
                            'bg-red-50 text-red-700' => $item->stock <= 0,
                                ])>{{ $item->stock > 0 ? 'Tersedia' : 'Stok habis' }} · {{ $item->stock }}</span>
                    </td>
                    <td class="px-5 py-4">{{ $item->rak ?: '-' }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <a class="font-semibold text-blue-700 hover:underline" href="{{ route('petugas.kelola-buku.edit', $item->id_buku) }}">Edit</a>
                            <form action="{{ route('petugas.kelola-buku.permanent-destroy', $item->id_buku) }}" method="POST" data-confirm="Hapus buku ini secara permanen? Buku yang memiliki riwayat peminjaman tidak dapat dihapus." data-confirm-title="Hapus buku" data-confirm-variant="danger" data-confirm-label="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-red-700 hover:underline">Hapus</button>
                            </form>
                            @unless ($item->diarsipkan)
                            <form action="{{ route('petugas.kelola-buku.destroy', $item->id_buku) }}" method="POST" data-confirm="Arsipkan buku ini? Riwayat transaksi akan tetap tersimpan." data-confirm-title="Arsipkan buku" data-confirm-variant="danger" data-confirm-label="Ya, arsipkan">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="font-semibold text-slate-700 hover:underline">Arsipkan</button>
                            </form>
                            @endunless
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data buku yang sesuai.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($buku->hasPages())
    <div class="border-t border-slate-100 px-5 py-4">{{ $buku->links() }}</div>
    @endif
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectVisible = document.getElementById('select-visible-books');
        if (!selectVisible) {
            return;
        }
        selectVisible.addEventListener('change', function() {
            document.querySelectorAll('.book-row-checkbox').forEach(function(checkbox) {
                checkbox.checked = selectVisible.checked;
            });
        });
    });
</script>
@endpush