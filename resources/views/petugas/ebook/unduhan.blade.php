@extends('petugas.layout')

@section('title', 'Rekap Unduhan Ebook')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Rekap Unduhan Ebook</h1>
            <p class="mt-1 text-sm text-slate-600">Riwayat unduhan ebook per anggota.</p>
        </div>
        <a href="{{ route('petugas.ebook.index') }}" class="rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">Kembali ke Kelola Ebook</a>
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Nama anggota</th>
                        <th scope="col" class="px-5 py-3">Ebook</th>
                        <th scope="col" class="px-5 py-3">Penulis</th>
                        <th scope="col" class="px-5 py-3">Waktu unduh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($unduhan as $memberUnduhan)
                        @foreach ($memberUnduhan as $item)
                            <tr>
                                <td class="px-5 py-4 font-semibold">{{ $item->anggota?->nama_anggota ?? 'Anggota tidak tersedia' }}</td>
                                <td class="px-5 py-4">{{ $item->judul_ebook }}</td>
                                <td class="px-5 py-4">{{ $item->penulis_ebook }}</td>
                                <td class="px-5 py-4">{{ $item->waktu_unduh->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada riwayat unduhan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
