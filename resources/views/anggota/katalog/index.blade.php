@extends('layouts.anggota')

@section('title', 'Katalog Buku | SILARAS')

@section('content')
    {{-- ===== PAGE: KATALOG BUKU ===== --}}
    {{-- SECTION: Catalog hero --}}
    <section class="catalog-hero" aria-labelledby="catalog-title">
        <div>
            <span class="catalog-eyebrow"><span class="material-symbols-rounded member-icon" aria-hidden="true">public</span> Layanan katalog anggota</span>
            <h1 id="catalog-title">Katalog Koleksi Pustaka Umum Daerah</h1>
            <p>Jelajahi koleksi buku bacaan, sastra, pengetahuan umum, dan buku anak. Cek ketersediaan stok fisik sebelum berkunjung ke gedung perpustakaan.</p>
        </div>
        <div class="catalog-total">
            <span class="catalog-total-icon"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span></span>
            <span><strong><i></i>{{ number_format($bookCount, 0, ',', '.') }} Koleksi Fisik</strong><small>Data koleksi tercatat di katalog</small></span>
        </div>
    </section>

    {{-- SECTION: Search and filters --}}
    <section class="catalog-toolbar" aria-label="Pencarian dan filter katalog">
        <form action="{{ route('anggota.katalog') }}" method="GET" role="search">
            <label class="catalog-search">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>
                <span class="sr-only">Cari buku</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Ketik judul buku, nama pengarang, kategori, atau ISBN...">
            </label>
            <button class="button button-primary" type="submit"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Cari Buku</button>

            <div class="catalog-filters">
                <div class="catalog-filter-group">
                    <span class="catalog-filter-label"><span class="material-symbols-rounded member-icon" aria-hidden="true">tune</span>Pilihan kategori koleksi</span>
                    <div class="catalog-pills" aria-label="Filter kategori">
                        <a class="{{ $selectedCategory === '' ? 'is-active' : '' }}" href="{{ route('anggota.katalog', array_filter(['q' => $search, 'ketersediaan' => $selectedAvailability])) }}">Semua Koleksi</a>
                        @foreach ($categories as $category)
                            <a class="{{ $selectedCategory === $category ? 'is-active' : '' }}" href="{{ route('anggota.katalog', array_filter(['q' => $search, 'kategori' => $category, 'ketersediaan' => $selectedAvailability])) }}">{{ $category }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="catalog-filter-group catalog-availability-group">
                    <span class="catalog-filter-label">Status ketersediaan fisik</span>
                    <div class="catalog-pills" aria-label="Filter ketersediaan">
                        <a class="{{ $selectedAvailability === '' ? 'is-active' : '' }}" href="{{ route('anggota.katalog', array_filter(['q' => $search, 'kategori' => $selectedCategory])) }}">Semua Status</a>
                        <a class="{{ $selectedAvailability === 'tersedia' ? 'is-active' : '' }}" href="{{ route('anggota.katalog', array_filter(['q' => $search, 'kategori' => $selectedCategory, 'ketersediaan' => 'tersedia'])) }}"><i class="is-green"></i>Tersedia Siap Pinjam</a>
                        <a class="{{ $selectedAvailability === 'habis' ? 'is-active' : '' }}" href="{{ route('anggota.katalog', array_filter(['q' => $search, 'kategori' => $selectedCategory, 'ketersediaan' => 'habis'])) }}"><i class="is-red"></i>Stok tidak tersedia</a>
                    </div>
                    <span class="catalog-result-count">Menampilkan {{ $books->count() }} dari {{ $books->total() }} koleksi pilihan</span>
                </div>
                @if ($search !== '' || $selectedCategory !== '' || $selectedAvailability !== '')
                    <a class="catalog-clear-filter" href="{{ route('anggota.katalog') }}">Hapus filter</a>
                @endif
            </div>
        </form>
    </section>

    {{-- SECTION: Book collection --}}
    <section aria-label="Daftar koleksi buku">
        @if ($books->isNotEmpty())
            <div class="catalog-grid">
                @foreach ($books as $book)
                    <article class="catalog-card">
                        <div class="catalog-cover">
                            @if ($book->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul buku {{ $book->judul_buku }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                <div class="catalog-cover-placeholder" hidden aria-label="Sampul buku tidak tersedia">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>
                                    <span>{{ $book->kategori ?: 'Koleksi pustaka' }}</span>
                                </div>
                            @else
                                <div class="catalog-cover-placeholder" aria-label="Sampul buku tidak tersedia">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>
                                    <span>{{ $book->kategori ?: 'Koleksi pustaka' }}</span>
                                </div>
                            @endif
                            @if ($book->kategori)<span class="catalog-category">{{ $book->kategori }}</span>@endif
                        </div>
                        <div class="catalog-card-body">
                            <h2><a href="{{ route('anggota.buku.show', $book) }}">{{ $book->judul_buku }}</a></h2>
                            <p>{{ $book->pengarang }}</p>
                            <div class="catalog-card-meta">
                                <span class="catalog-stock {{ $book->stock > 0 ? 'is-available' : 'is-unavailable' }}"><i></i>{{ $book->stock > 0 ? $book->stock . ' Eksemplar Tersedia' : 'Stok tidak tersedia' }}</span>
                                @if ($book->isbn)<small>{{ $book->isbn }}</small>@endif
                            </div>
                            @if ($book->rak)
                                <p class="catalog-location"><span class="material-symbols-rounded member-icon" aria-hidden="true">location_on</span>Rak {{ $book->rak }}@if ($book->kategori) • {{ $book->kategori }}@endif</p>
                            @elseif ($book->penerbit)
                                <p class="catalog-location"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>{{ $book->penerbit }}</p>
                            @endif
                            <a class="catalog-detail" href="{{ route('anggota.buku.show', $book) }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span>Detail Buku</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="catalog-pagination">
                <p>Menampilkan {{ $books->firstItem() }} sampai {{ $books->lastItem() }} dari {{ $books->total() }} koleksi buku</p>
                @if ($books->hasPages())
                    <nav aria-label="Navigasi halaman katalog">
                        @if ($books->onFirstPage())<span aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">chevron_left</span></span>@else<a href="{{ $books->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"><span class="material-symbols-rounded member-icon" aria-hidden="true">chevron_left</span></a>@endif
                        @foreach ($books->getUrlRange(1, $books->lastPage()) as $page => $url)
                            @if ($page === $books->currentPage())<span class="is-active" aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}">{{ $page }}</a>@endif
                        @endforeach
                        @if ($books->hasMorePages())<a href="{{ $books->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"><span class="material-symbols-rounded member-icon" aria-hidden="true">chevron_right</span></a>@else<span aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">chevron_right</span></span>@endif
                    </nav>
                @endif
            </div>
        @else
            <div class="empty-state catalog-empty">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>
                <h2>{{ $search !== '' || $selectedCategory !== '' || $selectedAvailability !== '' ? 'Buku tidak ditemukan' : 'Koleksi buku belum tersedia' }}</h2>
                <p>{{ $search !== '' || $selectedCategory !== '' || $selectedAvailability !== '' ? 'Ubah kata kunci atau filter untuk melihat hasil lain.' : 'Koleksi akan tampil setelah data buku tersedia.' }}</p>
                @if ($search !== '' || $selectedCategory !== '' || $selectedAvailability !== '')
                    <a class="button button-light" href="{{ route('anggota.katalog') }}">Hapus Filter</a>
                @endif
            </div>
        @endif
    </section>

    {{-- SECTION: Collection guide --}}
    <section class="catalog-guide" aria-labelledby="catalog-guide-title">
        <header class="catalog-guide-header">
            <span class="catalog-guide-icon"><span class="material-symbols-rounded member-icon" aria-hidden="true">map</span></span>
            <div>
                <h2 id="catalog-guide-title">Panduan Penelusuran Koleksi</h2>
                <p>Gunakan informasi rak pada setiap kartu buku sebagai petunjuk awal. Konfirmasikan lokasi dan fasilitas kepada pustakawan saat berkunjung.</p>
            </div>
            <div class="catalog-guide-facilities" aria-label="Status informasi fasilitas">
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">accessible</span>Aksesibilitas: perlu konfirmasi</span>
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>Fasilitas: perlu konfirmasi</span>
            </div>
        </header>

        <div class="catalog-guide-grid">
            <article class="catalog-floor">
                <div class="catalog-floor-heading">
                    <strong><i>1</i>Informasi Rak <small>• Berdasarkan data katalog</small></strong>
                    <span>Petunjuk<br>awal</span>
                </div>
                <ul>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">category</span><span><strong>Nomor rak buku:</strong> Periksa informasi rak pada kartu koleksi. Jika tidak tercantum, lokasi belum tersedia pada katalog.</span></li>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">layers</span><span><strong>Kategori koleksi:</strong> Gunakan filter kategori untuk mempersempit pencarian sebelum menuju rak.</span></li>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">desktop_windows</span><span><strong>Konfirmasi lokasi:</strong> Tanyakan lokasi koleksi kepada pustakawan bila petunjuk rak belum cukup.</span></li>
                </ul>
            </article>

            <article class="catalog-floor">
                <div class="catalog-floor-heading">
                    <strong><i>2</i>Informasi Layanan <small>• Perlu konfirmasi</small></strong>
                    <span>Informasi<br>umum</span>
                </div>
                <ul>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span><span><strong>Ketersediaan koleksi:</strong> Stok pada katalog merupakan petunjuk ketersediaan saat data halaman dimuat.</span></li>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">description</span><span><strong>Layanan sirkulasi:</strong> Ketentuan peminjaman dan pengembalian perlu dikonfirmasi melalui layanan resmi perpustakaan.</span></li>
                    <li><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span><span><strong>Fasilitas gedung:</strong> Informasi aksesibilitas dan fasilitas gedung belum tersedia dari sumber data halaman ini.</span></li>
                </ul>
            </article>
        </div>
    </section>
@endsection
