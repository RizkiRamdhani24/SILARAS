@extends('petugas.layout')

@section('title', 'Kelola Ebook')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Ebook</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola koleksi ebook dan tautan Google Drive.</p>
        </div>
        <a href="{{ route('petugas.ebook.create') }}" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Tambah Ebook</a>
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
                <h2 class="text-base font-extrabold">Daftar ebook</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $ebooks->count() }} ebook.</p>
            </div>
            <a href="{{ route('petugas.ebook.unduhan') }}" class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Rekap Unduhan</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Judul ebook</th>
                        <th scope="col" class="px-5 py-3">Penulis</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ebooks as $ebook)
                        <tr>
                            <td class="px-5 py-4 font-semibold">{{ $ebook->judul }}</td>
                            <td class="px-5 py-4">{{ $ebook->penulis }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <a class="font-semibold text-blue-700 hover:underline" href="{{ route('petugas.ebook.edit', $ebook->getKey()) }}">Edit</a>
                                    <form action="{{ route('petugas.ebook.destroy', $ebook->getKey()) }}" method="POST" data-confirm="Hapus ebook ini secara permanen? Riwayat unduhan anggota akan tetap tersimpan." data-confirm-title="Hapus ebook" data-confirm-variant="danger" data-confirm-label="Ya, hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-700 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada ebook.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
