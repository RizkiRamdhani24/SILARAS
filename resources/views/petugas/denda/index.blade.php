@extends('petugas.layout')

@section('title', 'Denda')

@php
    $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');
    $namaPetugas = auth('petugas')->user()->nama_petugas ?? 'Eom Seonghyeon';
    $shiftPetugas = ucfirst(strtolower(auth('petugas')->user()->shift ?? 'pagi'));
    $tarif = 2000;

    $denda = \Illuminate\Support\Facades\Schema::hasTable('pengembalian')
        ? \App\Models\Pengembalian::query()->with(['peminjaman.anggota', 'peminjaman.buku'])
            ->orderByDesc('tgl_pengembalian')
            ->get()
            ->map(function ($return) {
                $loan = $return->peminjaman;
                $lateDays = 0;
                if ($loan && $loan->tgl_jatuh_tempo && $return->tgl_pengembalian) {
                    $lateDays = max(0, $return->tgl_pengembalian->diffInDays($loan->tgl_jatuh_tempo, false));
                }

                return [
                    'id' => 'DND-' . $return->id_pengembalian,
                    'ref' => 'KMB-' . $return->id_pengembalian,
                    'nama' => $loan?->anggota?->nama_anggota ?? 'Anggota',
                    'nim' => $loan?->anggota?->id_anggota ?? '-',
                    'prodi' => 'Anggota',
                    'buku' => $loan?->buku?->judul_buku ?? 'Judul tidak tersedia',
                    'tempo' => $loan?->tgl_jatuh_tempo ? $loan->tgl_jatuh_tempo->format('d M') : '-',
                    'telat' => $lateDays,
                    'nominal' => (float) $return->denda,
                    'lunas' => $return->status_denda === \App\Models\Pengembalian::DENDA_LUNAS,
                    'metode' => $return->status_denda === \App\Models\Pengembalian::DENDA_LUNAS ? 'Lunas' : 'Menunggu kasir',
                ];
            })->all()
        : [];

    $log = [
        ['nama' => $namaPetugas, 'ref' => 'Sinkronisasi denda', 'jam' => 'Sekarang', 'nominal' => 0, 'ikon' => 'cash'],
    ];

    $alur = [
        ['Hitung otomatis sistem', 'Sistem mendeteksi keterlambatan saat petugas memproses kembali buku. Rumus: selisih hari × Rp 2.000.', 'Modul UC-09', 'Trigger: tgl jatuh tempo lewat'],
        ['Tunggakan & blokir hak pinjam', 'Tunggakan tercatat pada profil anggota. Berdasarkan itu, hak pinjam baru anggota langsung ditangguhkan otomatis hingga tunggakan selesai.', 'Status: belum dibayar', 'Blokir otomatis kuota peminjaman'],
        ['Pembayaran kasir loket', 'Petugas loket memverifikasi nominal tagihan denda, menerima pembayaran (tunai/QRIS), dan memverifikasi uang fisik atau mutasi digital.', 'Modul UC-11', 'Koreksi kembalian kasir'],
        ['Pemulihan hak anggota', 'Status denda berubah menjadi "Lunas", kuitansi digital tercatat di buku kas, dan sistem secara real-time membuka blokir peminjaman sehingga anggota bebas bertransaksi.', 'Status: lunas', 'Bebas tunggakan & siap pinjam'],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Manajemen &amp; Pembayaran Denda Keterlambatan</h1>
            <p class="mt-1 text-sm text-slate-600">Kalkulasi otomatis denda per hari dan pencatatan pembayaran denda kasir untuk pemulihan status bebas tunggakan anggota.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-demo-click="Tampilan contoh: cetak kuitansi belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'printer', 'class' => 'size-4']) Cetak Kuitansi Terakhir
            </button>
            <button type="button" data-demo-click="Tampilan contoh: ekspor laporan kas belum terhubung ke backend."
                    class="flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-slate-100 hover:bg-slate-50">
                @include('petugas.partials.icon', ['name' => 'download', 'class' => 'size-4']) Ekspor Laporan Kas
            </button>
            <button type="button" id="kasir-cepat" class="flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-4']) Kasir Cepat (F2)
            </button>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan denda">
        @include('petugas.partials.stat-card', ['label' => 'Total denda belum dibayar', 'value' => $rp(42000), 'icon' => 'borrow', 'tone' => 'red', 'valueTone' => 'text-red-600', 'footer' => '11 transaksi menunggak • hak pinjam terkunci', 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Denda terkumpul hari ini', 'value' => $rp(145000), 'icon' => 'cash', 'tone' => 'blue', 'valueTone' => 'text-blue-700', 'footer' => '28 transaksi lunas • Kasir: '.\Illuminate\Support\Str::words($namaPetugas, 1, '')])
        @include('petugas.partials.stat-card', ['label' => 'Tarif denda sistem (UC-09)', 'value' => $rp($tarif), 'suffix' => '/hari', 'icon' => 'borrow', 'tone' => 'blue', 'footer' => 'Hari telat × tarif • auto-kalkulasi'])
        @include('petugas.partials.stat-card', ['label' => 'Anggota tertahan sanksi', 'value' => '7', 'suffix' => 'Mahasiswa', 'icon' => 'ban', 'tone' => 'amber', 'footer' => 'Blokir pinjaman (FR-04) • auto-unlock'])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar denda</h2>
            <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div data-tabs="#tabel-denda" class="flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach (['all' => 'Semua status (39)', 'belum' => 'Belum dibayar (11)', 'lunas' => 'Lunas hari ini (28)'] as $nilai => $label)
                        <button type="button" data-tab="{{ $nilai }}" data-active="{{ $nilai === 'all' ? 'true' : 'false' }}" aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                                class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white">{{ $label }}</button>
                    @endforeach
                </div>
                <label class="relative min-w-48 flex-1">
                    <span class="sr-only">Cari ID denda, NIM, atau judul buku</span>
                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                    <input type="search" data-search="#tabel-denda" placeholder="Cari ID denda, NIM, judul..."
                           class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </label>
                <p class="flex w-full items-center gap-2 text-xs text-slate-500">
                    @include('petugas.partials.icon', ['name' => 'clock', 'class' => 'size-4']) Menampilkan rekap denda real-time terhitung modul pengembalian.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-denda" class="w-full min-w-[50rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID denda &amp; ref</th>
                                <th scope="col" class="px-3 py-3">Anggota &amp; NIM</th>
                                <th scope="col" class="px-3 py-3">Buku &amp; keterlambatan</th>
                                <th scope="col" class="px-3 py-3">Nominal denda</th>
                                <th scope="col" class="px-3 py-3">Status denda</th>
                                <th scope="col" class="px-3 py-3 pr-5">Metode &amp; jam</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($denda as $d)
                                <tr class="align-top" data-cat="{{ $d['lunas'] ? 'lunas' : 'belum' }}">
                                    <td class="px-5 py-4"><p class="font-mono text-xs font-bold">{{ $d['id'] }}</p><p class="font-mono text-xs text-slate-500">Ref: {{ $d['ref'] }}</p></td>
                                    <td class="px-3 py-4"><p class="font-semibold">{{ $d['nama'] }}</p><p class="text-xs text-slate-500">{{ $d['nim'] }} • {{ $d['prodi'] }}</p></td>
                                    <td class="px-3 py-4"><p>{{ $d['buku'] }}</p><p class="text-xs text-slate-500">Tempo: {{ $d['tempo'] }} • <span class="font-semibold text-red-600">Terlambat {{ $d['telat'] }} hari</span></p></td>
                                    <td class="px-3 py-4"><p class="font-bold {{ $d['lunas'] ? 'text-[#0F1B3D]' : 'text-red-600' }}">{{ $rp($d['nominal']) }}</p><p class="text-xs text-slate-500">{{ $d['telat'] }} hr × {{ $rp($tarif) }}</p></td>
                                    <td class="px-3 py-4">
                                        @if ($d['lunas'])
                                            <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">@include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-3.5']) Lunas</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-semibold text-red-700"><span class="size-1.5 rounded-full bg-red-600"></span>Belum dibayar</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-4 pr-5 text-xs italic text-slate-500">{{ $d['metode'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('petugas.partials.pagination', ['from' => 1, 'to' => 5, 'total' => 39, 'noun' => 'catatan denda transaksi sirkulasi', 'last' => 3])
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Kasir pembayaran denda">
            <section class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-kasir">
                <div class="flex items-start justify-between gap-3 bg-blue-700 p-5 text-white">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-white/15">@include('petugas.partials.icon', ['name' => 'cash'])</span>
                        <div>
                            <h2 id="judul-kasir" class="text-base font-extrabold leading-tight">Kasir Pembayaran Denda</h2>
                            <p class="text-xs text-blue-100">Modul UC-11 • Loket sirkulasi 1</p>
                        </div>
                    </div>
                    <span class="rounded-lg bg-white/15 px-2 py-1 text-xs font-semibold">Shift {{ $shiftPetugas }}</span>
                </div>

                {{-- TODO backend (Herlyan): arahkan ke route pembayaran denda, mis. route('petugas.denda.bayar') --}}
                <form method="POST" action="#" id="form-kasir" data-demo="Tampilan contoh: konfirmasi pelunasan belum terhubung ke backend." data-total="6000" class="space-y-4 p-5">
                    @csrf
                    <div>
                        <label for="anggota-kasir" class="text-xs font-semibold">Pilih anggota / scan ID mahasiswa</label>
                        <div class="mt-1 flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 focus-within:border-blue-600 focus-within:ring-2 focus-within:ring-blue-600/30">
                            @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-4 text-slate-400'])
                            <input id="anggota-kasir" name="anggota" value="21040055 - Farhan Maulana" class="min-w-0 flex-1 border-0 p-0 text-sm focus:ring-0 focus:outline-none">
                            <span class="shrink-0 rounded-md bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">Tunggakan aktif</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-[#EEF3FC] p-4 text-xs">
                        <p class="flex items-center justify-between gap-2"><span class="font-semibold uppercase tracking-wide text-slate-500">Judul buku terlambat</span><span class="font-mono font-bold text-blue-700">DND-2024-0089</span></p>
                        <p class="mt-1 text-sm font-bold">Algoritma Pemrograman C++</p>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <p><span class="block text-slate-500">Jatuh tempo</span><b>18 Okt 2024</b></p>
                            <p><span class="block text-slate-500">Dikembalikan</span><b class="text-red-600">21 Okt (telat 3 hari)</b></p>
                        </div>
                        <div class="mt-3 flex items-center justify-between gap-3 rounded-lg bg-white p-3">
                            <p><span class="block text-slate-500">Rumus hitung (UC-09)</span><b>3 hari × {{ $rp($tarif) }} / hari</b></p>
                            <p class="text-right"><span class="block text-slate-500">Total tagihan</span><b class="text-lg text-red-600">{{ $rp(6000) }}</b></p>
                        </div>
                    </div>

                    <fieldset>
                        <legend class="text-xs font-semibold">Metode pembayaran</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="metode" value="tunai" checked class="text-blue-700 focus:ring-blue-600"> @include('petugas.partials.icon', ['name' => 'cash', 'class' => 'size-4 text-slate-500']) Tunai (cash)
                            </label>
                            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 p-3 has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50">
                                <input type="radio" name="metode" value="qris" class="text-blue-700 focus:ring-blue-600"> @include('petugas.partials.icon', ['name' => 'qr', 'class' => 'size-4 text-slate-500']) QRIS statis
                            </label>
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="uang-diterima" class="text-xs font-semibold">Uang diterima (Rp)</label>
                            <input id="uang-diterima" name="uang_diterima" type="number" min="0" step="500" value="10000" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                        </div>
                        <div>
                            <span class="text-xs font-semibold">Kembalian</span>
                            <output id="kembalian" for="uang-diterima" class="mt-1 block rounded-xl bg-[#EEF3FC] px-3 py-2.5 text-sm font-bold text-blue-700">{{ $rp(4000) }}</output>
                        </div>
                    </div>

                    <div>
                        <label for="catatan-kasir" class="text-xs font-semibold">Catatan petugas kasir</label>
                        <input id="catatan-kasir" name="catatan" value="Pembayaran tunai lunas di meja sirkulasi loket 1" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-blue-600 focus:ring-2 focus:ring-blue-600/30 focus:outline-none">
                    </div>

                    <p class="flex gap-2 rounded-xl bg-blue-50 p-3 text-xs text-blue-900">
                        @include('petugas.partials.icon', ['name' => 'shield', 'class' => 'size-4'])
                        <span><b>Postcondition:</b> konfirmasi pembayaran mengubah status denda menjadi "Lunas" dan langsung membuka blokir peminjaman anggota.</span>
                    </p>

                    <button type="submit" id="konfirmasi-lunas" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                        @include('petugas.partials.icon', ['name' => 'check-circle', 'class' => 'size-4']) Konfirmasi Lunas &amp; Buka Blokir
                    </button>
                </form>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-log">
                <h2 id="judul-log" class="flex items-center gap-2 text-sm font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-5 text-blue-700']) Log kasir hari ini (shift {{ \Illuminate\Support\Str::words($namaPetugas, 1, '') }})
                </h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($log as $l)
                        <li class="flex items-center gap-3 rounded-xl bg-[#EEF3FC] p-3">
                            <span class="grid size-8 shrink-0 place-items-center rounded-full bg-white text-blue-700">@include('petugas.partials.icon', ['name' => $l['ikon'], 'class' => 'size-4'])</span>
                            <div class="min-w-0 flex-1"><p class="font-semibold">{{ $l['nama'] }}</p><p class="truncate text-xs text-slate-500">{{ $l['ref'] }}</p></div>
                            <div class="text-right"><p class="font-bold">{{ $rp($l['nominal']) }}</p><p class="text-xs text-slate-500">{{ $l['jam'] }}</p></div>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                    <span>Total transaksi kasir: <b class="text-[#0F1B3D]">2</b> transaksi</span>
                    <a href="{{ url('/petugas/transaksi') }}" class="font-semibold text-blue-700 hover:underline">Buka riwayat kas lengkap →</a>
                </div>
            </section>
        </aside>
    </div>

    <section class="mt-6 rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-alur">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="judul-alur" class="text-base font-extrabold">Alur Integrasi Hitung &amp; Bayar Denda</h2>
                <p class="text-xs text-slate-500">Standard operating procedure (SOP) otomasi sanksi keuangan dan pelepasan status penangguhan sirkulasi anggota.</p>
            </div>
            <span class="text-xs font-semibold text-blue-700">Validasi terhubung modul pengembalian (UC-08)</span>
        </div>
        <ol class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($alur as $n => [$judul, $isi, $label, $akhir])
                <li class="flex flex-col rounded-xl bg-[#EEF3FC] p-4">
                    <div class="flex items-center justify-between gap-2">
                        <span class="grid size-7 place-items-center rounded-lg bg-blue-700 text-xs font-bold text-white">{{ $n + 1 }}</span>
                        <span class="rounded-md bg-white px-2 py-1 font-mono text-[11px] font-semibold text-blue-700">{{ $label }}</span>
                    </div>
                    <p class="mt-3 text-sm font-bold">{{ $judul }}</p>
                    <p class="mt-1 flex-1 text-xs text-slate-600">{{ $isi }}</p>
                    <p class="mt-3 border-t border-slate-200 pt-3 text-xs font-semibold text-blue-700">{{ $akhir }}</p>
                </li>
            @endforeach
        </ol>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('form-kasir');
    const uang = document.getElementById('uang-diterima');
    const kembalian = document.getElementById('kembalian');
    const tombol = document.getElementById('konfirmasi-lunas');
    const total = parseInt(form.dataset.total, 10);
    const rupiah = (n) => 'Rp ' + n.toLocaleString('id-ID');

    // Hitung kembalian; tombol konfirmasi aktif hanya jika uang cukup
    const hitung = () => {
        const nilai = parseInt(uang.value, 10) || 0;
        kembalian.textContent = rupiah(Math.max(0, nilai - total));
        tombol.disabled = nilai < total;
    };
    uang.addEventListener('input', hitung);
    hitung();

    // Metode QRIS: uang diterima otomatis sama dengan tagihan
    form.querySelectorAll('input[name="metode"]').forEach((radio) => radio.addEventListener('change', () => {
        if (radio.value === 'qris') uang.value = total;
        hitung();
    }));

    // Pintasan F2 dan tombol Kasir Cepat
    const fokus = () => { uang.focus(); uang.select(); };
    document.getElementById('kasir-cepat').addEventListener('click', fokus);
    document.addEventListener('keydown', (e) => { if (e.key === 'F2') { e.preventDefault(); fokus(); } });
})();
</script>
@endpush
