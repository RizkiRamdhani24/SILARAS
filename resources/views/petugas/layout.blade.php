@php
    // Data petugas yang sedang login (guard "petugas"), dari tabel petugas: nama_petugas, shift (boleh kosong).
    [$namaPetugas, $shiftPetugas] = \App\Support\PetugasData::identitasPetugas();
    $inisial = \App\Support\PetugasData::inisial($namaPetugas);
    $antreanPinjam = \App\Support\PetugasData::antreanPeminjaman();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Petugas') · SILARAS</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6FB] text-[#0F1B3D] antialiased" style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif">
    <a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">Lewati ke konten</a>

    <div class="min-h-screen lg:flex">
        @include('petugas.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('petugas.partials.topbar')

            <main id="konten" class="flex-1 px-4 pb-10 sm:px-6 lg:px-8">
                {{-- Pesan dari controller (redirect()->with('success'|'error', ...)) dan galat validasi form --}}
                @if (session('success'))
                    <p role="status" class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">{{ session('success') }}</p>
                @endif
                @if (session('error'))
                    <p role="alert" class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-200">{{ session('error') }}</p>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mt-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                        <p class="font-semibold">Data belum bisa disimpan:</p>
                        <ul class="mt-1 list-disc pl-5">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <footer class="px-4 py-6 text-sm text-slate-500 sm:px-6 lg:px-8">
                © {{ date('Y') }} SILARAS - Pustaka Akademik Kampus. Hak cipta dilindungi.
            </footer>
        </div>
    </div>

    <div id="toast" role="status" aria-live="polite"
         class="pointer-events-none fixed bottom-6 left-1/2 z-50 -translate-x-1/2 translate-y-2 rounded-xl bg-[#0F1B3D] px-4 py-3 text-sm font-medium text-white opacity-0 shadow-lg transition motion-reduce:transition-none"></div>

    @include('petugas.partials.scripts')
    @stack('scripts')
</body>
</html>
