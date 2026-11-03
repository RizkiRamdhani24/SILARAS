{{-- COMPONENT: Header anggota --}}
<header class="member-header">
    <a class="member-brand" href="{{ route('home') }}" aria-label="SILARAS, kembali ke beranda">
        <img src="{{ asset('images/silaras-logo.png') }}" alt="" width="42" height="38">
        <span><strong>SILARAS</strong><small>Sistem Layanan Pustaka Cerdas</small></span>
    </a>

    <div class="member-header-main">
        <button class="member-menu-toggle" type="button" aria-label="Buka navigasi anggota" aria-controls="member-sidebar" aria-expanded="false" data-member-menu-toggle>
            <span class="material-symbols-rounded member-icon" aria-hidden="true">menu</span>
        </button>
        <p class="member-date">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">calendar_month</span>
            {{ now()->locale('id')->translatedFormat('l, j F Y') }}
        </p>
        <form class="member-search" action="{{ route('anggota.katalog') }}" method="GET" role="search" data-member-search>
            <span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>
            <label class="sr-only" for="member-search">Cari buku</label>
            <input id="member-search" type="search" name="q" value="{{ request()->routeIs('anggota.katalog', 'anggota.buku.*') ? request('q', '') : '' }}" placeholder="Cari Judul, Penulis, Kategori..">
        </form>
        @auth('anggota')
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="member-logout-top" type="submit">Keluar</button>
            </form>
        @endauth
        <button class="member-icon-button" type="button" aria-label="Notifikasi" disabled>
            <span class="material-symbols-rounded member-icon" aria-hidden="true">notifications</span>
        </button>
        <span class="member-avatar" aria-hidden="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">person</span></span>
    </div>
</header>
