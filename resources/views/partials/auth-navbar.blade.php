<header class="silaras-auth-navbar">
    <a class="silaras-auth-brand" href="/" aria-label="SILARAS Beranda">
            <img class="silaras-auth-brand-logo" src="{{ asset('images/silaras-logo.png') }}" alt="Logo SILARAS">
        <span class="silaras-auth-brand-copy">
            <span class="silaras-auth-brand-name">
                SILARAS
                <span class="silaras-auth-brand-badge">CIVITAS</span>
            </span>
            <span class="silaras-auth-brand-tagline">Sistem Layanan Pustaka Cerdas</span>
        </span>
    </a>

    <div class="silaras-auth-navbar-actions">
        <nav class="silaras-auth-nav-links" aria-label="Navigasi utama">
            <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Beranda</a>
            @if (request()->routeIs('home'))
                <a href="{{ route('home') }}#koleksi">Katalog</a>
                <a href="{{ route('home') }}#kategori">Jenis Kategori</a>
                <a href="{{ route('home') }}#faq">FAQ</a>
                <a href="{{ route('home') }}#kontak">Kontak</a>
            @else
                <a href="{{ route('home') }}#koleksi">Katalog</a>
                <a href="{{ route('home') }}#kategori">Jenis Kategori</a>
            @endif
        </nav>

        @if (auth('anggota')->check())
            <a class="silaras-auth-nav-login" href="{{ route('anggota.dashboard') }}">Dashboard</a>
        @elseif (auth('petugas')->check())
            <a class="silaras-auth-nav-login" href="{{ route('petugas.dashboard') }}">Dashboard</a>
        @else
            <a class="silaras-auth-nav-login" href="{{ route('login') }}">Login</a>
        @endif
    </div>
</header>
