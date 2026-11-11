@php
    $menu = [
        ['label' => 'Dashboard', 'path' => 'petugas/dashboard', 'icon' => 'dashboard', 'badge' => null],
        ['label' => 'Kelola Buku', 'path' => 'petugas/buku', 'icon' => 'book', 'badge' => null],
        ['label' => 'Kelola Anggota', 'path' => 'petugas/anggota', 'icon' => 'users', 'badge' => null],
        ['label' => 'Peminjaman', 'path' => 'petugas/peminjaman', 'icon' => 'borrow', 'badge' => $antreanPinjam],
        ['label' => 'Pengembalian', 'path' => 'petugas/pengembalian', 'icon' => 'return', 'badge' => null],
        ['label' => 'Denda', 'path' => 'petugas/denda', 'icon' => 'cash', 'badge' => null],
        ['label' => 'Riwayat/Transaksi', 'path' => 'petugas/transaksi', 'icon' => 'history', 'badge' => null],
        ['label' => 'Profil Saya', 'path' => 'petugas/profil', 'icon' => 'person', 'badge' => null],
    ];
@endphp

<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-900/40 lg:hidden" aria-hidden="true"></div>

<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col bg-white shadow-xl transition-transform duration-200 motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:border-r lg:border-slate-100 lg:shadow-none"
       aria-label="Menu petugas">

    <a href="{{ url('/petugas/dashboard') }}" class="flex items-center gap-3 px-6 py-5">
        {{-- Ganti ikon ini dengan logo SILARAS dari Figma: <img src="{{ asset('images/logo-silaras.png') }}" alt="SILARAS" class="h-10"> --}}
        <span class="grid size-10 place-items-center rounded-xl bg-blue-700 text-white">
            @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-6'])
        </span>
        <span class="leading-tight">
            <span class="block text-base font-extrabold">SILARAS</span>
            <span class="block text-xs text-slate-500">Sistem Layanan Pustaka Cerdas</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto px-4" aria-label="Menu layanan">
        <p class="px-3 pb-2 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Menu Layanan</p>
        <ul class="space-y-1">
            @foreach ($menu as $item)
                @php $aktif = request()->is($item['path'].'*'); @endphp
                <li>
                    <a href="{{ url('/'.$item['path']) }}"
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
            <a href="{{ route('petugas.profile') }}" aria-label="Buka profil {{ $namaPetugas }}" class="grid size-10 shrink-0 place-items-center rounded-full bg-blue-700 text-sm font-bold text-white">{{ $inisial }}</a>
            <a href="{{ route('petugas.profile') }}" class="min-w-0 flex-1 truncate text-sm font-semibold hover:text-blue-700">{{ $namaPetugas }}</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="grid size-9 place-items-center rounded-lg text-slate-500 hover:bg-white hover:text-red-600" aria-label="Keluar">
                    @include('petugas.partials.icon', ['name' => 'logout'])
                </button>
            </form>
        </div>
    </div>
</aside>
