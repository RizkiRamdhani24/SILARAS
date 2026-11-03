@extends('layouts.anggota')

@section('title', $book->judul_buku . ' | SILARAS')

@section('page_class', 'member-page-book-detail')

@section('content')
    {{-- ===== PAGE: DETAIL BUKU ===== --}}
    {{-- SECTION: Book detail --}}
    <a class="book-back" href="{{ route('anggota.katalog') }}" aria-label="Kembali ke katalog"><span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_back</span></a>

    <section class="book-detail" aria-labelledby="book-title">
        <div class="book-detail-cover-column">
            <div class="book-detail-cover">
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
            </div>
            <dl class="book-quick-meta">
                <div><dt>Stok</dt><dd>{{ $book->stock }} Eks.</dd></div>
                <div><dt>Kategori</dt><dd>{{ $book->kategori ?: '—' }}</dd></div>
                <div><dt>Rak</dt><dd>{{ $book->rak ? 'Rak ' . $book->rak : '—' }}</dd></div>
            </dl>
            <div class="book-location"><span class="material-symbols-rounded member-icon" aria-hidden="true">location_on</span><span><strong>{{ $book->rak ? 'Rak ' . $book->rak : 'Lokasi belum tersedia' }}</strong><small>{{ $book->kategori ?: 'Kategori belum tersedia' }}</small></span></div>
        </div>

        <div class="book-detail-content">
            <div class="book-labels">
                @if ($book->kategori)<span><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>{{ $book->kategori }}</span>@endif
                <small>ID: {{ $book->getKey() }}</small>
            </div>
            <h1 id="book-title">{{ $book->judul_buku }}</h1>
            <p class="book-author">Ditulis oleh <strong>{{ $book->pengarang }}</strong>@if ($book->penerbit)<i aria-hidden="true"></i><span>Penerbit {{ $book->penerbit }}</span>@endif</p>

            <div class="book-actions">
                @if ($book->stock > 0)
                    <a class="button button-primary" href="#loan-request"><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>Informasi Peminjaman</a>
                @else
                    <span class="button book-unavailable"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span>Stok tidak tersedia</span>
                @endif
            </div>

            <section class="book-description" aria-labelledby="book-description-title">
                <h2 id="book-description-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Informasi Buku</h2>
                <p>Detail bibliografi tersedia dari katalog perpustakaan. Hubungi pustakawan untuk informasi isi, ketersediaan, atau proses peminjaman buku ini.</p>
            </section>

            <dl class="book-facts">
                <div><dt>ISBN</dt><dd>{{ $book->isbn ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Penerbit</dt><dd>{{ $book->penerbit ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Kategori</dt><dd>{{ $book->kategori ?: 'Belum tersedia' }}</dd></div>
                <div><dt>Format</dt><dd>Buku fisik</dd></div>
            </dl>
        </div>
    </section>

    {{-- SECTION: Loan request preview --}}
    @if ($book->stock > 0)
        <section class="loan-request" id="loan-request" aria-labelledby="loan-request-title">
            <header class="loan-request-heading">
                <div>
                    <h2 id="loan-request-title">Peminjaman Buku Mandiri</h2>
                    <p>Tinjau buku pilihan dan lengkapi rencana pengambilan. Pengajuan belum dapat dikirim sampai layanan backend tersedia.</p>
                </div>
                <span class="loan-request-status"><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span>Menunggu integrasi</span>
            </header>

            <section class="loan-request-prerequisites" aria-label="Ringkasan prasyarat peminjaman">
                <article class="loan-request-metric">
                    <header><span>Status anggota</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">person</span></b></header>
                    <strong>{{ $member?->status_anggota ? ucfirst($member->status_anggota) : 'Belum diverifikasi' }}</strong>
                    <p>
                        {{ $member?->nama_anggota ?: 'Anggota belum tersedia' }}
                        @if ($member?->id_anggota)
                            · No. anggota: {{ $member->id_anggota }}
                        @endif
                    </p>
                </article>
                <article class="loan-request-metric">
                    <header><span>Stok koleksi</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></b></header>
                    <strong>{{ $book->stock }} <small>eksemplar</small></strong>
                    <p>Kuota anggota belum dapat diverifikasi</p>
                </article>
                <article class="loan-request-metric">
                    <header><span>Tunggakan &amp; sanksi</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span></b></header>
                    <strong>Belum diverifikasi</strong>
                    <p>Pemeriksaan memerlukan layanan backend</p>
                </article>
            </section>

            <div class="loan-request-grid">
                <form class="loan-request-form" data-loan-request-form novalidate>
                    <section class="loan-request-card" aria-labelledby="loan-form-title">
                        <header class="loan-request-card-heading">
                            <h3 id="loan-form-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Buku terpilih dari katalog</h3>
                            <span>ID koleksi: {{ $book->getKey() }}</span>
                        </header>

                        <div class="loan-request-book">
                            <div class="loan-request-cover">
                                @if ($book->cover)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul buku {{ $book->judul_buku }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                    <div hidden aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><small>Sampul tidak tersedia</small></div>
                                @else
                                    <div aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><small>Sampul tidak tersedia</small></div>
                                @endif
                            </div>
                            <div class="loan-request-book-info">
                                <div class="loan-request-book-labels">
                                    <span>{{ $book->kategori ?: 'Kategori belum tersedia' }}</span>
                                    <b>{{ $book->stock }} eksemplar tersedia</b>
                                </div>
                                <h4>{{ $book->judul_buku }}</h4>
                                <p>{{ $book->pengarang }}@if ($book->penerbit) <i aria-hidden="true"></i> {{ $book->penerbit }}@endif</p>
                                <dl>
                                    <div><dt>ISBN</dt><dd>{{ $book->isbn ?: 'Belum tersedia' }}</dd></div>
                                    <div><dt>Lokasi koleksi</dt><dd>{{ $book->rak ? 'Rak ' . $book->rak : 'Belum tersedia' }}</dd></div>
                                </dl>
                            </div>
                        </div>

                        <div class="loan-request-fields">
                            <section aria-labelledby="loan-duration-title">
                                <h4 id="loan-duration-title">Durasi peminjaman</h4>
                                <div class="loan-request-readonly"><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span><span><strong>Sesuai ketentuan petugas</strong><small>Durasi dan tanggal jatuh tempo ditetapkan saat peminjaman diproses.</small></span></div>
                            </section>
                            <label>
                                <span>Tanggal rencana pengambilan</span>
                                <input type="date" name="planned_date" data-loan-date disabled aria-disabled="true" aria-describedby="loan-date-help loan-form-feedback">
                                <small id="loan-date-help">Tanggal rencana belum dapat diisi karena pengajuan online belum tersedia.</small>
                            </label>
                            <section aria-labelledby="loan-location-title">
                                <h4 id="loan-location-title">Lokasi koleksi</h4>
                                <div class="loan-request-readonly"><span class="material-symbols-rounded member-icon" aria-hidden="true">location_on</span><span><strong>{{ $book->rak ? 'Rak ' . $book->rak : 'Lokasi belum tersedia' }}</strong><small>Lokasi pengambilan ditentukan oleh pustakawan saat peminjaman diproses.</small></span></div>
                            </section>
                            <label>
                                <span>Catatan untuk pustakawan <em>(opsional)</em></span>
                                <textarea name="note" rows="4" maxlength="300" placeholder="Tambahkan kebutuhan pengambilan bila diperlukan." disabled aria-disabled="true"></textarea>
                            </label>
                        </div>
                        <p class="loan-request-feedback" id="loan-form-feedback" data-loan-feedback role="status" aria-live="polite"></p>

                        <div class="loan-request-actions">
                            <a class="button button-light" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_back</span>Ganti buku dari katalog</a>
                            <button class="button loan-request-submit" type="button" data-loan-submit disabled aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span>Pengajuan belum tersedia</button>
                        </div>
                    </section>
                </form>

                <aside class="loan-request-summary" aria-labelledby="loan-check-title">
                    <section class="loan-request-card loan-request-checks">
                        <header class="loan-request-card-heading">
                            <h3 id="loan-check-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>Pemeriksaan sistem</h3>
                            <span>Belum lengkap</span>
                        </header>
                        <p>Pemeriksaan berikut memakai data yang tersedia pada halaman ini.</p>
                        <ul>
                            <li class="is-ready"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span><span><strong>Stok koleksi fisik</strong><small>{{ $book->stock }} eksemplar tercatat tersedia.</small></span><b>Tersedia</b></li>
                            <li class="is-pending"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span><span><strong>Tunggakan dan sanksi</strong><small>Belum dapat diperiksa.</small></span><b>Menunggu</b></li>
                            <li class="is-pending"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span><span><strong>Kuota peminjaman</strong><small>Belum dapat diperiksa.</small></span><b>Menunggu</b></li>
                        </ul>
                    </section>

                    <section class="loan-request-notice" aria-label="Status pengajuan online">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">wifi</span>
                        <div><strong>Pengajuan online belum dapat diproses</strong><p>Endpoint pengajuan belum tersedia. Halaman ini tidak menyimpan transaksi atau mengirim data.</p></div>
                    </section>
                </aside>
            </div>

            <section class="loan-request-sop" aria-labelledby="loan-sop-title">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">description</span>
                <div><h3 id="loan-sop-title">Ketentuan dan alur peminjaman</h3><p>Bawa kartu anggota dan buku pilihan ke meja layanan. Pustakawan akan memeriksa status anggota, kuota, tunggakan, serta menetapkan durasi peminjaman sebelum transaksi dicatat.</p></div>
                <div class="loan-request-links"><a href="{{ route('home') }}#faq">Panduan layanan</a><a href="{{ route('home') }}#kontak">Hubungi pustakawan</a></div>
            </section>
        </section>
    @endif

    {{-- SECTION: Related books --}}
    <section class="related-books" aria-labelledby="related-books-title">
        <div class="related-books-heading">
            <div><span class="catalog-eyebrow"><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span> Koleksi sejenis</span><h2 id="related-books-title">Buku Terkait</h2><p>{{ $book->kategori ? 'Koleksi lain dalam kategori ' . $book->kategori . '.' : 'Koleksi lain dari katalog perpustakaan.' }}</p></div>
            <a href="{{ route('anggota.katalog', array_filter(['kategori' => $book->kategori])) }}">Lihat katalog <span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span></a>
        </div>
        @if ($relatedBooks->isNotEmpty())
            <div class="catalog-grid">
                @foreach ($relatedBooks as $relatedBook)
                    <article class="catalog-card">
                        <div class="catalog-cover">
                            @if ($relatedBook->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($relatedBook->cover) }}" alt="Sampul buku {{ $relatedBook->judul_buku }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                <div class="catalog-cover-placeholder" hidden aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><span>{{ $relatedBook->kategori ?: 'Koleksi pustaka' }}</span></div>
                            @else
                                <div class="catalog-cover-placeholder" aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><span>{{ $relatedBook->kategori ?: 'Koleksi pustaka' }}</span></div>
                            @endif
                        </div>
                        <div class="catalog-card-body">
                            <h3><a href="{{ route('anggota.buku.show', $relatedBook) }}">{{ $relatedBook->judul_buku }}</a></h3>
                            <p>{{ $relatedBook->pengarang }}</p>
                            <span class="catalog-stock {{ $relatedBook->stock > 0 ? 'is-available' : 'is-unavailable' }}"><i></i>{{ $relatedBook->stock > 0 ? $relatedBook->stock . ' Eksemplar Tersedia' : 'Tidak Tersedia' }}</span>
                            <a class="catalog-detail" href="{{ route('anggota.buku.show', $relatedBook) }}">Detail Buku</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="empty-state related-books-empty"><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span><h3>Belum ada buku terkait</h3><p>Koleksi sejenis akan tampil saat tersedia.</p></div>
        @endif
    </section>
@endsection
