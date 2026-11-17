@extends('petugas.layout')

@section('title', 'Kelola Ebook')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Ebook</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola koleksi ebook dan tautan Google Drive.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('petugas.ebook.template') }}" class="inline-flex items-center rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Unduh Template CSV (Excel)</a>
            <a href="{{ route('petugas.ebook.create') }}" class="inline-flex items-center rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">Tambah Ebook</a>
        </div>
    </div>

    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">{{ session('error') }}</div>
    @endif

    <section class="mt-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
        <h2 class="text-base font-extrabold">Impor ebook dari CSV</h2>
        <p class="mt-1 text-sm text-slate-500">Gunakan template CSV yang dapat dibuka dan diedit di Excel. Impor hanya menambah ebook baru; cover boleh ditambahkan nanti melalui edit.</p>
        <form method="POST" action="{{ route('petugas.ebook.import') }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
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
                <h2 class="text-base font-extrabold">Daftar ebook</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $ebooks->count() }} ebook.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <form id="bulk-ebook-form" method="POST" action="{{ route('petugas.ebook.bulk-destroy') }}" data-confirm="Hapus semua ebook yang dipilih? Riwayat unduhan anggota tetap tersimpan." data-confirm-title="Hapus ebook terpilih" data-confirm-variant="danger" data-confirm-label="Ya, hapus">
                    @csrf
                    <label class="mr-3 inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="all_filtered" value="1" class="rounded border-slate-300">
                        Pilih semua ebook
                    </label>
                    <button type="submit" class="rounded-xl bg-red-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-800">Hapus terpilih</button>
                </form>
                <a href="{{ route('petugas.ebook.unduhan') }}" class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Rekap Unduhan</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3"><input id="select-visible-ebooks" type="checkbox" aria-label="Pilih ebook yang tampil" class="rounded border-slate-300"></th>
                        <th scope="col" class="px-5 py-3">Judul ebook</th>
                        <th scope="col" class="px-5 py-3">Penulis</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($ebooks as $ebook)
                        <tr>
                            <td class="px-5 py-4">
                                <input type="checkbox" name="ids[]" value="{{ $ebook->getKey() }}" form="bulk-ebook-form" aria-label="Pilih {{ $ebook->judul }}" class="ebook-row-checkbox rounded border-slate-300">
                            </td>
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
                        <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada ebook.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectVisible = document.getElementById('select-visible-ebooks');
            if (!selectVisible) {
                return;
            }
            selectVisible.addEventListener('change', function () {
                document.querySelectorAll('.ebook-row-checkbox').forEach(function (checkbox) {
                    checkbox.checked = selectVisible.checked;
                });
            });
        });
    </script>
@endpush
