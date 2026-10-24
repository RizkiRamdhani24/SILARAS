@extends('layouts.auth')

@section('title', 'SILARAS | Pustaka Akademik Pintar & Cepat')

@section('content')
    <section class="silaras-landing-hero" aria-labelledby="landing-title">
        <div class="silaras-landing-status">
            <span aria-hidden="true"></span>
            SIPRUS V4.2 <b>•</b> TERHUBUNG REPOSITORI KAMPUS
        </div>
        <h1 id="landing-title">Akses Pustaka Akademik Pintar &amp; Cepat<br>Bersama <span>SILARAS</span></h1>
        <p class="silaras-landing-intro">
            Sistem Layanan Pustaka Cerdas generasi baru. Jelajahi koleksi buku yang tersedia
            dan temukan informasi pustaka dalam satu tempat.
        </p>

        <form class="silaras-landing-search" action="{{ route('home') }}" method="GET" role="search">
            <label class="silaras-landing-category-select">
                <span class="silaras-landing-search-icon" aria-hidden="true">▦</span>
                <span class="silaras-sr-only">Pilih kategori buku</span>
                <select name="kategori">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $item)
                        <option value="{{ $item->kategori }}" @selected($selectedCategory === $item->kategori)>
                            {{ $item->kategori }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="silaras-landing-query">
                <span class="silaras-landing-search-icon" aria-hidden="true">▤</span>
                <span class="silaras-sr-only">Cari judul, pengarang, penerbit, atau ISBN</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari Judul Buku, Pengarang, ISBN, atau Penerbit...">
            </label>
            <button class="silaras-landing-search-button" type="submit">
                <span aria-hidden="true">⌕</span> Cari Buku
            </button>
        </form>

        <div class="silaras-landing-hero-actions">
            <a href="{{ route('login') }}" class="silaras-landing-action silaras-landing-action-white">
                <span aria-hidden="true">✓</span> Cek Status Peminjaman
            </a>
            @if (auth('anggota')->check())
                <a href="{{ route('anggota.dashboard') }}" class="silaras-landing-action silaras-landing-action-blue">
                    <span aria-hidden="true">◉</span> Buka Dashboard Anggota
                </a>
            @elseif (auth('petugas')->check())
                <a href="{{ route('petugas.dashboard') }}" class="silaras-landing-action silaras-landing-action-blue">
                    <span aria-hidden="true">◉</span> Buka Dashboard Petugas
                </a>
            @else
                <a href="{{ route('register') }}" class="silaras-landing-action silaras-landing-action-blue">
                    <span aria-hidden="true">◉</span> Masuk / Pendaftaran Kartu Pustaka
                </a>
            @endif
        </div>
    </section>

    <section class="silaras-landing-stats" aria-label="Statistik koleksi perpustakaan">
        <article class="silaras-landing-stat-card">
            <span class="silaras-landing-stat-icon silaras-landing-stat-blue" aria-hidden="true">▣</span>
            <span><strong>{{ number_format($bookCount, 0, ',', '.') }}</strong><small>Judul Terindeks</small></span>
        </article>
        <article class="silaras-landing-stat-card">
            <span class="silaras-landing-stat-icon silaras-landing-stat-blue" aria-hidden="true">▤</span>
            <span><strong>{{ number_format($copyCount, 0, ',', '.') }}</strong><small>Eksemplar Tersedia</small></span>
        </article>
        <article class="silaras-landing-stat-card">
            <span class="silaras-landing-stat-icon silaras-landing-stat-orange" aria-hidden="true">♟</span>
            <span><strong>{{ number_format($memberCount, 0, ',', '.') }}</strong><small>Anggota Terdaftar</small></span>
        </article>
        <article class="silaras-landing-stat-card">
            <span class="silaras-landing-stat-icon silaras-landing-stat-blue" aria-hidden="true">✓</span>
            <span><strong>{{ number_format($circulationSuccess, 1, ',', '.') }}%</strong><small>Peminjaman Selesai</small></span>
        </article>
    </section>

    <section class="silaras-landing-section" id="koleksi" aria-labelledby="collection-title">
        <div class="silaras-landing-section-heading">
            <div>
                <span class="silaras-landing-kicker">KOLEKSI PERPUSTAKAAN</span>
                <h2 id="collection-title">{{ $search !== '' || $selectedCategory !== '' ? 'Hasil Pencarian Buku' : 'Jelajahi Koleksi Buku' }}</h2>
            </div>
            <a class="silaras-landing-text-link" href="#koleksi">Jelajahi Katalog <span aria-hidden="true">→</span></a>
        </div>

        @if ($books->isNotEmpty())
            <div class="silaras-landing-book-grid">
                @foreach ($books as $book)
                    <article class="silaras-landing-book-card">
                        <div class="silaras-landing-book-cover">
                            @if ($book->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul buku {{ $book->judul_buku }}">
                            @else
                                <div class="silaras-landing-cover-placeholder" aria-label="Sampul buku tidak tersedia">
                                    <span class="silaras-landing-cover-mark" aria-hidden="true">S</span>
                                    <span>{{ $book->kategori ?: 'KOLEKSI PUSTAKA' }}</span>
                                </div>
                            @endif
                            <span class="silaras-landing-stock {{ $book->stock > 0 ? 'is-available' : 'is-unavailable' }}">
                                {{ $book->stock > 0 ? 'Tersedia (' . $book->stock . ')' : 'Tidak tersedia' }}
                            </span>
                        </div>
                        <div class="silaras-landing-book-info">
                            @if ($book->rak)
                                <span class="silaras-landing-book-rack">RAK {{ $book->rak }}</span>
                            @endif
                            <h3>{{ $book->judul_buku }}</h3>
                            <p>{{ $book->pengarang }}</p>
                            @if ($book->isbn)
                                <div class="silaras-landing-book-meta">
                                    <span>ISBN {{ $book->isbn }}</span>
                                    <a href="{{ route('login') }}">Detail &amp; Pinjam</a>
                                </div>
                            @else
                                <div class="silaras-landing-book-meta">
                                    <span>{{ $book->kategori ?: 'Koleksi SILARAS' }}</span>
                                    <a href="{{ route('login') }}">Detail &amp; Pinjam</a>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="silaras-landing-empty">
                <span aria-hidden="true">▤</span>
                <p>
                    @if ($search !== '' || $selectedCategory !== '')
                        Belum ada buku yang cocok dengan pencarian ini.
                    @else
                        Koleksi buku belum tersedia untuk ditampilkan.
                    @endif
                </p>
            </div>
        @endif
    </section>

    <section class="silaras-landing-section silaras-landing-categories" id="kategori" aria-labelledby="categories-title">
        <div class="silaras-landing-section-heading">
            <div>
                <span class="silaras-landing-kicker">EKSPLORASI REPOSITORI</span>
                <h2 id="categories-title">Jelajahi Jenis Koleksi</h2>
                <p>Akses koleksi berdasarkan kategori yang tersedia di katalog SILARAS.</p>
            </div>
            <span class="silaras-landing-category-count">{{ $categories->count() }} kategori tersedia</span>
        </div>

        @if ($categories->isNotEmpty())
            <div class="silaras-landing-category-grid">
                @foreach ($categories as $item)
                    <a class="silaras-landing-category-card" href="{{ route('home', ['kategori' => $item->kategori]) }}">
                        <span class="silaras-landing-category-symbol" aria-hidden="true">▧</span>
                        <span class="silaras-landing-category-total">{{ number_format($item->jumlah, 0, ',', '.') }}</span>
                        <strong>{{ $item->kategori }}</strong>
                        <small>{{ number_format($item->jumlah, 0, ',', '.') }} judul dalam koleksi</small>
                    </a>
                @endforeach
            </div>
        @else
            <div class="silaras-landing-empty">
                <span aria-hidden="true">▧</span>
                <p>Kategori akan tampil setelah koleksi buku ditambahkan.</p>
            </div>
        @endif
    </section>

    <section class="silaras-landing-help" id="faq" aria-labelledby="faq-title">
        <div class="silaras-landing-help-intro">
            <span class="silaras-landing-kicker">PUSAT BANTUAN &amp; PANDUAN</span>
            <h2 id="faq-title">Pertanyaan yang Sering Diajukan</h2>
            <p>Temukan jawaban cepat terkait keanggotaan, alur peminjaman, dan penggunaan layanan SILARAS.</p>

            <article class="silaras-landing-librarian">
                <div class="silaras-landing-librarian-heading">
                    <span class="silaras-landing-librarian-icon" aria-hidden="true">◉</span>
                    <span><strong>Bantuan Pustakawan</strong><small>Silakan datang ke perpustakaan kampus</small></span>
                    <span class="silaras-landing-online"><i></i> Layanan</span>
                </div>
                <p>Butuh bantuan khusus atau panduan pencarian? Pustakawan siap membantu Anda di jam layanan perpustakaan.</p>
                <a href="#kontak" class="silaras-landing-small-button">Informasi Kontak</a>
                <a href="{{ route('register') }}" class="silaras-landing-small-button silaras-landing-small-button-plain">Panduan Anggota</a>
            </article>
        </div>

        <div class="silaras-landing-faq-list">
            <details open>
                <summary><span>PEMINJAMAN</span> Bagaimana alur peminjaman buku di perpustakaan?</summary>
                <p>Temukan buku melalui pencarian katalog, cek ketersediaan, lalu masuk sebagai anggota untuk melanjutkan proses peminjaman sesuai ketentuan perpustakaan.</p>
            </details>
            <details>
                <summary><span>MASA BERLAKU</span> Berapa lama batas waktu peminjaman buku?</summary>
                <p>Informasi batas waktu peminjaman dapat dilihat pada detail transaksi atau ditanyakan kepada petugas perpustakaan.</p>
            </details>
            <details>
                <summary><span>KETERSEDIAAN</span> Bagaimana jika buku yang dicari sedang dipinjam?</summary>
                <p>Periksa kembali katalog secara berkala atau tanyakan perkiraan ketersediaan kepada petugas perpustakaan.</p>
            </details>
            <details>
                <summary><span>AKSES DIGITAL</span> Apakah koleksi digital dapat diakses dari rumah?</summary>
                <p>Ketersediaan akses digital mengikuti koleksi dan kebijakan perpustakaan kampus.</p>
            </details>
            <details>
                <summary><span>SANKSI</span> Apakah ada denda keterlambatan pengembalian buku?</summary>
                <p>Ketentuan denda mengikuti kebijakan perpustakaan. Silakan hubungi petugas untuk informasi yang berlaku.</p>
            </details>
        </div>
    </section>

    <section class="silaras-landing-contact" id="kontak" aria-labelledby="contact-title">
        <div class="silaras-landing-contact-heading">
            <div>
                <span class="silaras-landing-kicker">KONTAK &amp; LOKASI KAMPUS</span>
                <h2 id="contact-title">Kunjungi &amp; Hubungi Kami</h2>
            </div>
            <p>Perpustakaan mendukung kegiatan akademik, penelitian, dan literasi civitas akademika.</p>
        </div>
        <div class="silaras-landing-contact-grid">
            <article class="silaras-landing-contact-card">
                <span class="silaras-landing-contact-icon" aria-hidden="true">⌖</span>
                <h3>Perpustakaan Kampus</h3>
                <small>Lokasi layanan pustaka</small>
                <p>Kunjungi perpustakaan kampus untuk mendapatkan layanan koleksi dan bantuan pustakawan secara langsung.</p>
                <a href="#faq">Lihat panduan layanan <span aria-hidden="true">→</span></a>
            </article>
            <article class="silaras-landing-contact-card">
                <span class="silaras-landing-contact-icon silaras-contact-purple" aria-hidden="true">▣</span>
                <h3>Kontak &amp; Bantuan</h3>
                <small>Layanan informasi pustaka</small>
                <p>Untuk informasi lebih lanjut, hubungi petugas melalui kanal resmi yang tersedia di lingkungan kampus.</p>
                <a href="{{ route('login') }}">Masuk ke SILARAS <span aria-hidden="true">→</span></a>
            </article>
            <article class="silaras-landing-contact-card">
                <span class="silaras-landing-contact-icon silaras-contact-orange" aria-hidden="true">▣</span>
                <h3>Layanan Digital</h3>
                <small>Akses katalog dari mana saja</small>
                <p>Jelajahi koleksi melalui katalog SILARAS dan periksa informasi ketersediaan buku.</p>
                <a href="#koleksi">Jelajahi katalog <span aria-hidden="true">→</span></a>
            </article>
        </div>
    </section>
@endsection
