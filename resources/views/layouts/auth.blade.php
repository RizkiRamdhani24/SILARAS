<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SILARAS')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="silaras-auth-shell @if (request()->routeIs('home')) silaras-landing-shell @endif">
    @include('partials.auth-navbar')

    <main class="silaras-auth-main @if (request()->routeIs('home')) silaras-landing-main @endif">
        @yield('content')
    </main>

    @include('partials.auth-footer')
</body>
</html>
