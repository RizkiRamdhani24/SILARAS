<header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-100 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" data-sidebar-toggle
            class="grid size-10 place-items-center rounded-xl text-slate-600 hover:bg-slate-100 lg:hidden"
            aria-label="Buka menu" aria-controls="sidebar">
        @include('petugas.partials.icon', ['name' => 'menu'])
    </button>

    <p class="flex items-center gap-2 text-sm font-medium">
        @include('petugas.partials.icon', ['name' => 'calendar', 'class' => 'size-5 text-blue-700'])
        <span>{{ now()->locale('id')->translatedFormat('l, j F Y') }}</span>
    </p>

    <div class="ml-auto flex items-center gap-3">
        <label class="relative hidden md:block">
            <span class="sr-only">Cari buku, anggota, atau nomor pinjam</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">
                @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])
            </span>
            <input type="search" placeholder="Cari buku, anggota, no. pinjam..."
                   class="w-72 rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm placeholder:text-slate-400 focus:ring-2 focus:ring-blue-600 focus:outline-none lg:w-96">
        </label>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100">
                <span class="size-2 rounded-full bg-red-600"></span>Logout
            </button>
        </form>

        <button type="button" class="relative grid size-10 place-items-center rounded-xl text-slate-600 hover:bg-slate-100" aria-label="Notifikasi">
            @include('petugas.partials.icon', ['name' => 'bell'])
            <span class="absolute right-2.5 top-2.5 size-2 rounded-full bg-red-600 ring-2 ring-white"></span>
        </button>

        <span class="grid size-10 place-items-center rounded-full bg-blue-700 text-sm font-bold text-white" title="{{ $namaPetugas }}">{{ $inisial }}</span>
    </div>
</header>
