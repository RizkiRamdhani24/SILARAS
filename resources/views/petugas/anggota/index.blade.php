@extends('petugas.layout')

@section('title', 'Kelola Anggota')

@php
    use App\Models\Anggota;
    use App\Models\Peminjaman;
    use App\Support\PetugasData as D;

    // Kolom tabel anggota: nama_anggota, email_anggota, no_telp, status_anggota ("aktif"/"nonaktif").
    // Pinjaman aktif dihitung dari tabel peminjaman; tunggakan dari pengembalian berstatus_denda "belum dibayar".
    $tunggakan = D::tunggakanPerAnggota();
    $idBertunggakan = array_keys($tunggakan);
    $pinjamAktif = Peminjaman::whereIn('status_peminjaman', [Peminjaman::STATUS_DIPINJAM, Peminjaman::STATUS_TERLAMBAT])
        ->selectRaw('id_anggota, COUNT(*) as total')->groupBy('id_anggota')->pluck('total', 'id_anggota');

    $filter = in_array(request('filter'), ['bebas', 'tunggakan', 'nonaktif'], true) ? request('filter') : 'semua';
    $kata = mb_strtolower(trim((string) request('q', '')));

    $daftar = Anggota::query()
        ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w
            ->whereRaw('LOWER(nama_anggota) LIKE ?', ["%{$kata}%"])
            ->orWhereRaw('LOWER(email_anggota) LIKE ?', ["%{$kata}%"])))
        ->when($filter === 'bebas', fn ($q) => $q->where('status_anggota', Anggota::STATUS_AKTIF)->whereNotIn('id_anggota', $idBertunggakan))
        ->when($filter === 'tunggakan', fn ($q) => $q->whereIn('id_anggota', $idBertunggakan))
        ->when($filter === 'nonaktif', fn ($q) => $q->where('status_anggota', '!=', Anggota::STATUS_AKTIF))
        ->orderBy('nama_anggota')->paginate(15)->withQueryString();

    $total = Anggota::count();
    $jumlahNonaktif = Anggota::where('status_anggota', '!=', Anggota::STATUS_AKTIF)->count();
    $jumlahTunggakan = count($idBertunggakan);
    $jumlahBebas = Anggota::where('status_anggota', Anggota::STATUS_AKTIF)->whereNotIn('id_anggota', $idBertunggakan)->count();

    $tab = [
        ['semua', 'Semua', $total],
        ['bebas', 'Bebas Denda', $jumlahBebas],
        ['tunggakan', 'Ada Tunggakan', $jumlahTunggakan],
        ['nonaktif', 'Nonaktif', $jumlahNonaktif],
    ];
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Kelola Data Anggota Perpustakaan</h1>
            <p class="mt-1 text-sm text-slate-600">Pantau akun anggota, jumlah pinjaman yang sedang berjalan, dan tunggakan denda.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan anggota">
        @include('petugas.partials.stat-card', ['label' => 'Total terdaftar', 'value' => number_format($total, 0, ',', '.'), 'icon' => 'users', 'tone' => 'blue', 'footer' => ($total - $jumlahNonaktif).' akun aktif'])
        @include('petugas.partials.stat-card', ['label' => 'Pinjaman berjalan', 'value' => number_format(D::sedangDipinjam(), 0, ',', '.'), 'icon' => 'book', 'tone' => 'blue', 'footer' => 'Buku sedang dipinjam anggota'])
        @include('petugas.partials.stat-card', ['label' => 'Dengan tunggakan denda', 'value' => $jumlahTunggakan, 'icon' => 'cash', 'tone' => 'red', 'labelTone' => 'text-red-600', 'footer' => 'Total '.D::rupiah(array_sum($tunggakan)), 'footerTone' => 'text-red-600'])
        @include('petugas.partials.stat-card', ['label' => 'Pendaftaran bulan ini', 'value' => D::anggotaBaruBulanIni(), 'icon' => 'user', 'tone' => 'amber', 'footer' => 'Anggota baru sejak tanggal 1'])
    </section>

    <section class="mt-6 rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-label="Filter anggota">
        <div class="flex flex-wrap items-center gap-3">
            <nav class="flex flex-wrap gap-2 text-sm font-semibold" aria-label="Status anggota">
                @foreach ($tab as [$kode, $label, $jumlah])
                    <a href="{{ request()->fullUrlWithQuery(['filter' => $kode === 'semua' ? null : $kode, 'page' => null]) }}"
                       @if ($filter === $kode) aria-current="page" @endif
                       @class([
                           'rounded-xl px-4 py-2.5',
                           'bg-blue-700 text-white' => $filter === $kode,
                           'bg-[#EEF1F8] text-slate-600 hover:bg-blue-50' => $filter !== $kode,
                       ])>{{ $label }} ({{ number_format($jumlah, 0, ',', '.') }})</a>
                @endforeach
            </nav>
            <form method="GET" action="{{ url()->current() }}" class="relative ml-auto min-w-52 flex-1 sm:max-w-sm">
                @if ($filter !== 'semua') <input type="hidden" name="filter" value="{{ $filter }}"> @endif
                <label>
                    <span class="sr-only">Cari nama atau email anggota</span>
                    <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email anggota..."
                           class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                </label>
            </form>
        </div>
    </section>

    <section class="mt-4 overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-label="Daftar anggota">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-sm">
                <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3">ID</th>
                        <th scope="col" class="px-3 py-3">Nama anggota</th>
                        <th scope="col" class="px-3 py-3">Kontak</th>
                        <th scope="col" class="px-3 py-3">Pinjaman aktif</th>
                        <th scope="col" class="px-3 py-3">Tunggakan denda</th>
                        <th scope="col" class="px-3 py-3 pr-5">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $a)
                        @php
                            $denda = $tunggakan[$a->id_anggota] ?? 0;
                            $aktif = $a->status_anggota === Anggota::STATUS_AKTIF;
                        @endphp
                        <tr @class(['align-middle', 'bg-red-50/50' => ! $aktif])>
                            <td class="px-5 py-4 font-bold text-blue-700">{{ D::kode('AGT', $a->id_anggota) }}</td>
                            <td class="px-3 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $aktif ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' }} text-xs font-bold">{{ D::inisial($a->nama_anggota) }}</span>
                                    <p class="font-semibold">{{ $a->nama_anggota }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-4">
                                <p>{{ $a->email_anggota }}</p>
                                <p class="text-xs text-slate-500">{{ $a->no_telp ?: 'No. telepon belum diisi' }}</p>
                            </td>
                            <td class="px-3 py-4"><b>{{ $pinjamAktif[$a->id_anggota] ?? 0 }}</b> <span class="text-xs text-slate-500">buku</span></td>
                            <td class="px-3 py-4">
                                @if ($denda > 0)
                                    <span class="font-semibold text-red-600">{{ D::rupiah($denda) }}</span>
                                @else
                                    <span class="text-xs text-emerald-700">Bebas denda</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 pr-5">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-700' => $aktif,
                                    'bg-red-100 text-red-700' => ! $aktif,
                                ])><span class="size-1.5 rounded-full {{ $aktif ? 'bg-emerald-600' : 'bg-red-600' }}"></span>{{ $aktif ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">
                            @if ($kata !== '' || $filter !== 'semua') Tidak ada anggota yang cocok dengan filter ini. @else Belum ada anggota terdaftar. @endif
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($daftar->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $daftar->links() }}</div>
        @endif
    </section>
@endsection
