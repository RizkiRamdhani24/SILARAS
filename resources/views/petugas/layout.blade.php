@php
    // Data petugas yang sedang login (guard "petugas").
    $petugas = auth('petugas')->user();
    $namaPetugas = $petugas->nama_petugas ?? 'Petugas';
    $inisial = collect(explode(' ', $namaPetugas))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $antreanPinjam = $antreanPinjam ?? (\Illuminate\Support\Facades\Schema::hasTable('peminjaman')
        ? \App\Models\Peminjaman::query()
            ->where('status_peminjaman', \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI)
            ->count()
        : 0);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Petugas') · SILARAS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="petugas-page bg-[#F4F6FB] text-[#0F1B3D] antialiased" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    <div class="min-h-screen lg:flex">
        @include('petugas.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('petugas.partials.topbar')

            <main id="konten" class="flex-1 px-4 pb-10 sm:px-6 lg:px-8">
                @yield('content')
            </main>

            <footer class="px-4 py-6 text-sm text-slate-500 sm:px-6 lg:px-8">
                © {{ date('Y') }} SILARAS - Pustaka Akademik Kampus. Hak cipta dilindungi.
            </footer>
        </div>
    </div>

    <div id="toast" class="silaras-toast" role="status" aria-live="polite" aria-hidden="true">
        <span class="silaras-toast-icon" aria-hidden="true"><span>info</span></span>
        <span class="silaras-toast-message"></span>
    </div>

    @include('petugas.partials.scripts')
    @stack('scripts')
</body>
</html>
