@extends('petugas.layout')

@section('title', 'Denda')

@php
    use App\Models\Anggota;
    use App\Models\Pengembalian;
    use App\Support\PetugasData as D;

    $tarif = D::tarifDenda();

    // Denda tercatat di tabel pengembalian (kolom denda + status_denda: tidak ada / belum dibayar / lunas).
    $semua = Pengembalian::with(['peminjaman.buku', 'peminjaman.anggota', 'petugas'])
        ->where('denda', '>', 0)
        ->latest('id_pengembalian')
        ->get();

    $belum = $semua->where('status_denda', Pengembalian::DENDA_BELUM_DIBAYAR);
    $lunas = $semua->where('status_denda', Pengembalian::DENDA_LUNAS);
    $tampil = $semua->take(100);

    $tab = [
        ['belum-dibayar', 'Belum dibayar', $belum->count()],
        ['lunas', 'Lunas', $lunas->count()],
        ['all', 'Semua', $semua->count()],
    ];

    $namaAnggota = Anggota::pluck('nama_anggota', 'id_anggota');
    $tunggakan = collect(D::tunggakanPerAnggota())->sortDesc()->take(5);
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Denda Keterlambatan</h1>
            <p class="mt-1 text-sm text-slate-600">Daftar denda dari pengembalian buku yang terlambat, lengkap dengan status pembayarannya.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan denda">
        @include('petugas.partials.stat-card', ['label' => 'Denda belum dibayar', 'value' => D::rupiah($belum->sum('denda')), 'icon' => 'cash', 'tone' => 'red', 'labelTone' => 'text-red-600', 'valueTone' => 'text-red-600', 'footer' => $belum->count().' transaksi'])
        @include('petugas.partials.stat-card', ['label' => 'Denda terkumpul', 'value' => D::rupiah($lunas->sum('denda')), 'icon' => 'check-circle', 'tone' => 'green', 'footer' => $lunas->count().' transaksi lunas'])
        @include('petugas.partials.stat-card', ['label' => 'Anggota bertunggakan', 'value' => count(D::tunggakanPerAnggota()), 'suffix' => 'Anggota', 'icon' => 'users', 'tone' => 'amber', 'footer' => 'Punya denda belum dibayar'])
        @include('petugas.partials.stat-card', ['label' => 'Tarif denda', 'value' => D::rupiah($tarif), 'suffix' => '/ hari', 'icon' => 'clock', 'tone' => 'blue', 'footer' => 'Dari tabel pengaturan'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="min-w-0 space-y-4" aria-label="Daftar denda">
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <label class="relative block">
                    <span class="sr-only">Cari anggota atau judul buku</span>
                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                    <input type="search" data-search="#tabel-denda" placeholder="Cari ID, anggota, atau judul buku..."
                           class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </label>
                <div data-tabs="#tabel-denda" class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    @foreach ($tab as [$kode, $label, $jumlah])
                        <button type="button" data-tab="{{ $kode }}"
                                class="rounded-lg bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }} ({{ $jumlah }})</button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-denda" class="w-full min-w-[46rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Buku</th>
                                <th scope="col" class="px-3 py-3">Terlambat</th>
                                <th scope="col" class="px-3 py-3">Denda</th>
                                <th scope="col" class="px-3 py-3 pr-5">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tampil as $k)
                                @php
                                    $pinjam = $k->peminjaman;
                                    $telat = D::hariTerlambat($pinjam?->tgl_jatuh_tempo, $k->tgl_pengembalian);
                                    $isLunas = $k->status_denda === Pengembalian::DENDA_LUNAS;
                                @endphp
                                <tr class="align-top" data-cat="{{ $isLunas ? 'lunas' : 'belum-dibayar' }}">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-blue-700">{{ D::kode('KMB', $k->id_pengembalian) }}</p>
                                        <p class="text-xs text-slate-500">{{ D::tanggal($k->tgl_pengembalian) }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $pinjam?->anggota?->nama_anggota ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $pinjam?->anggota?->email_anggota }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold leading-snug">{{ $pinjam?->buku?->judul_buku ?? '-' }}</p>
                                        @if ($k->kondisi_buku && $k->kondisi_buku !== Pengembalian::KONDISI_BAIK)
                                            <p class="text-xs text-red-600">Kondisi {{ $k->kondisi_buku }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 text-xs">{{ $telat > 0 ? $telat.' hari' : '-' }}</td>
                                    <td class="px-3 py-4 font-bold {{ $isLunas ? 'text-slate-800' : 'text-red-600' }}">{{ D::rupiah($k->denda) }}</td>
                                    <td class="px-3 py-4 pr-5">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-700' => $isLunas,
                                            'bg-amber-100 text-amber-700' => ! $isLunas,
                                        ])>{{ ucfirst($k->status_denda) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada denda tercatat.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($semua->count() > $tampil->count())
                    <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Menampilkan {{ $tampil->count() }} terbaru dari {{ $semua->count() }} denda.</p>
                @endif
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Tunggakan dan catatan">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <h2 class="text-base font-extrabold">Tunggakan Terbesar</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse ($tunggakan as $idAnggota => $total)
                        <li class="flex items-center justify-between gap-3 rounded-xl bg-[#EEF3FC] p-3">
                            <span class="min-w-0 truncate font-semibold">{{ $namaAnggota[$idAnggota] ?? D::kode('AGT', $idAnggota) }}</span>
                            <span class="shrink-0 font-bold text-red-600">{{ D::rupiah($total) }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-[#EEF3FC] p-4 text-center text-xs text-slate-500">Tidak ada tunggakan denda.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-900 ring-1 ring-amber-100">
                <h2 class="text-sm font-bold">Pembayaran denda</h2>
                <p class="mt-1 text-xs leading-relaxed">Pencatatan pembayaran (mengubah status menjadi lunas) belum tersedia karena modul pembayaran masih dikerjakan. Halaman ini menampilkan data denda apa adanya dari database.</p>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-tab="belum-dibayar"]')?.click();
</script>
@endpush
