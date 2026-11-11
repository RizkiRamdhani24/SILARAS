{{-- COMPONENT: Sidebar anggota dan drawer mobile --}}
<aside class="member-sidebar" id="member-sidebar" aria-label="Sidebar anggota" aria-hidden="false" tabindex="-1">
    <div class="member-sidebar-mobile-header">
        <a class="member-sidebar-brand" href="{{ route('home') }}" aria-label="SILARAS, kembali ke beranda" data-member-sidebar-brand>
            <img src="{{ asset('images/silaras-logo.png') }}" alt="" width="42" height="38">
            <span><strong>SILARAS</strong><small>Sistem Layanan Pustaka Cerdas</small></span>
        </a>
        <button class="member-sidebar-close" type="button" aria-label="Tutup navigasi anggota" data-member-menu-close>
            <span class="material-symbols-rounded member-icon" aria-hidden="true">close</span>
        </button>
    </div>
    <nav aria-label="Navigasi anggota">
        <a class="{{ request()->routeIs('anggota.dashboard') ? 'is-active' : '' }}" href="{{ route('anggota.dashboard') }}" @if(request()->routeIs('anggota.dashboard')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">dashboard</span>Dashboard</a>
        <a class="{{ request()->routeIs('anggota.katalog', 'anggota.buku.*') ? 'is-active' : '' }}" href="{{ route('anggota.katalog') }}" @if(request()->routeIs('anggota.katalog', 'anggota.buku.*')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Katalog Buku</a>
        <a class="{{ request()->routeIs('anggota.peminjaman.*') ? 'is-active' : '' }}" href="{{ route('anggota.peminjaman.index') }}" @if(request()->routeIs('anggota.peminjaman.*')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span>Peminjaman Buku</a>
        <a class="{{ request()->routeIs('anggota.riwayat') ? 'is-active' : '' }}" href="{{ route('anggota.riwayat') }}" @if(request()->routeIs('anggota.riwayat')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Riwayat Peminjaman</a>
        <a class="{{ request()->routeIs('anggota.pengembalian.*', 'anggota.denda.*') ? 'is-active' : '' }}" href="{{ route('anggota.pengembalian.index') }}" @if(request()->routeIs('anggota.pengembalian.*', 'anggota.denda.*')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>Pengembalian &amp; Denda</a>
        <a class="{{ request()->routeIs('anggota.profile') ? 'is-active' : '' }}" href="{{ route('anggota.profile') }}" @if(request()->routeIs('anggota.profile')) aria-current="page" @endif><span class="material-symbols-rounded member-icon" aria-hidden="true">person</span>Profil Saya</a>
    </nav>

    <div class="member-sidebar-bottom">
        <section class="sidebar-help">
            <h2><span class="material-symbols-rounded member-icon" aria-hidden="true">support_agent</span>Bantuan Layanan</h2>
            <p>Butuh asistensi pencarian rak fisik atau reservasi referensi?</p>
            <a href="{{ route('home') }}#kontak">Hubungi Pustakawan <span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span></a>
        </section>
        @auth('anggota')
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="sidebar-logout" type="submit"><span class="material-symbols-rounded member-icon" aria-hidden="true">logout</span>Keluar Portal</button>
            </form>
        @else
            <button class="sidebar-logout is-preview" type="button" disabled><span class="material-symbols-rounded member-icon" aria-hidden="true">logout</span>Keluar Portal</button>
        @endauth
    </div>
</aside>
