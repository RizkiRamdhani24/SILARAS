@extends('petugas.layout')

@section('title', 'Kelola Anggota')

@php
    $rp = fn ($n) => 'Rp '.number_format($n, 0, ',', '.');
    $totalAktif = $anggota->where('status', 'Aktif')->count();
    $totalDitangguhkan = $anggota->where('status', 'Ditangguhkan')->count();
    $totalTunggakan = $anggota->where('cat', 'tunggakan')->count();
    $totalPinjaman = $anggota->sum('pinjam');

    $bar = ['blue' => 'bg-blue-700', 'amber' => 'bg-amber-500', 'red' => 'bg-red-600'];
    $teksCatatan = ['blue' => 'text-slate-500', 'amber' => 'text-amber-700', 'red' => 'text-red-600'];
    $pil = [
        'Aktif' => 'bg-emerald-100 text-emerald-700',
        'Perlu pelunasan' => 'bg-amber-100 text-amber-800',
        'Ditangguhkan' => 'bg-red-100 text-red-700',
    ];
@endphp

@section('content')
    @if (session('success'))
        <div role="status" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div role="alert" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Data Anggota Perpustakaan</h1>
            <p class="mt-1 text-sm text-slate-600">Verifikasi akun pemustaka, pemantauan status pinjaman aktif, serta rekonsiliasi denda sirkulasi secara terpadu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: cetak kartu belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-4']) Cetak Kartu Anggota
            </button>
            <button type="button" data-demo-click="Tampilan contoh: tambah anggota belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'plus-circle', 'class' => 'size-4']) Tambah Anggota Baru
            </button>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan anggota">
        @include('petugas.partials.stat-card', ['label' => 'Total terdaftar', 'value' => $anggota->count(), 'icon' => 'users', 'tone' => 'blue', 'footer' => 'Data anggota terdaftar'])
        @include('petugas.partials.stat-card', ['label' => 'Pinjaman berjalan', 'value' => $totalPinjaman, 'icon' => 'book', 'tone' => 'blue', 'footer' => 'Termasuk pengajuan menunggu validasi'])
        @include('petugas.partials.stat-card', ['label' => 'Dengan tunggakan denda', 'value' => $totalTunggakan, 'icon' => 'borrow', 'tone' => 'red', 'valueTone' => 'text-red-600', 'labelTone' => 'text-red-600', 'footer' => 'Perlu pelunasan'])
        @include('petugas.partials.stat-card', ['label' => 'Akun aktif', 'value' => $totalAktif, 'icon' => 'user', 'tone' => 'amber', 'footer' => $totalDitangguhkan . ' akun nonaktif'])
    </section>

    <section class="mt-6 space-y-4" aria-labelledby="judul-tabel">
        <h2 id="judul-tabel" class="sr-only">Daftar anggota</h2>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div data-tabs="#tabel-anggota" class="flex flex-wrap gap-2 text-sm font-semibold">
                @foreach (['all' => 'Semua ('.$anggota->count().')', 'bebas' => 'Bebas denda ('.$anggota->where('cat', 'bebas')->count().')', 'tunggakan' => 'Ada tunggakan ('.$totalTunggakan.')', 'ditangguhkan' => 'Ditangguhkan ('.$totalDitangguhkan.')'] as $nilai => $label)
                    <button type="button" data-tab="{{ $nilai }}" data-active="{{ $nilai === 'all' ? 'true' : 'false' }}" aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                            class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }}</button>
                @endforeach
            </div>
            <label class="relative min-w-56 flex-1">
                <span class="sr-only">Cari nama anggota, ID, atau email</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                <input type="search" data-search="#tabel-anggota" placeholder="Cari nama anggota, ID (AGT-...), atau email..."
                       class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
            </label>
            <button type="button" data-demo-click="Tampilan contoh: filter lanjutan belum tersedia."
                    class="flex items-center gap-2 rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-blue-50">
                @include('petugas.partials.icon', ['name' => 'filter', 'class' => 'size-4']) Filter
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="overflow-x-auto">
                <table id="tabel-anggota" class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="w-10 py-3 pl-5"><input type="checkbox" data-check-all="#tabel-anggota" class="size-4 rounded border-slate-300" aria-label="Pilih semua anggota"></th>
                            <th scope="col" class="px-3 py-3">ID &amp; NIM</th>
                            <th scope="col" class="px-3 py-3">Nama anggota</th>
                            <th scope="col" class="px-3 py-3">Kontak resmi</th>
                            <th scope="col" class="px-3 py-3">Sirkulasi &amp; limit kuota</th>
                            <th scope="col" class="px-3 py-3">Status anggota</th>
                            <th scope="col" class="px-3 py-3 pr-5 text-right">Aksi cepat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($anggota as $a)
                            @php
                                $inisial = collect(explode(' ', $a['nama']))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                            @endphp
                            <tr @class(['align-middle', 'bg-red-50/50' => $a['status'] === 'Ditangguhkan']) data-cat="{{ $a['cat'] }}">
                                <td class="py-4 pl-5"><input type="checkbox" data-row-check class="size-4 rounded border-slate-300" aria-label="Pilih {{ $a['nama'] }}"></td>
                                <td class="px-3 py-4"><p class="font-bold text-blue-700">{{ $a['id'] }}</p><p class="text-xs text-slate-500">{{ $a['induk'] }}</p></td>
                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-3">
                                        <span @class(['grid size-10 shrink-0 place-items-center rounded-full text-xs font-bold', 'bg-red-100 text-red-700' => $a['status'] === 'Ditangguhkan', 'bg-blue-100 text-blue-700' => $a['status'] !== 'Ditangguhkan'])>{{ $inisial }}</span>
                                        <div><p class="font-semibold">{{ $a['nama'] }}</p><p class="text-xs text-slate-500">{{ $a['sub'] }}</p></div>
                                    </div>
                                </td>
                                <td class="px-3 py-4"><p>{{ $a['email'] }}</p><p class="text-xs text-slate-500">{{ $a['telp'] }}</p></td>
                                <td class="px-3 py-4">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <b class="text-sm">{{ $a['pinjam'] }} / 3 buku</b>
                                        <span class="{{ $teksCatatan[$a['tone']] }}">({{ $a['catatan'] }})</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 w-40 rounded-full bg-slate-100" role="img" aria-label="Kuota terpakai {{ $a['pinjam'] }} dari 3">
                                        <div class="h-full rounded-full {{ $bar[$a['tone']] }}" style="width: {{ round($a['pinjam'] / 3 * 100) }}%"></div>
                                    </div>
                                </td>
                                <td class="px-3 py-4"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $pil[$a['status']] }}"><span class="size-1.5 rounded-full bg-current"></span>{{ $a['status'] }}</span></td>
                                <td class="px-3 py-4 pr-5">
                                    <div class="flex justify-end gap-1 text-slate-500">
                                        <button type="button" data-demo-click="Tampilan contoh: ubah data {{ $a['nama'] }} belum terhubung." class="grid size-9 place-items-center rounded-lg hover:bg-blue-50 hover:text-blue-700" aria-label="Ubah {{ $a['nama'] }}">@include('petugas.partials.icon', ['name' => 'pencil', 'class' => 'size-4'])</button>
                                        <button type="button" data-demo-click="Tampilan contoh: kartu QR {{ $a['nama'] }} belum terhubung." class="grid size-9 place-items-center rounded-lg hover:bg-blue-50 hover:text-blue-700" aria-label="Kartu QR {{ $a['nama'] }}">@include('petugas.partials.icon', ['name' => 'qr', 'class' => 'size-4'])</button>
                                        @if ($a['status'] === 'Aktif')
                                            <form method="POST" action="{{ route('petugas.anggota.nonaktifkan', $a['id_anggota']) }}" data-confirm="Nonaktifkan akun {{ $a['nama'] }}?" data-confirm-title="Nonaktifkan anggota" data-confirm-variant="danger" data-confirm-label="Ya, nonaktifkan">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" @disabled(! $a['can_disable']) class="grid size-9 place-items-center rounded-lg hover:bg-red-50 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Nonaktifkan {{ $a['nama'] }}" title="{{ $a['can_disable'] ? 'Nonaktifkan akun' : 'Harus bebas pinjaman aktif dan tunggakan denda' }}">@include('petugas.partials.icon', ['name' => 'ban', 'class' => 'size-4'])</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('petugas.partials.pagination', ['from' => 1, 'to' => 5, 'total' => '1.845', 'noun' => 'total anggota terdaftar', 'last' => 369])
        </div>

        <aside class="flex gap-3 rounded-2xl bg-amber-50 p-5 text-sm" aria-label="Prosedur bebas pustaka">
            @include('petugas.partials.icon', ['name' => 'shield', 'class' => 'size-6 text-amber-700'])
            <div>
                <p class="font-bold">Prosedur bebas pustaka &amp; pelunasan sirkulasi</p>
                <p class="mt-1 text-slate-600">Anggota dengan tunggakan denda aktif dibatasi dari transaksi peminjaman mandiri di anjungan mandiri (RFID) dan tidak dapat menerbitkan surat bebas pustaka untuk yudisium kelulusan sebelum kasir memverifikasi kwitansi resmi.</p>
            </div>
        </aside>
    </section>
@endsection
