@extends('layouts.anggota')

@section('title', 'Peminjaman Buku Mandiri | SILARAS')

@push('styles')
    @vite('resources/css/anggota/peminjaman.css')
@endpush

@section('content')
    @php
        $loanMember = $member ?? auth('anggota')->user();
        $selectedBook = $book ?? null;
    @endphp

    <section class="loan-page" aria-labelledby="loan-page-title">
        <header class="loan-page-heading">
            <div>
                <h1 id="loan-page-title">Peminjaman Buku Mandiri</h1>
                <p>Tinjau buku dan persyaratan yang tersedia sebelum melanjutkan proses peminjaman bersama pustakawan.</p>
            </div>
            <span><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>Preview layanan</span>
        </header>

        <section class="loan-page-prerequisites" aria-label="Prasyarat peminjaman">
            <article class="loan-page-metric">
                <header><span>Status anggota</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">person</span></b></header>
                <strong>{{ $loanMember?->status_anggota ? ucfirst($loanMember->status_anggota) : 'Belum diverifikasi' }}</strong>
                <p>
                    {{ $loanMember?->nama_anggota ?: 'Identitas anggota belum tersedia' }}
                    @if ($loanMember?->id_anggota)
                        · No. anggota: {{ $loanMember->id_anggota }}
                    @endif
                </p>
            </article>
            <article class="loan-page-metric">
                <header><span>Kuota peminjaman</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span></b></header>
                <strong>Belum diverifikasi</strong>
                <p>Informasi kuota memerlukan layanan backend.</p>
            </article>
            <article class="loan-page-metric">
                <header><span>Tunggakan dan sanksi</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span></b></header>
                <strong>Belum diverifikasi</strong>
                <p>Informasi tunggakan memerlukan layanan backend.</p>
            </article>
        </section>

        <div class="loan-page-workflow">
            <section class="loan-page-card loan-page-selection" aria-labelledby="loan-page-selection-title">
                <header class="loan-page-card-heading">
                    <h2 id="loan-page-selection-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Buku terpilih dari katalog</h2>
                    @if ($selectedBook)
                        <span>ID koleksi: {{ $selectedBook->getKey() }}</span>
                    @endif
                </header>

                @if ($selectedBook)
                    <article class="loan-page-book">
                        <div class="loan-page-cover">
                            @if ($selectedBook->cover)
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($selectedBook->cover) }}" alt="Sampul buku {{ $selectedBook->judul_buku }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                <div hidden aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><span>Sampul tidak tersedia</span></div>
                            @else
                                <div aria-label="Sampul buku tidak tersedia"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span><span>Sampul tidak tersedia</span></div>
                            @endif
                        </div>
                        <div class="loan-page-book-info">
                            <div class="loan-page-book-labels">
                                <span>{{ $selectedBook->kategori ?: 'Kategori belum tersedia' }}</span>
                                <b>{{ $selectedBook->stock > 0 ? $selectedBook->stock . ' eksemplar tersedia' : 'Stok tidak tersedia' }}</b>
                            </div>
                            <h3>{{ $selectedBook->judul_buku }}</h3>
                            <p>{{ $selectedBook->pengarang ?: 'Pengarang belum tersedia' }}</p>
                            <dl>
                                <div><dt>ISBN</dt><dd>{{ $selectedBook->isbn ?: 'Belum tersedia' }}</dd></div>
                                <div><dt>Lokasi koleksi</dt><dd>{{ $selectedBook->rak ? 'Rak ' . $selectedBook->rak : 'Belum tersedia' }}</dd></div>
                            </dl>
                        </div>
                    </article>
                @else
                    <div class="loan-page-empty">
                        <span><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span></span>
                        <h3>Belum ada buku yang dipilih</h3>
                        <p>Pilih buku dari katalog. Halaman ini hanya meninjau informasi sebelum proses peminjaman resmi.</p>
                        <a class="button button-primary" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Pilih dari katalog</a>
                    </div>
                @endif

                <div class="loan-page-fields">
                    <section aria-labelledby="loan-page-duration-title">
                        <h3 id="loan-page-duration-title">Durasi peminjaman</h3>
                        <div class="loan-page-readonly"><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span><span><strong>Sesuai ketentuan petugas</strong><small>Durasi dan tanggal jatuh tempo ditetapkan saat peminjaman diproses.</small></span></div>
                    </section>
                    <label>
                        <span>Tanggal rencana pengambilan</span>
                        <input type="date" disabled aria-disabled="true" aria-describedby="loan-page-date-help">
                        <small id="loan-page-date-help">Hanya catatan visual, bukan tanggal peminjaman resmi. Aktif setelah buku tersedia pada halaman ini.</small>
                    </label>
                    <section aria-labelledby="loan-page-location-title">
                        <h3 id="loan-page-location-title">Lokasi koleksi</h3>
                        <div class="loan-page-readonly"><span class="material-symbols-rounded member-icon" aria-hidden="true">location_on</span><span><strong>{{ $selectedBook?->rak ? 'Rak ' . $selectedBook->rak : 'Belum tersedia' }}</strong><small>Lokasi pengambilan ditentukan pustakawan saat proses peminjaman.</small></span></div>
                    </section>
                    <label>
                        <span>Catatan untuk pustakawan <em>(opsional)</em></span>
                        <textarea rows="4" disabled aria-disabled="true" placeholder="Catatan tersedia setelah integrasi layanan."></textarea>
                    </label>
                </div>

                <div class="loan-page-actions">
                    <a class="button button-light" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_back</span>{{ $selectedBook ? 'Ganti buku' : 'Buka katalog' }}</a>
                    <button class="button loan-page-submit" type="button" disabled aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span>Pengajuan belum tersedia</button>
                </div>
            </section>

            <aside class="loan-page-summary" aria-labelledby="loan-page-check-title">
                <section class="loan-page-card loan-page-checks">
                    <header class="loan-page-card-heading">
                        <h2 id="loan-page-check-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>Pemeriksaan persyaratan</h2>
                        <span>Belum lengkap</span>
                    </header>
                    <p>Pemeriksaan hanya memakai data yang tersedia pada view ini.</p>
                    <ul>
                        <li class="{{ $selectedBook && $selectedBook->stock > 0 ? 'is-ready' : 'is-pending' }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span><span><strong>Stok koleksi fisik</strong><small>{{ $selectedBook ? ($selectedBook->stock > 0 ? $selectedBook->stock . ' eksemplar tersedia.' : 'Stok tidak tersedia.') : 'Pilih buku untuk memeriksa stok.' }}</small></span><b>{{ $selectedBook && $selectedBook->stock > 0 ? 'Tersedia' : 'Menunggu' }}</b></li>
                        <li class="is-pending"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span><span><strong>Tunggakan dan sanksi</strong><small>Belum dapat diperiksa.</small></span><b>Menunggu</b></li>
                        <li class="is-pending"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span><span><strong>Kuota peminjaman</strong><small>Belum dapat diperiksa.</small></span><b>Menunggu</b></li>
                    </ul>
                </section>

                <section class="loan-page-notice" aria-label="Status layanan pengajuan">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">wifi</span>
                    <div><strong>Pengajuan online belum dapat diproses</strong><p>Endpoint pengajuan dan validasi backend belum tersedia. Halaman ini tidak mengirim atau menyimpan transaksi.</p></div>
                </section>
            </aside>
        </div>

        <section class="loan-page-sop" aria-labelledby="loan-page-sop-title">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">description</span>
            <div><h2 id="loan-page-sop-title">Ketentuan dan alur peminjaman</h2><p>Pilih buku dari katalog, lalu bawa kartu anggota dan buku ke meja layanan. Pustakawan memeriksa status anggota, kuota, tunggakan, serta menetapkan durasi sebelum transaksi dicatat.</p></div>
            <nav aria-label="Tautan bantuan peminjaman"><a href="{{ route('home') }}#faq">Panduan layanan</a><a href="{{ route('home') }}#kontak">Hubungi pustakawan</a></nav>
        </section>
    </section>
@endsection
