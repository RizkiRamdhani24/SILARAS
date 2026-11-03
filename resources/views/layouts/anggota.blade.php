{{-- ===== LAYOUT: PORTAL ANGGOTA ===== --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard Anggota | SILARAS')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500|plus-jakarta-sans:400,500,600,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,400,0,0" rel="stylesheet">
    @vite(['resources/css/anggota/dashboard.css', 'resources/js/anggota/dashboard.js'])
    @stack('styles')
</head>
<body class="member-page @yield('page_class')">
    {{-- COMPONENT: Header --}}
    @include('anggota.partials.header')

    {{-- COMPONENT: Sidebar dan drawer backdrop --}}
    @include('anggota.partials.sidebar')
    <button class="member-menu-backdrop" type="button" tabindex="-1" aria-label="Tutup navigasi anggota" data-member-menu-backdrop></button>

    {{-- SECTION: Main content --}}
    <main class="member-main" id="main-content">
        @yield('content')

        {{-- COMPONENT: Footer --}}
        @include('anggota.partials.footer')
    </main>
</body>
</html>
