@php
    use App\Support\PetugasData;

    // "pola" = alamat yang membuat menu tampak aktif (rute modul Exel/Herlyan, rute web.php, dan pratinjau lokal).
    $menu = [
        ['label' => 'Dashboard', 'halaman' => 'dashboard', 'pola' => ['petugas/dashboard*', 'dev/petugas/dashboard*'], 'icon' => 'dashboard', 'badge' => null],
        ['label' => 'Kelola Buku', 'halaman' => 'buku', 'pola' => ['petugas/buku*', 'petugas/kelola-buku*', 'dev/petugas/buku*'], 'icon' => 'book', 'badge' => null],
        ['label' => 'Kelola Anggota', 'halaman' => 'anggota', 'pola' => ['petugas/anggota*', 'dev/petugas/anggota*'], 'icon' => 'users', 'badge' => null],
        ['label' => 'Peminjaman', 'halaman' => 'peminjaman', 'pola' => ['petugas/peminjaman*', 'dev/petugas/peminjaman*'], 'icon' => 'borrow', 'badge' => $antreanPinjam ?: null],
        ['label' => 'Pengembalian', 'halaman' => 'pengembalian', 'pola' => ['petugas/pengembalian*', 'dev/petugas/pengembalian*'], 'icon' => 'return', 'badge' => null],
        ['label' => 'Denda', 'halaman' => 'denda', 'pola' => ['petugas/denda*', 'dev/petugas/denda*'], 'icon' => 'cash', 'badge' => null],
        ['label' => 'Riwayat/Transaksi', 'halaman' => 'transaksi', 'pola' => ['petugas/transaksi*', 'dev/petugas/transaksi*'], 'icon' => 'history', 'badge' => null],
    ];
@endphp

<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden" aria-hidden="true"></div>

<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-white shadow-xl transition-transform duration-200 motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:border-r lg:border-slate-100 lg:shadow-none"
       aria-label="Menu petugas">

    <a href="{{ PetugasData::link('dashboard') }}" class="flex items-center gap-3 px-6 py-5">
        <img src="{{ asset('images/silaras-logo.png') }}" alt="" width="40" height="40" class="size-10 shrink-0 object-contain">
        <span class="leading-tight">
            <span class="block text-base font-extrabold">SILARAS</span>
            <span class="block text-xs text-slate-500">Sistem Layanan Pustaka Cerdas</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-4" aria-label="Menu layanan">
        <p class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Menu Layanan</p>
        <ul class="space-y-1">
            @foreach ($menu as $item)
                @php $aktif = request()->is(...$item['pola']); @endphp
                <li>
                    <a href="{{ PetugasData::link($item['halaman']) }}"
                       @if ($aktif) aria-current="page" @endif
                       @class([
                           'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition motion-reduce:transition-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-700',
                           'bg-blue-700 text-white shadow-sm' => $aktif,
                           'text-slate-600 hover:bg-slate-50 hover:text-[#0F1B3D]' => ! $aktif,
                       ])>
                        @include('petugas.partials.icon', ['name' => $item['icon']])
                        <span class="flex-1">{{ $item['label'] }}</span>
                        @if ($item['badge'])
                            <span class="grid min-w-6 place-items-center rounded-full bg-red-600 px-1.5 py-0.5 text-xs font-bold text-white">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="m-4 rounded-2xl bg-[#EEF3FC] p-3">
        <div class="flex items-center gap-3">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-blue-700 text-sm font-bold text-white">{{ $inisial }}</span>
            <p class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $namaPetugas }}</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-white hover:text-red-600" aria-label="Keluar">
                    @include('petugas.partials.icon', ['name' => 'logout'])
                </button>
            </form>
        </div>
        <p class="mt-3 flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-medium text-slate-600">
            <span class="size-2 rounded-full bg-blue-600"></span>
            @if ($shiftPetugas) Shift {{ $shiftPetugas }} @else Petugas perpustakaan @endif
        </p>
    </div>
</aside>
