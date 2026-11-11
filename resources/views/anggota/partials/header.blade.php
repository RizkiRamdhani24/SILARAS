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
            {{ now()->format('l, j F Y') }}
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
        @auth('anggota')
            <details class="member-notification-menu">
                <summary class="member-icon-button" aria-label="Buka notifikasi" title="Notifikasi">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">notifications</span>
                    @if (($jumlahNotifikasiBelumDibaca ?? 0) > 0)
                        <span class="member-notification-count">{{ $jumlahNotifikasiBelumDibaca > 99 ? '99+' : $jumlahNotifikasiBelumDibaca }}</span>
                    @endif
                </summary>
                <div class="member-notification-popover">
                    <div class="member-notification-heading">
                        <strong>Notifikasi</strong>
                        <span>{{ $jumlahNotifikasiBelumDibaca ?? 0 }} belum dibaca</span>
                    </div>
                    @forelse ($notifikasiAnggotaTerbaru ?? [] as $notification)
                        <form method="POST" action="{{ route('anggota.notifikasi.buka', $notification->id_notifikasi) }}">
                            @csrf
                            <button class="member-notification-item {{ $notification->is_dibaca ? '' : 'is-unread' }}" type="submit">
                                <span class="member-notification-item-title">{{ $notification->judul }}</span>
                                <span class="member-notification-item-body">{{ $notification->isi }}</span>
                                <time datetime="{{ $notification->created_at?->toIso8601String() }}">{{ $notification->created_at?->diffForHumans() }}</time>
                            </button>
                        </form>
                    @empty
                        <p class="member-notification-empty">Belum ada notifikasi.</p>
                    @endforelse
                    <a class="member-notification-all" href="{{ route('anggota.notifikasi.index') }}">Lihat semua notifikasi</a>
                </div>
            </details>
        @endauth
        @auth('anggota')
            <a class="member-avatar" href="{{ route('anggota.profile') }}" aria-label="Buka profil anggota" title="Profil saya">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">person</span>
            </a>
        @else
            <span class="member-avatar" aria-hidden="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">person</span></span>
        @endauth
    </div>
</header>
