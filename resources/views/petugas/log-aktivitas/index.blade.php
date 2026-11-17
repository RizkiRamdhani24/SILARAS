@extends('petugas.layout')

@section('title', 'Log Aktivitas')

@section('content')
    <div class="pt-6">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Log Aktivitas</h1>
        <p class="mt-1 text-sm text-slate-600">Riwayat tindakan admin pada sistem.</p>
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
        <form method="GET" action="{{ route('petugas.log-aktivitas.index') }}" class="flex flex-wrap items-end gap-3 border-b border-slate-100 p-5">
            <div>
                <label for="dari" class="text-sm font-semibold">Dari tanggal</label>
                <input id="dari" type="date" name="dari" value="{{ $dari }}" class="mt-1 block rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label for="sampai" class="text-sm font-semibold">Sampai tanggal</label>
                <input id="sampai" type="date" name="sampai" value="{{ $sampai }}" class="mt-1 block rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600">
            </div>
            <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
        </form>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[52rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">Waktu</th>
                        <th scope="col" class="px-5 py-3">Petugas</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                        <th scope="col" class="px-5 py-3">Objek</th>
                        <th scope="col" class="px-5 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr>
                            <td class="px-5 py-4">{{ $log->waktu->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-4 font-semibold">{{ $log->nama_petugas }}</td>
                            <td class="px-5 py-4">{{ ucfirst($log->aksi) }}</td>
                            <td class="px-5 py-4">{{ ucfirst($log->objek) }}</td>
                            <td class="px-5 py-4">{{ $log->keterangan ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Tidak ada aktivitas pada rentang tanggal ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
