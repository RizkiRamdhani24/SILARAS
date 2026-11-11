@extends('petugas.layout')

@section('title', 'Pengelolaan Buku')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Pengelolaan Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola koleksi buku yang tersimpan di perpustakaan SILARAS.</p>
        </div>
        <a href="{{ route('petugas.kelola-buku.create') }}" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Tambah Buku</a>
    </div>

    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 p-5">
            <div>
                <h2 class="text-base font-extrabold">Daftar koleksi buku</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $buku->total() }} hasil dari {{ $totalBuku }} buku.</p>
            </div>
            <form method="GET" action="{{ route('petugas.buku') }}" class="flex flex-wrap gap-2">
                <label class="sr-only" for="cari-buku">Cari buku</label>
                <input id="cari-buku" type="search" name="q" value="{{ $search }}" placeholder="Cari judul, pengarang, ISBN..."
                       class="w-60 rounded-xl border-0 bg-[#EEF1F8] px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                <label class="sr-only" for="kategori-buku">Kategori buku</label>
                <select id="kategori-buku" name="kategori" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="ketersediaan-buku">Ketersediaan</label>
                <select id="ketersediaan-buku" name="ketersediaan" class="rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
                    <option value="">Semua stok</option>
                    <option value="tersedia" @selected($selectedAvailability === 'tersedia')>Tersedia</option>
                    <option value="habis" @selected($selectedAvailability === 'habis')>Stok habis</option>
                </select>
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[62rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
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
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $item->stock > 0,
                                    'bg-red-50 text-red-700' => $item->stock <= 0,
                                ])>{{ $item->stock > 0 ? 'Tersedia' : 'Stok habis' }} · {{ $item->stock }}</span>
                            </td>
                            <td class="px-5 py-4">{{ $item->rak ?: '-' }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <a class="font-semibold text-blue-700 hover:underline" href="{{ route('petugas.kelola-buku.edit', $item->id_buku) }}">Edit</a>
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
                        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada data buku yang sesuai.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($buku->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $buku->links() }}</div>
        @endif
    </section>
@endsection
