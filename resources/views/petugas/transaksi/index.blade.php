@extends('petugas.layout')

@section('title', 'Transaksi')

@php
    use App\Models\Peminjaman;
    use App\Models\Pengembalian;
    use App\Support\PetugasData as D;

    // Riwayat gabungan: setiap peminjaman = satu transaksi "peminjaman",
    // setiap pengembalian = satu transaksi "pengembalian". Semuanya dibaca dari database.
    $pinjam = Peminjaman::with(['anggota', 'buku', 'petugas'])->latest('id_peminjaman')->limit(100)->get()
        ->map(fn ($p) => [
            'jenis' => 'peminjaman',
            'kode' => D::kode('PMJ', $p->id_peminjaman),
            'waktu' => $p->updated_at ?? $p->created_at,
            'anggota' => $p->anggota?->nama_anggota ?? '-',
            'buku' => $p->buku?->judul_buku ?? '-',
            'petugas' => $p->petugas?->nama_petugas,
            'status' => D::statusPeminjaman($p->status_peminjaman),
            'denda' => 0,
        ]);

    $kembali = Pengembalian::with(['peminjaman.anggota', 'peminjaman.buku', 'petugas'])->latest('id_pengembalian')->limit(100)->get()
        ->map(fn ($k) => [
            'jenis' => 'pengembalian',
            'kode' => D::kode('KMB', $k->id_pengembalian),
            'waktu' => $k->updated_at ?? $k->created_at,
            'anggota' => $k->peminjaman?->anggota?->nama_anggota ?? '-',
            'buku' => $k->peminjaman?->buku?->judul_buku ?? '-',
            'petugas' => $k->petugas?->nama_petugas,
            'status' => [ucfirst($k->status_pengembalian), $k->status_pengembalian === Pengembalian::STATUS_SELESAI ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'],
            'denda' => (float) $k->denda,
        ]);

    $baris = $pinjam->concat($kembali)->sortByDesc('waktu')->take(100)->values();

    $hariIni = fn ($c) => $c->filter(fn ($r) => $r['waktu']?->isToday())->count();
    $tab = [
        ['all', 'Semua', $baris->count()],
        ['peminjaman', 'Peminjaman', $baris->where('jenis', 'peminjaman')->count()],
        ['pengembalian', 'Pengembalian', $baris->where('jenis', 'pengembalian')->count()],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Riwayat Transaksi</h1>
            <p class="mt-1 text-sm text-slate-600">Semua aktivitas peminjaman dan pengembalian buku, diurutkan dari yang terbaru.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan transaksi">
        @include('petugas.partials.stat-card', ['label' => 'Peminjaman hari ini', 'value' => $hariIni($pinjam), 'icon' => 'borrow', 'tone' => 'blue', 'footer' => 'Pengajuan &amp; pinjaman baru'])
        @include('petugas.partials.stat-card', ['label' => 'Pengembalian hari ini', 'value' => $hariIni($kembali), 'icon' => 'return', 'tone' => 'green', 'footer' => 'Pengajuan &amp; validasi'])
        @include('petugas.partials.stat-card', ['label' => 'Sedang dipinjam', 'value' => D::sedangDipinjam(), 'suffix' => 'Buku', 'icon' => 'book', 'tone' => 'amber', 'footer' => 'Status dipinjam / terlambat'])
        @include('petugas.partials.stat-card', ['label' => 'Total transaksi', 'value' => Peminjaman::count() + Pengembalian::count(), 'icon' => 'history', 'tone' => 'blue', 'footer' => Peminjaman::count().' pinjam • '.Pengembalian::count().' kembali'])
    </section>

    <section class="mt-6 space-y-4" aria-label="Daftar transaksi">
        <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <label class="relative block">
                <span class="sr-only">Cari transaksi</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                <input type="search" data-search="#tabel-transaksi" placeholder="Cari ID, anggota, atau judul buku..."
                       class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </label>
            <div data-tabs="#tabel-transaksi" class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                @foreach ($tab as [$kode, $label, $jumlah])
                    <button type="button" data-tab="{{ $kode }}"
                            class="rounded-lg bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }} ({{ $jumlah }})</button>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="overflow-x-auto">
                <table id="tabel-transaksi" class="w-full min-w-[48rem] text-left text-sm">
                    <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">ID &amp; waktu</th>
                            <th scope="col" class="px-3 py-3">Jenis</th>
                            <th scope="col" class="px-3 py-3">Anggota</th>
                            <th scope="col" class="px-3 py-3">Buku</th>
                            <th scope="col" class="px-3 py-3">Denda</th>
                            <th scope="col" class="px-3 py-3">Status</th>
                            <th scope="col" class="px-3 py-3 pr-5">Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($baris as $r)
                            <tr class="align-top" data-cat="{{ $r['jenis'] }}">
                                <td class="px-5 py-4">
                                    <p class="font-bold text-blue-700">{{ $r['kode'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $r['waktu']?->translatedFormat('d M Y, H:i') }}</p>
                                </td>
                                <td class="px-3 py-4 text-xs font-semibold">{{ $r['jenis'] === 'peminjaman' ? 'Peminjaman' : 'Pengembalian' }}</td>
                                <td class="px-3 py-4 font-semibold">{{ $r['anggota'] }}</td>
                                <td class="px-3 py-4 leading-snug">{{ $r['buku'] }}</td>
                                <td class="px-3 py-4 {{ $r['denda'] > 0 ? 'font-bold text-red-600' : 'text-slate-400' }}">{{ $r['denda'] > 0 ? D::rupiah($r['denda']) : '-' }}</td>
                                <td class="px-3 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $r['status'][1] }}">{{ $r['status'][0] }}</span></td>
                                <td class="px-3 py-4 pr-5 text-xs text-slate-600">{{ $r['petugas'] ?? 'Belum diproses' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Menampilkan maksimal 100 transaksi terbaru.</p>
        </div>
    </section>
@endsection
