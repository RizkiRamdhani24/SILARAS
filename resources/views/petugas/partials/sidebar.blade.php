@php
    $menu = [
        ['label' => 'Dashboard', 'path' => 'petugas/dashboard', 'icon' => 'dashboard'],
        ['label' => 'Kelola Buku', 'path' => 'petugas/buku', 'icon' => 'book'],
        ['label' => 'Kelola Anggota', 'path' => 'petugas/anggota', 'icon' => 'users'],
        ['label' => 'Peminjaman', 'path' => 'petugas/peminjaman', 'icon' => 'borrow', 'badge' => $antreanPinjam],
        ['label' => 'Pengembalian', 'path' => 'petugas/pengembalian', 'icon' => 'return'],
        ['label' => 'Denda', 'path' => 'petugas/denda', 'icon' => 'cash'],
        ['label' => 'Riwayat/Transaksi', 'path' => 'petugas/transaksi', 'icon' => 'history'],
        ['label' => 'Profil Saya', 'path' => 'petugas/profil', 'icon' => 'user'],
    ];
@endphp

<button id="sidebar-overlay" class="petugas-sidebar-backdrop" type="button" aria-label="Tutup menu petugas" aria-hidden="true" tabindex="-1"></button>

<aside id="sidebar" class="petugas-sidebar" aria-label="Navigasi petugas" aria-hidden="false">
    <div class="petugas-sidebar-brand-row">
        <a class="petugas-sidebar-brand" href="{{ route('home') }}" aria-label="SILARAS, kembali ke beranda">
            <img src="{{ asset('images/silaras-logo.png') }}" alt="" width="42" height="38">
            <span><strong>SILARAS</strong><small>Sistem Layanan Pustaka Cerdas</small></span>
        </a>
        <button class="petugas-sidebar-close" type="button" aria-label="Tutup menu petugas" data-sidebar-close>
            @include('petugas.partials.icon', ['name' => 'x'])
        </button>
    </div>

    <nav class="petugas-sidebar-nav" aria-label="Menu layanan petugas">
        <p>Menu Layanan</p>
        @foreach ($menu as $item)
            @php $aktif = request()->is($item['path'].'*'); @endphp
            <a href="{{ url('/'.$item['path']) }}"
               @if ($aktif) aria-current="page" @endif
               @class(['is-active' => $aktif])>
                @include('petugas.partials.icon', ['name' => $item['icon']])
                <span>{{ $item['label'] }}</span>
                @if (! empty($item['badge']) && $item['badge'] > 0)
                    <span class="petugas-sidebar-badge">{{ $item['badge'] }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="petugas-sidebar-bottom">
        <section class="petugas-sidebar-help">
            <h2>@include('petugas.partials.icon', ['name' => 'book']) Bantuan Layanan</h2>
            <p>Perlu bantuan mengelola layanan perpustakaan?</p>
            <a href="{{ route('home') }}#kontak">Hubungi Pustakawan</a>
        </section>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="petugas-sidebar-logout" type="submit">
                @include('petugas.partials.icon', ['name' => 'logout'])
                Keluar Portal
            </button>
        </form>
    </div>
</aside>
