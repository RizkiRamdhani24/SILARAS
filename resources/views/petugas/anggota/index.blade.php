@extends('petugas.layout')

@section('title', 'Kelola Anggota')

@php
    use App\Models\Anggota;
    use App\Models\Peminjaman;
    use Illuminate\Support\Facades\Schema;

    $tabelAnggotaAda = Schema::hasTable('anggota');
    $tabelPeminjamanAda = Schema::hasTable('peminjaman');

    $anggota = $tabelAnggotaAda
        ? Anggota::query()->orderBy('nama_anggota')->get()->map(function ($member) use ($tabelPeminjamanAda) {
            $jumlahPinjaman = 0;

            if ($tabelPeminjamanAda) {
                $jumlahPinjaman = Peminjaman::query()
                    ->where('id_anggota', $member->id_anggota)
                    ->whereIn('status_peminjaman', [
                        Peminjaman::STATUS_DIPINJAM,
                        Peminjaman::STATUS_TERLAMBAT,
                        Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
                    ])
                    ->count();
            }

            $nonaktif = $member->status_anggota === Anggota::STATUS_NONAKTIF;

            return [
                'id' => '#AGT-' . str_pad((string) $member->id_anggota, 3, '0', STR_PAD_LEFT),
                'induk' => 'ID Anggota: ' . $member->id_anggota,
                'nama' => $member->nama_anggota,
                'email' => $member->email_anggota ?? '-',
                'telp' => $member->no_telp ?? '-',
                'pinjam' => $jumlahPinjaman,
                'status' => $nonaktif ? 'Ditangguhkan' : 'Aktif',
                'cat' => $nonaktif ? 'nonaktif' : 'aktif',
            ];
        })->all()
        : [];

    $totalAnggota = count($anggota);
    $totalAktif = collect($anggota)->where('status', 'Aktif')->count();
    $totalNonaktif = collect($anggota)->where('status', 'Ditangguhkan')->count();

    $totalPinjamanBerjalan = $tabelPeminjamanAda
        ? Peminjaman::query()
            ->whereIn('status_peminjaman', [
                Peminjaman::STATUS_DIPINJAM,
                Peminjaman::STATUS_TERLAMBAT,
                Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
            ])
            ->count()
        : 0;

    $bar = [
        'aktif' => [
            0 => 'w-0',
            1 => 'w-1/3',
            2 => 'w-2/3',
            3 => 'w-full',
        ],
        'nonaktif' => [
            0 => 'w-0',
            1 => 'w-1/3',
            2 => 'w-2/3',
            3 => 'w-full',
        ],
    ];

    $pil = [
        'Aktif' => 'bg-emerald-100 text-emerald-700',
        'Ditangguhkan' => 'bg-red-100 text-red-700',
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                Kelola Data Anggota Perpustakaan
            </h1>
            <p class="mt-1 text-sm text-slate-600">
                Kelola informasi anggota serta pantau status akun dan aktivitas peminjaman buku.
            </p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan anggota">
        @include('petugas.partials.stat-card', [
            'label' => 'Total anggota',
            'value' => number_format($totalAnggota, 0, ',', '.'),
            'icon' => 'users',
            'tone' => 'blue',
            'footer' => 'Jumlah anggota yang terdaftar',
            'footerTone' => 'text-blue-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Pinjaman berjalan',
            'value' => number_format($totalPinjamanBerjalan, 0, ',', '.'),
            'icon' => 'book',
            'tone' => 'blue',
            'footer' => 'Buku yang sedang dipinjam anggota',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Anggota aktif',
            'value' => number_format($totalAktif, 0, ',', '.'),
            'icon' => 'user',
            'tone' => 'blue',
            'footer' => 'Akun dengan status aktif',
            'footerTone' => 'text-emerald-700',
        ])

        @include('petugas.partials.stat-card', [
            'label' => 'Anggota nonaktif',
            'value' => number_format($totalNonaktif, 0, ',', '.'),
            'icon' => 'user',
            'tone' => 'amber',
            'valueTone' => 'text-red-600',
            'footer' => 'Akun yang sedang dinonaktifkan',
            'footerTone' => 'text-red-600',
        ])
    </section>

    <section class="mt-6 space-y-4" aria-labelledby="judul-tabel">
        <h2 id="judul-tabel" class="sr-only">Daftar anggota</h2>

        <div class="flex flex-wrap items-center gap-3 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div data-tabs="#tabel-anggota" class="flex flex-wrap gap-2 text-sm font-semibold">
                @foreach ([
                    'all' => 'Semua (' . $totalAnggota . ')',
                    'aktif' => 'Aktif (' . $totalAktif . ')',
                    'nonaktif' => 'Nonaktif (' . $totalNonaktif . ')',
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

            <label class="relative min-w-56 flex-1">
                <span class="sr-only">Cari nama anggota, ID, atau email</span>
                <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                    @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
                </span>
                <input
                    type="search"
                    data-search="#tabel-anggota"
                    placeholder="Cari nama anggota, ID, atau email..."
                    class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none"
                >
            </label>
        </div>

        <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
            <div class="overflow-x-auto">
                <table id="tabel-anggota" class="w-full min-w-[56rem] text-left text-sm">
                    <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">ID Anggota</th>
                            <th scope="col" class="px-3 py-3">Nama anggota</th>
                            <th scope="col" class="px-3 py-3">Kontak</th>
                            <th scope="col" class="px-3 py-3">Pinjaman berjalan</th>
                            <th scope="col" class="px-3 py-3">Status anggota</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($anggota as $a)
                            @php
                                $inisial = collect(explode(' ', $a['nama']))
                                    ->filter()
                                    ->take(2)
                                    ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                                    ->implode('');

                                $jumlahBar = min(3, max(0, (int) $a['pinjam']));
                            @endphp

                            <tr
                                @class([
                                    'align-middle',
                                    'bg-red-50/50' => $a['status'] === 'Ditangguhkan',
                                ])
                                data-cat="{{ $a['cat'] }}"
                            >
                                <td class="px-5 py-4">
                                    <p class="font-bold text-blue-700">{{ $a['id'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $a['induk'] }}</p>
                                </td>

                                <td class="px-3 py-4">
                                    <div class="flex items-center gap-3">
                                        <span
                                            @class([
                                                'grid size-10 shrink-0 place-items-center rounded-full text-xs font-bold',
                                                'bg-red-100 text-red-700' => $a['status'] === 'Ditangguhkan',
                                                'bg-blue-100 text-blue-700' => $a['status'] !== 'Ditangguhkan',
                                            ])
                                        >
                                            {{ $inisial ?: '?' }}
                                        </span>

                                        <div>
                                            <p class="font-semibold">{{ $a['nama'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $a['email'] }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-4">
                                    <p>{{ $a['email'] }}</p>
                                    <p class="text-xs text-slate-500">{{ $a['telp'] }}</p>
                                </td>

                                <td class="px-3 py-4">
                                    <span class="font-bold">{{ $a['pinjam'] }} / 3 buku</span>

                                    <div
                                        class="mt-1.5 h-1.5 w-40 overflow-hidden rounded-full bg-slate-100"
                                        role="img"
                                        aria-label="Pinjaman berjalan {{ $a['pinjam'] }} buku"
                                    >
                                        <div
                                            class="h-full rounded-full {{ $bar[$a['cat']][$jumlahBar] }}"
                                        ></div>
                                    </div>
                                </td>

                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $pil[$a['status']] }}">
                                        <span class="size-1.5 rounded-full bg-current"></span>
                                        {{ $a['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-2 text-slate-500">
                                        @include('petugas.partials.icon', ['name' => 'users', 'class' => 'size-8'])
                                        <p class="font-semibold">Belum ada data anggota</p>
                                        <p class="text-xs">Data anggota yang terdaftar akan ditampilkan di sini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
                Menampilkan {{ $totalAnggota }} anggota terdaftar.
            </div>
        </div>

        <aside class="flex gap-3 rounded-2xl bg-amber-50 p-5 text-sm" aria-label="Informasi pengelolaan anggota">
            @include('petugas.partials.icon', ['name' => 'shield', 'class' => 'size-6 text-amber-700'])

            <div>
                <p class="font-bold">Informasi status anggota</p>
                <p class="mt-1 text-slate-600">
                    Anggota dengan status nonaktif ditampilkan sebagai ditangguhkan.
                    Jumlah pinjaman berjalan dihitung dari transaksi yang berstatus dipinjam,
                    terlambat, atau pengembalian diajukan.
                </p>
            </div>
        </aside>
    </section>
@endsection
