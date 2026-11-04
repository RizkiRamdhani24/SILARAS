@extends('petugas.layout')

@section('title', 'Validasi Pengembalian')

@php
    use App\Models\Pengembalian;
    use App\Support\PetugasData as D;
    use Illuminate\Support\Str;

    [$namaPetugas, $shiftPetugas] = D::identitasPetugas();
    $tarif = D::tarifDenda();

    // $pengembalian dari controller Exel (PengembalianController@indexPetugas, dengan peminjaman.buku, peminjaman.anggota, petugas).
    // Bila halaman dibuka lewat rute di web.php yang belum memakai controller itu, data diambil langsung dari tabel pengembalian.
    $semua = isset($pengembalian)
        ? collect($pengembalian)
        : Pengembalian::with(['peminjaman.buku', 'peminjaman.anggota', 'petugas'])->latest('id_pengembalian')->get();

    $menunggu = $semua->where('status_pengembalian', Pengembalian::STATUS_MENUNGGU_VALIDASI);
    $selesai = $semua->where('status_pengembalian', Pengembalian::STATUS_SELESAI);
    $selesaiHariIni = $selesai->filter(fn ($k) => $k->updated_at?->isToday());
    $kondisi = fn (string $nilai) => $selesaiHariIni->where('kondisi_buku', $nilai)->count();
    $belumBayar = $semua->where('status_denda', Pengembalian::DENDA_BELUM_DIBAYAR);
    $tepatWaktu = $selesai->count() ? round($selesai->filter(fn ($k) => (float) $k->denda <= 0)->count() / $selesai->count() * 100, 1) : null;
    $tampil = $semua->take(100);

    $tab = [
        ['menunggu-validasi', 'Menunggu validasi', $menunggu->count()],
        ['selesai', 'Selesai', $selesai->count()],
        ['all', 'Semua', $semua->count()],
    ];

    $idSaya = auth('petugas')->id();
    $logSaya = $idSaya ? $selesai->where('id_petugas', $idSaya)->sortByDesc('updated_at')->take(5) : collect();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Validasi Pengembalian Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Periksa kondisi fisik buku yang diajukan kembali oleh anggota. Denda dihitung otomatis dari hari keterlambatan, dan stok bertambah bila buku dalam kondisi baik.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pengembalian">
        @include('petugas.partials.stat-card', ['label' => 'Antrean pengembalian', 'value' => $menunggu->count(), 'suffix' => 'Pengajuan', 'icon' => 'return', 'tone' => 'blue', 'footer' => 'Menunggu pemeriksaan fisik'])
        @include('petugas.partials.stat-card', ['label' => 'Selesai hari ini', 'value' => $selesaiHariIni->count(), 'suffix' => 'Buku', 'icon' => 'book', 'tone' => 'blue', 'footer' => $kondisi('baik').' baik • '.$kondisi('rusak').' rusak • '.$kondisi('hilang').' hilang'])
        @include('petugas.partials.stat-card', ['label' => 'Denda belum dibayar', 'value' => D::rupiah($belumBayar->sum('denda')), 'icon' => 'cash', 'tone' => 'amber', 'footer' => 'Tarif '.D::rupiah($tarif).'/hari • '.$belumBayar->count().' transaksi'])
        @include('petugas.partials.stat-card', ['label' => 'Tingkat tepat waktu', 'value' => $tepatWaktu === null ? '-' : $tepatWaktu.'%', 'suffix' => $tepatWaktu === null ? '' : 'Bebas denda', 'icon' => 'shield', 'tone' => 'green', 'footer' => 'Dari '.$selesai->count().' pengembalian selesai'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <label class="relative block">
                    <span class="sr-only">Cari ID, anggota, atau judul buku</span>
                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                    <input type="search" data-search="#tabel-kembali" placeholder="Cari ID transaksi, anggota, atau judul buku..."
                           class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </label>
                <div data-tabs="#tabel-kembali" class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    @foreach ($tab as [$kode, $label, $jumlah])
                        <button type="button" data-tab="{{ $kode }}"
                                class="rounded-lg bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }} ({{ $jumlah }})</button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <h2 id="judul-antrean" class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5 text-blue-700'])
                        Antrean &amp; Verifikasi Pengembalian Buku
                        <span class="rounded-md bg-blue-50 px-2 py-0.5 text-xs text-blue-700">{{ $menunggu->count() }} menunggu</span>
                    </h2>
                    @if ($semua->count() > $tampil->count())
                        <span class="text-xs text-slate-500">Menampilkan {{ $tampil->count() }} terbaru dari {{ $semua->count() }}</span>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table id="tabel-kembali" class="w-full min-w-[52rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID &amp; waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota peminjam</th>
                                <th scope="col" class="px-3 py-3">Buku</th>
                                <th scope="col" class="px-3 py-3">Jatuh tempo</th>
                                <th scope="col" class="px-3 py-3">Denda</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tampil as $k)
                                @php
                                    $pinjam = $k->peminjaman;
                                    $antri = $k->status_pengembalian === Pengembalian::STATUS_MENUNGGU_VALIDASI;
                                    $telat = D::hariTerlambat($pinjam?->tgl_jatuh_tempo, $k->tgl_pengembalian);
                                    $nilaiDenda = $antri ? $telat * $tarif : (float) $k->denda;
                                @endphp
                                <tr class="align-top" data-cat="{{ $antri ? 'menunggu-validasi' : 'selesai' }}">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-blue-700">{{ D::kode('KMB', $k->id_pengembalian) }}</p>
                                        <p class="text-xs text-slate-500">Diajukan {{ D::tanggal($k->tgl_pengembalian) }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $pinjam?->anggota?->nama_anggota ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $pinjam?->anggota?->email_anggota }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold leading-snug">{{ $pinjam?->buku?->judul_buku ?? '-' }}</p>
                                        <p class="font-mono text-xs text-slate-500">{{ $pinjam ? D::kode('BK', $pinjam->id_buku) : '' }}</p>
                                    </td>
                                    <td class="px-3 py-4 text-xs">
                                        <p>{{ D::tanggal($pinjam?->tgl_jatuh_tempo) }}</p>
                                        @if ($telat > 0)
                                            <span class="mt-1 inline-flex rounded-md bg-red-100 px-2 py-1 font-semibold text-red-700">Terlambat {{ $telat }} hari</span>
                                        @else
                                            <span class="mt-1 inline-flex rounded-md bg-emerald-100 px-2 py-1 font-semibold text-emerald-700">Tepat waktu</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <p @class(['font-bold', 'text-red-600' => $nilaiDenda > 0])>{{ D::rupiah($nilaiDenda) }}</p>
                                        @if ($antri)
                                            <p class="text-xs text-slate-500">{{ $telat > 0 ? $telat.' hr × '.D::rupiah($tarif).' (estimasi)' : 'Bebas denda' }}</p>
                                        @else
                                            <p class="text-xs {{ $k->status_denda === Pengembalian::DENDA_BELUM_DIBAYAR ? 'text-amber-700' : 'text-slate-500' }}">{{ ucfirst($k->status_denda) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 pr-5">@include('petugas.partials.aksi-kembali', ['k' => $k])</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pengajuan pengembalian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Aturan dan log validasi">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <h2 class="text-base font-extrabold">Aturan Validasi</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    <li class="rounded-xl bg-[#EEF3FC] p-3"><b>Baik:</b> stok buku bertambah 1 eksemplar.</li>
                    <li class="rounded-xl bg-[#EEF3FC] p-3"><b>Rusak atau hilang:</b> stok tidak bertambah.</li>
                    <li class="rounded-xl bg-[#EEF3FC] p-3"><b>Denda:</b> hari terlambat × {{ D::rupiah($tarif) }}, dihitung dari jatuh tempo sampai tanggal pengajuan pengembalian.</li>
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                    Validasi oleh {{ Str::words($namaPetugas, 1, '') }}
                </h2>
                <ul class="mt-4 space-y-3 text-xs">
                    @forelse ($logSaya as $l)
                        <li class="rounded-xl bg-[#EEF3FC] p-3">
                            <p class="flex items-center justify-between gap-2"><b class="text-blue-700">{{ D::kode('KMB', $l->id_pengembalian) }}</b><span class="text-slate-500">{{ $l->updated_at?->diffForHumans() }}</span></p>
                            <p class="mt-1 text-slate-700">{{ $l->peminjaman?->buku?->judul_buku ?? '-' }} • kondisi {{ $l->kondisi_buku ?? '-' }} • denda {{ D::rupiah($l->denda) }}</p>
                        </li>
                    @empty
                        <li class="rounded-xl bg-[#EEF3FC] p-4 text-center text-slate-500">Belum ada pengembalian yang Anda validasi.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>

    <section class="mt-6 rounded-2xl bg-[#EEF3FC] p-5" aria-labelledby="judul-alur">
        <h2 id="judul-alur" class="text-sm font-bold text-blue-800">Alur validasi pengembalian</h2>
        <ol class="mt-3 grid gap-3 text-sm text-slate-700 sm:grid-cols-2 xl:grid-cols-4">
            <li class="rounded-xl bg-white p-4"><b class="block">1. Anggota mengajukan</b>Anggota menekan ajukan pengembalian; status peminjaman menjadi "pengembalian diajukan".</li>
            <li class="rounded-xl bg-white p-4"><b class="block">2. Periksa fisik buku</b>Petugas memeriksa buku lalu memilih kondisi: baik, rusak, atau hilang.</li>
            <li class="rounded-xl bg-white p-4"><b class="block">3. Denda dihitung</b>Sistem membandingkan jatuh tempo dengan tanggal pengembalian dan mencatat denda.</li>
            <li class="rounded-xl bg-white p-4"><b class="block">4. Status &amp; stok</b>Peminjaman menjadi "dikembalikan". Stok bertambah hanya untuk buku kondisi baik.</li>
        </ol>
    </section>
@endsection

@push('scripts')
<script>
    // Tampilan awal: antrean "Menunggu validasi".
    document.querySelector('[data-tab="menunggu-validasi"]')?.click();
</script>
@endpush
