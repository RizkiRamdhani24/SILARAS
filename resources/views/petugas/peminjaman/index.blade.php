@extends('petugas.layout')

@section('title', 'Validasi Peminjaman')

@php
    use App\Models\Peminjaman;
    use App\Support\PetugasData as D;
    use Illuminate\Support\Str;

    [$namaPetugas, $shiftPetugas] = D::identitasPetugas();

    // $peminjaman dari controller Exel (PeminjamanController@indexPetugas: semua status, terbaru dulu, dengan buku/anggota/petugas).
    // Bila halaman dibuka lewat rute di web.php yang belum memakai controller itu, data diambil langsung dari tabel peminjaman.
    $semua = isset($peminjaman)
        ? collect($peminjaman)
        : Peminjaman::with(['buku', 'anggota', 'petugas'])->latest('id_peminjaman')->get();

    $hitung = fn (string $status) => $semua->where('status_peminjaman', $status)->count();
    $menunggu = $semua->where('status_peminjaman', Peminjaman::STATUS_MENUNGGU_VALIDASI);
    $kritisDiantrean = $menunggu->filter(fn ($p) => ($p->buku?->stock ?? 0) <= 1)->pluck('id_buku')->unique()->count();
    $tampil = $semua->take(100);

    $tab = [
        ['menunggu-validasi', 'Menunggu validasi', $hitung(Peminjaman::STATUS_MENUNGGU_VALIDASI)],
        ['dipinjam', 'Dipinjam', $hitung(Peminjaman::STATUS_DIPINJAM)],
        ['terlambat', 'Terlambat', $hitung(Peminjaman::STATUS_TERLAMBAT)],
        ['pengembalian-diajukan', 'Pengembalian diajukan', $hitung(Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN)],
        ['dikembalikan', 'Dikembalikan', $hitung(Peminjaman::STATUS_DIKEMBALIKAN)],
        ['ditolak', 'Ditolak', $hitung(Peminjaman::STATUS_DITOLAK)],
        ['all', 'Semua', $semua->count()],
    ];

    // Log validasi: peminjaman yang diproses (disetujui/ditolak) oleh petugas yang login.
    $idSaya = auth('petugas')->id();
    $logSaya = $idSaya
        ? $semua->where('id_petugas', $idSaya)->sortByDesc('updated_at')->take(5)
        : collect();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Validasi &amp; Proses Peminjaman Buku</h1>
            <p class="mt-1 text-sm text-slate-600">Periksa pengajuan peminjaman dari anggota. Menyetujui akan mengurangi stok buku dan menetapkan jatuh tempo {{ D::lamaPinjam() }} hari.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan peminjaman">
        @include('petugas.partials.stat-card', ['label' => 'Antrean validasi', 'value' => $menunggu->count(), 'suffix' => 'Pengajuan', 'icon' => 'borrow', 'tone' => 'amber', 'footer' => 'Menunggu keputusan petugas'])
        @include('petugas.partials.stat-card', ['label' => 'Sedang dipinjam', 'value' => $hitung(Peminjaman::STATUS_DIPINJAM) + $hitung(Peminjaman::STATUS_TERLAMBAT), 'suffix' => 'Peminjaman', 'icon' => 'book', 'tone' => 'blue', 'footer' => 'Status dipinjam dan terlambat'])
        @include('petugas.partials.stat-card', ['label' => 'Stok kritis di antrean', 'value' => $kritisDiantrean, 'suffix' => 'Judul diajukan', 'icon' => 'alert', 'tone' => 'red', 'labelTone' => 'text-red-600', 'footer' => 'Stok ≤ 1 eksemplar, cek fisik dulu', 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Terlambat', 'value' => $hitung(Peminjaman::STATUS_TERLAMBAT), 'suffix' => 'Peminjaman', 'icon' => 'shield', 'tone' => 'amber', 'footer' => 'Lewat jatuh tempo, belum kembali'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-antrean">
            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center gap-3">
                    <label class="relative min-w-52 flex-1">
                        <span class="sr-only">Cari ID pinjam, nama anggota, atau judul buku</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" data-search="#tabel-pinjam" placeholder="Cari ID pinjam, anggota, atau judul buku..."
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                </div>
                <div data-tabs="#tabel-pinjam" class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    @foreach ($tab as [$kode, $label, $jumlah])
                        <button type="button" data-tab="{{ $kode }}"
                                class="rounded-lg bg-[#EEF1F8] px-3 py-2 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }} ({{ $jumlah }})</button>
                    @endforeach
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap items-center justify-between gap-3 bg-[#EEF3FC] px-5 py-4">
                    <h2 id="judul-antrean" class="flex items-center gap-2 text-sm font-bold">
                        @include('petugas.partials.icon', ['name' => 'return', 'class' => 'size-5 text-blue-700'])
                        Daftar Pengajuan Peminjaman
                    </h2>
                    @if ($semua->count() > $tampil->count())
                        <span class="text-xs text-slate-500">Menampilkan {{ $tampil->count() }} terbaru dari {{ $semua->count() }}</span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table id="tabel-pinjam" class="w-full min-w-[48rem] text-left text-sm">
                        <thead class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr class="border-b border-slate-100">
                                <th scope="col" class="px-5 py-3">ID &amp; status</th>
                                <th scope="col" class="px-3 py-3">Anggota pemohon</th>
                                <th scope="col" class="px-3 py-3">Buku diajukan &amp; stok</th>
                                <th scope="col" class="px-3 py-3">Tanggal</th>
                                <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tampil as $p)
                                @php
                                    [$labelStatus, $kelasStatus] = D::statusPeminjaman($p->status_peminjaman);
                                    $stok = (int) ($p->buku?->stock ?? 0);
                                    $antri = $p->status_peminjaman === Peminjaman::STATUS_MENUNGGU_VALIDASI;
                                    $telat = in_array($p->status_peminjaman, [Peminjaman::STATUS_DIPINJAM, Peminjaman::STATUS_TERLAMBAT], true)
                                        ? D::hariTerlambat($p->tgl_jatuh_tempo) : 0;
                                @endphp
                                <tr @class(['align-top', 'bg-red-50/50' => $antri && $stok < 1]) data-cat="{{ Str::slug($p->status_peminjaman) }}">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-blue-700">{{ D::kode('PMJ', $p->id_peminjaman) }}</p>
                                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $kelasStatus }}">{{ $labelStatus }}</span>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $p->anggota?->nama_anggota ?? '-' }}</p>
                                        <p class="text-xs text-slate-500">{{ $p->anggota?->email_anggota }}</p>
                                        @if ($p->anggota?->no_telp)<p class="text-xs text-slate-500">{{ $p->anggota->no_telp }}</p>@endif
                                    </td>
                                    <td class="px-3 py-4">
                                        <div class="flex gap-3">
                                            @include('petugas.partials.cover', ['i' => $p->id_buku])
                                            <div class="min-w-0">
                                                <p class="font-semibold leading-snug">{{ $p->buku?->judul_buku ?? '-' }}</p>
                                                <p class="text-xs text-slate-500">{{ D::kode('BK', $p->id_buku) }}@if ($p->buku?->rak) • Rak {{ $p->buku->rak }}@endif</p>
                                                @if ($antri)
                                                    <span @class([
                                                        'mt-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium',
                                                        'bg-red-100 text-red-700' => $stok < 1,
                                                        'bg-amber-100 text-amber-800' => $stok === 1,
                                                        'bg-emerald-50 text-emerald-700' => $stok > 1,
                                                    ])>
                                                        @if ($stok < 1) Stok habis, tidak bisa disetujui
                                                        @elseif ($stok === 1) Stok kritis: 1 eks
                                                        @else Tersedia (stok: {{ $stok }})
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-4 text-xs text-slate-600">
                                        <p>Diajukan {{ D::tanggal($p->tgl_peminjaman) }}</p>
                                        @if ($p->tgl_jatuh_tempo)
                                            <p class="font-semibold {{ $telat > 0 ? 'text-red-700' : 'text-amber-700' }}">Jatuh tempo {{ D::tanggal($p->tgl_jatuh_tempo) }}@if ($telat > 0) (telat {{ $telat }} hari)@endif</p>
                                        @elseif ($antri)
                                            <p class="font-semibold text-amber-700">Jatuh tempo +{{ D::lamaPinjam() }} hari saat disetujui</p>
                                        @endif
                                        @if ($p->petugas)<p class="mt-1">Diproses: {{ $p->petugas->nama_petugas }}</p>@endif
                                    </td>
                                    <td class="px-3 py-4 pr-5">@include('petugas.partials.aksi-pinjam', ['p' => $p])</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pengajuan peminjaman.</td></tr>
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
                    <li class="rounded-xl bg-[#EEF3FC] p-3">Stok buku harus lebih dari 0. Menyetujui mengurangi stok 1 eksemplar.</li>
                    <li class="rounded-xl bg-[#EEF3FC] p-3">Jatuh tempo ditetapkan {{ D::lamaPinjam() }} hari sejak disetujui.</li>
                    <li class="rounded-xl bg-[#EEF3FC] p-3">Pengajuan yang sudah diproses tidak bisa diproses lagi.</li>
                </ul>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700'])
                    Validasi oleh {{ Str::words($namaPetugas, 1, '') }}
                </h2>
                <ul class="mt-4 space-y-3">
                    @forelse ($logSaya as $l)
                        @php $ditolak = $l->status_peminjaman === Peminjaman::STATUS_DITOLAK; @endphp
                        <li @class(['rounded-xl p-3 text-xs', 'bg-[#EEF3FC]' => ! $ditolak, 'bg-red-50' => $ditolak])>
                            <div class="flex items-center justify-between gap-2">
                                <p @class(['font-bold', 'text-blue-700' => ! $ditolak, 'text-red-700' => $ditolak])>{{ D::kode('PMJ', $l->id_peminjaman) }} • {{ $ditolak ? 'Ditolak' : 'Disetujui' }}</p>
                                <span class="text-slate-500">{{ $l->updated_at?->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-slate-700">{{ $l->buku?->judul_buku ?? '-' }} • {{ $l->anggota?->nama_anggota ?? '-' }}</p>
                        </li>
                    @empty
                        <li class="rounded-xl bg-[#EEF3FC] p-4 text-center text-xs text-slate-500">Belum ada pengajuan yang Anda proses.</li>
                    @endforelse
                </ul>
                <a href="{{ D::link('transaksi') }}" class="mt-4 block rounded-xl bg-[#EEF3FC] px-3 py-2.5 text-center text-xs font-semibold text-blue-700 hover:bg-blue-100">Buka seluruh riwayat transaksi →</a>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
<script>
    // Tampilan awal: antrean "Menunggu validasi".
    document.querySelector('[data-tab="menunggu-validasi"]')?.click();
</script>
@endpush
