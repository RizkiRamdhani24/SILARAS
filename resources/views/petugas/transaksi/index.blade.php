@extends('petugas.layout')

@section('title', 'Riwayat Transaksi')

@php
    use App\Models\Peminjaman;
    use App\Models\Pengembalian;
    use App\Models\Petugas;
    use Illuminate\Support\Facades\Schema;
    use Carbon\Carbon;

    $rp = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

    $petugasLogin = auth('petugas')->user();
    $namaPetugas = $petugasLogin->nama_petugas ?? 'Petugas';

    /*
    |--------------------------------------------------------------------------
    | Ambil data peminjaman
    |--------------------------------------------------------------------------
    */

    $dataPeminjaman = collect();

    if (Schema::hasTable('peminjaman')) {
        $dataPeminjaman = Peminjaman::with(['anggota', 'buku'])
            ->orderByDesc('id_peminjaman')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil data pengembalian
    |--------------------------------------------------------------------------
    */

    $dataPengembalian = collect();

    if (Schema::hasTable('pengembalian')) {
        $dataPengembalian = Pengembalian::with([
                'peminjaman.anggota',
                'peminjaman.buku',
            ])
            ->orderByDesc('id_pengembalian')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Ambil nama petugas berdasarkan ID
    |--------------------------------------------------------------------------
    */

    $idPetugas = collect()
        ->merge($dataPeminjaman->pluck('id_petugas'))
        ->merge($dataPengembalian->pluck('id_petugas'))
        ->filter()
        ->unique()
        ->values();

    $daftarPetugas = collect();

    if ($idPetugas->isNotEmpty() && Schema::hasTable('petugas')) {
        $daftarPetugas = Petugas::whereIn('id_petugas', $idPetugas)
            ->get()
            ->keyBy('id_petugas');
    }

    /*
    |--------------------------------------------------------------------------
    | Bentuk riwayat peminjaman
    |--------------------------------------------------------------------------
    */

    $trxPeminjaman = $dataPeminjaman->map(function ($loan) use ($daftarPetugas) {
        $tanggal = $loan->tgl_peminjaman
            ? Carbon::parse($loan->tgl_peminjaman)
            : null;

        $petugas = $daftarPetugas->get($loan->id_petugas);

        $status = $loan->status_peminjaman ?? '-';

        $keterangan = match ($status) {
            Peminjaman::STATUS_MENUNGGU_VALIDASI => 'Menunggu validasi',
            Peminjaman::STATUS_DIPINJAM => 'Sedang dipinjam',
            Peminjaman::STATUS_TERLAMBAT => 'Terlambat',
            Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN => 'Pengembalian diajukan',
            Peminjaman::STATUS_DIKEMBALIKAN => 'Sudah dikembalikan',
            Peminjaman::STATUS_DITOLAK => 'Peminjaman ditolak',
            default => ucfirst($status),
        };

        $tempo = $loan->tgl_jatuh_tempo
            ? Carbon::parse($loan->tgl_jatuh_tempo)->format('d M Y')
            : '-';

        return [
            'id' => 'TRX-PMJ-' . $loan->id_peminjaman,
            'ref' => 'PMJ-' . $loan->id_peminjaman,
            'jenis' => 'Peminjaman',
            'cat' => 'peminjaman',
            'tanggal' => $tanggal?->format('d M Y') ?? '-',
            'waktu' => $tanggal?->format('d M Y') ?? '-',
            'nama' => $loan->anggota?->nama_anggota ?? 'Anggota',
            'id_anggota' => $loan->anggota?->id_anggota ?? '-',
            'judul' => $loan->buku?->judul_buku ?? 'Judul tidak tersedia',
            'buku' => 'BK-' . $loan->id_buku,
            'petugas' => $petugas?->nama_petugas ?? (
                $loan->id_petugas ? 'Petugas #' . $loan->id_petugas : 'Belum divalidasi'
            ),
            'kode' => $loan->id_petugas
                ? 'ID Petugas: ' . $loan->id_petugas
                : 'Belum ada petugas',
            'nilai' => 0,
            'ket' => $keterangan . ' • Jatuh tempo ' . $tempo,
            'status' => $status,
            '_sort' => $tanggal?->timestamp ?? 0,
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | Bentuk riwayat pengembalian
    |--------------------------------------------------------------------------
    */

    $trxPengembalian = $dataPengembalian->map(function ($return) use ($daftarPetugas) {
        $loan = $return->peminjaman;

        $tanggal = $return->tgl_pengembalian
            ? Carbon::parse($return->tgl_pengembalian)
            : null;

        $petugas = $daftarPetugas->get($return->id_petugas);

        $nominalDenda = (float) ($return->denda ?? 0);

        $statusDenda = $return->status_denda ?? '-';

        $keterangan = match ($statusDenda) {
            Pengembalian::DENDA_LUNAS => 'Denda lunas',
            Pengembalian::DENDA_BELUM_DIBAYAR => 'Denda belum dibayar',
            default => $nominalDenda > 0 ? 'Denda tercatat' : 'Tidak ada denda',
        };

        $jatuhTempo = $loan?->tgl_jatuh_tempo
            ? Carbon::parse($loan->tgl_jatuh_tempo)
            : null;

        $hariTerlambat = 0;

        if ($tanggal && $jatuhTempo) {
            $hariTerlambat = max(
                0,
                (int) $jatuhTempo->diffInDays($tanggal, false)
            );
        }

        if ($hariTerlambat > 0) {
            $keterangan .= ' • Terlambat ' . $hariTerlambat . ' hari';
        }

        return [
            'id' => 'TRX-KMB-' . $return->id_pengembalian,
            'ref' => 'KMB-' . $return->id_pengembalian,
            'jenis' => 'Pengembalian',
            'cat' => 'pengembalian',
            'tanggal' => $tanggal?->format('d M Y') ?? '-',
            'waktu' => $tanggal?->format('d M Y') ?? '-',
            'nama' => $loan?->anggota?->nama_anggota ?? 'Anggota',
            'id_anggota' => $loan?->anggota?->id_anggota ?? '-',
            'judul' => $loan?->buku?->judul_buku ?? 'Judul tidak tersedia',
            'buku' => $loan?->id_buku ? 'BK-' . $loan->id_buku : '-',
            'petugas' => $petugas?->nama_petugas ?? (
                $return->id_petugas ? 'Petugas #' . $return->id_petugas : 'Belum divalidasi'
            ),
            'kode' => $return->id_petugas
                ? 'ID Petugas: ' . $return->id_petugas
                : 'Belum ada petugas',
            'nilai' => $nominalDenda,
            'ket' => $keterangan,
            'status' => $return->status_pengembalian ?? '-',
            '_sort' => $tanggal?->timestamp ?? 0,
        ];
    });

    /*
    |--------------------------------------------------------------------------
    | Gabungkan dan urutkan riwayat
    |--------------------------------------------------------------------------
    */

    $trx = $trxPeminjaman
        ->concat($trxPengembalian)
        ->sortByDesc('_sort')
        ->values()
        ->map(fn ($item) => collect($item)->except('_sort')->all());

    $jumlahPeminjaman = $dataPeminjaman->count();
    $jumlahPengembalian = $dataPengembalian->count();
    $jumlahTransaksi = $jumlahPeminjaman + $jumlahPengembalian;

    $dendaLunas = $dataPengembalian
        ->where('status_denda', Pengembalian::DENDA_LUNAS);

    $totalDendaLunas = $dendaLunas->sum('denda');

    $jumlahDendaLunas = $dendaLunas->count();

    $jumlahTerlambat = $dataPeminjaman
        ->where('status_peminjaman', Peminjaman::STATUS_TERLAMBAT)
        ->count();

    /*
    |--------------------------------------------------------------------------
    | Aktivitas petugas yang sedang login
    |--------------------------------------------------------------------------
    */

    $jumlahAktivitasPetugas = 0;

    if ($petugasLogin) {
        $jumlahAktivitasPetugas =
            $dataPeminjaman->where('id_petugas', $petugasLogin->id_petugas)->count()
            + $dataPengembalian->where('id_petugas', $petugasLogin->id_petugas)->count();
    }

    $jenisKelas = [
        'Pengembalian' => 'bg-blue-100 text-blue-700',
        'Peminjaman' => 'bg-indigo-100 text-indigo-700',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Riwayat Transaksi &amp; Audit Sirkulasi
            </h1>

            <p class="mt-1 text-sm text-slate-600">
                Pusat rekaman transaksi peminjaman dan pengembalian buku berdasarkan data database perpustakaan.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <span class="flex items-center gap-2 rounded-xl bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-800">
                @include('petugas.partials.icon', ['name' => 'user', 'class' => 'size-4'])
                {{ $namaPetugas }}
            </span>
        </div>
    </div>

    {{-- Ringkasan transaksi --}}
    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan transaksi">
        @include('petugas.partials.stat-card', [
            'label' => 'Total transaksi',
            'value' => number_format($jumlahTransaksi, 0, ',', '.'),
            'icon' => 'dashboard',
            'tone' => 'blue',
            'footer' => 'Gabungan peminjaman dan pengembalian',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Peminjaman keseluruhan',
            'value' => number_format($jumlahPeminjaman, 0, ',', '.'),
            'icon' => 'borrow',
            'tone' => 'blue',
            'footer' => 'Data dari tabel peminjaman',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Pengembalian buku',
            'value' => number_format($jumlahPengembalian, 0, ',', '.'),
            'icon' => 'return',
            'tone' => 'blue',
            'footer' => $jumlahTerlambat . ' peminjaman berstatus terlambat',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Total denda lunas',
            'value' => $rp($totalDendaLunas),
            'icon' => 'cash',
            'tone' => 'blue',
            'valueTone' => 'text-blue-700',
            'footer' => $jumlahDendaLunas . ' transaksi berstatus lunas',
            'footerTone' => 'text-blue-700',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        {{-- Tabel transaksi --}}
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">
                Daftar transaksi
            </h2>

            <div class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div data-tabs="#tabel-transaksi" class="flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach ([
                        'all' => 'Semua transaksi (' . $jumlahTransaksi . ')',
                        'peminjaman' => 'Peminjaman (' . $jumlahPeminjaman . ')',
                        'pengembalian' => 'Pengembalian (' . $jumlahPengembalian . ')',
                    ] as $nilai => $label)
                        <button
                            type="button"
                            data-tab="{{ $nilai }}"
                            data-active="{{ $nilai === 'all' ? 'true' : 'false' }}"
                            aria-pressed="{{ $nilai === 'all' ? 'true' : 'false' }}"
                            class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-slate-600 hover:bg-blue-50 data-[active=true]:bg-blue-700 data-[active=true]:text-white"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="mt-3">
                    <label class="relative block">
                        <span class="sr-only">
                            Cari transaksi, anggota, judul buku, atau petugas
                        </span>

                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                            @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                        </span>

                        <input
                            type="search"
                            data-search="#tabel-transaksi"
                            placeholder="Cari ID transaksi, nama anggota, judul buku..."
                            class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                        >
                    </label>
                </div>

                <p class="mt-3 text-xs text-slate-500">
                    Data diurutkan berdasarkan tanggal transaksi terbaru.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                <table id="tabel-transaksi" data-transactions='@json($trx)' class="w-full min-w-[56rem] text-left text-sm">    

                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID transaksi &amp; ref</th>
                                <th scope="col" class="px-3 py-3">Jenis &amp; tanggal</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Buku terkait</th>
                                <th scope="col" class="px-3 py-3">Petugas</th>
                                <th scope="col" class="px-3 py-3 pr-5">Denda / nilai</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($trx as $i => $t)
                                <tr
                                    class="cursor-pointer align-top hover:bg-[#F7F9FD]"
                                    data-cat="{{ $t['cat'] }}"
                                    data-inspect="{{ $i }}"
                                    tabindex="0"
                                    aria-label="Lihat transaksi {{ $t['id'] }}"
                                >
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-blue-700">
                                            {{ $t['id'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            Ref: {{ $t['ref'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $jenisKelas[$t['jenis']] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $t['jenis'] }}
                                        </span>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ $t['tanggal'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-semibold">
                                            {{ $t['nama'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ID Anggota: {{ $t['id_anggota'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="max-w-56 leading-snug">
                                            {{ $t['judul'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            ID: {{ $t['buku'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4">
                                        <p class="font-medium">
                                            {{ $t['petugas'] }}
                                        </p>

                                        <p class="text-xs text-slate-500">
                                            {{ $t['kode'] }}
                                        </p>
                                    </td>

                                    <td class="px-3 py-4 pr-5">
                                        <p class="font-bold {{ $t['nilai'] > 0 ? 'text-red-600' : 'text-slate-700' }}">
                                            {{ $rp($t['nilai']) }}
                                        </p>

                                        <p class="max-w-44 text-xs text-slate-500">
                                            {{ $t['ket'] }}
                                        </p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-blue-50 text-blue-700">
                                            @include('petugas.partials.icon', ['name' => 'history', 'class' => 'size-6'])
                                        </div>

                                        <p class="mt-3 font-semibold text-slate-700">
                                            Belum ada riwayat transaksi
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Transaksi akan muncul setelah peminjaman atau pengembalian tercatat di database.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                    Menampilkan {{ $trx->count() }} transaksi dari database.
                </div>
            </div>
        </section>

        {{-- Panel inspeksi --}}
        <aside class="min-w-0 space-y-6" aria-label="Inspeksi dan aktivitas petugas">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-inspeksi">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-inspeksi" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-5 text-blue-700'])
                        Inspeksi Transaksi
                    </h2>

                    <span id="in-id" class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700">
                        -
                    </span>
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Klik salah satu baris pada tabel untuk melihat rinciannya.
                </p>

                <div class="mt-4 rounded-xl bg-[#EEF3FC] p-4 text-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Anggota
                    </p>

                    <p id="in-nama" class="font-bold">
                        Belum ada transaksi dipilih
                    </p>

                    <p id="in-nim" class="text-xs text-slate-500">
                        -
                    </p>

                    <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Buku terkait
                    </p>

                    <p id="in-judul" class="font-semibold">
                        -
                    </p>

                    <p id="in-buku" class="text-xs text-slate-500">
                        -
                    </p>
                </div>

                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Jenis transaksi</dt>
                        <dd id="in-jenis" class="text-right font-semibold">-</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Tanggal</dt>
                        <dd id="in-waktu" class="text-right font-semibold">-</dd>
                    </div>

                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Keterangan</dt>
                        <dd id="in-ket" class="max-w-36 text-right font-semibold">-</dd>
                    </div>
                </dl>

                <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm">
                    <div class="flex items-center justify-between gap-3 text-xs">
                        <span class="font-semibold text-red-700">
                            Nominal denda
                        </span>

                        <b id="in-nilai" class="text-lg text-red-700">
                            Rp 0
                        </b>
                    </div>
                </div>

                <p class="mt-4 rounded-xl bg-[#F3F5FA] p-3 text-xs text-slate-600">
                    Petugas terkait:
                    <b id="in-petugas" class="text-[#0F1B3D]">-</b>
                    <span id="in-kode"></span>
                </p>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-aktivitas">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-aktivitas" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-5 text-blue-700'])
                        Aktivitas Petugas
                    </h2>
                </div>

                <div class="mt-4 rounded-xl bg-[#EEF3FC] p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Petugas login
                    </p>

                    <p class="mt-1 font-bold">
                        {{ $namaPetugas }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        ID Petugas: {{ $petugasLogin->id_petugas ?? '-' }}
                    </p>
                </div>

                <div class="mt-3 rounded-xl border border-slate-100 p-4">
                    <p class="text-xs text-slate-500">
                        Jumlah transaksi yang tercatat atas petugas ini
                    </p>

                    <p class="mt-1 text-2xl font-extrabold text-blue-700">
                        {{ $jumlahAktivitasPetugas }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        Gabungan transaksi peminjaman dan pengembalian.
                    </p>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-slate-500">
                    Informasi aktivitas diambil dari ID petugas yang tersimpan pada transaksi. Halaman ini belum menggunakan tabel audit log terpisah.
                </p>
            </section>
        </aside>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('tabel-transaksi');
            const data = JSON.parse(table.dataset.transactions || '[]');

            const rupiah = (nominal) => {
                return 'Rp ' + Number(nominal || 0).toLocaleString('id-ID');
            };

            const setText = (id, value) => {
                const element = document.getElementById(id);

                if (element) {
                    element.textContent = value ?? '-';
                }
            };

            const tampilkan = (index) => {
                const transaksi = data[index];

                if (!transaksi) {
                    return;
                }

                setText('in-id', transaksi.id);
                setText('in-nama', transaksi.nama);
                setText('in-nim', 'ID Anggota: ' + transaksi.id_anggota);
                setText('in-judul', transaksi.judul);
                setText('in-buku', 'ID: ' + transaksi.buku);
                setText('in-jenis', transaksi.jenis);
                setText('in-waktu', transaksi.waktu);
                setText('in-ket', transaksi.ket);
                setText('in-nilai', rupiah(transaksi.nilai));
                setText('in-petugas', transaksi.petugas);
                setText('in-kode', '(' + transaksi.kode + ')');

                document.querySelectorAll('#tabel-transaksi tbody tr[data-inspect]').forEach((row) => {
                    row.classList.toggle(
                        'bg-blue-50/60',
                        row.dataset.inspect === String(index)
                    );
                });
            };

            document.querySelectorAll('#tabel-transaksi tbody tr[data-inspect]').forEach((row) => {
                row.addEventListener('click', () => {
                    tampilkan(Number(row.dataset.inspect));
                });

                row.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        tampilkan(Number(row.dataset.inspect));
                    }
                });
            });

            if (data.length > 0) {
                tampilkan(0);
            }
        });
    </script>
@endsection
