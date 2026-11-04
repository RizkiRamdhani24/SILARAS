@extends('layouts.anggota')

@section('title', 'Dashboard Anggota | SILARAS')

@section('content')
    {{-- ===== PAGE: DASHBOARD ANGGOTA ===== --}}
    @php
        $memberName = $member?->nama_anggota;
        $loansProvided = isset($loans);
        $loans = $loans ?? collect();
        $activeLoans = $activeLoans ?? collect();
        $returnedLoans = $loans->where('status_peminjaman', \App\Models\Peminjaman::STATUS_DIKEMBALIKAN);
        $nearestDueDate = $activeLoans->pluck('tgl_jatuh_tempo')->filter()->sort()->first();
        $dueDays = $nearestDueDate ? now()->startOfDay()->diffInDays($nearestDueDate, false) : null;
        $fineDataAvailable = $loansProvided
            && $loans->isNotEmpty()
            && $loans->every(fn ($loan) => $loan->relationLoaded('pengembalian'));
        $unpaidFine = $fineDataAvailable
            ? $loans->pluck('pengembalian')->filter()
                ->where('status_denda', \App\Models\Pengembalian::DENDA_BELUM_DIBAYAR)
                ->sum(fn ($return) => (float) $return->denda)
            : null;
    @endphp
    {{-- SECTION: Welcome banner --}}
    <section class="member-hero" aria-labelledby="welcome-title">
        <div>
            <div class="member-badges">
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">verified</span> {{ $member ? 'AKUN TERVERIFIKASI' : 'MODE PREVIEW' }}</span>
                <span class="is-light"><i></i> STATUS: {{ strtoupper($member?->status_anggota ?? 'BELUM TERSEDIA') }}</span>
            </div>
            <h1 id="welcome-title">Selamat Datang Kembali{{ $memberName ? ', ' . $memberName : '' }}</h1>
            <div class="member-identity">
                <span>No. Anggota: {{ $member?->id_anggota ?? 'Belum tersedia' }}</span>
                <i aria-hidden="true"></i>
                <span>Perpustakaan Akademik SILARAS</span>
            </div>
        </div>
        <div class="member-hero-actions">
            <a class="button button-primary" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Cari Buku di Katalog Publik <span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span></a>
            <a class="button button-light" href="#loan-section"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span>Lihat Peminjaman Saya</a>
        </div>
    </section>

    {{-- SECTION: Statistics --}}
    <section class="member-stats" aria-label="Ringkasan anggota">
        <article class="stat-card">
            <header><span>KUOTA PINJAMAN AKTIF</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span></b></header>
            <strong>{{ $member ? $activeLoans->count() : '—' }} <small>Buku</small></strong>
            <p>{{ $member ? 'Peminjaman aktif saat ini' : 'Status kuota belum tersedia' }}</p>
        </article>
        <article class="stat-card">
            <header><span>BATAS PENGEMBALIAN</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span></b></header>
            @if ($dueDays === null)
                <strong>— <small>Hari</small></strong>
                <p>Tidak ada jatuh tempo aktif</p>
            @elseif ($dueDays < 0)
                <strong class="stat-alert">Terlambat</strong>
                <p>{{ abs($dueDays) }} hari lewat jatuh tempo, {{ $nearestDueDate->format('j F Y') }}</p>
            @elseif ($dueDays === 0)
                <strong>Hari ini</strong>
                <p>Jatuh tempo {{ $nearestDueDate->format('j F Y') }}</p>
            @else
                <strong>{{ $dueDays }} <small>Hari</small></strong>
                <p>Jatuh tempo {{ $nearestDueDate->format('j F Y') }}</p>
            @endif
        </article>
        <article class="stat-card">
            <header><span>TUNGGAKAN DENDA</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span></b></header>
            <strong class="{{ $unpaidFine !== null && (float) $unpaidFine === 0.0 ? 'stat-green' : '' }}">{{ $unpaidFine !== null ? 'Rp' . number_format($unpaidFine, 0, ',', '.') : 'Belum tersedia' }}</strong>
            <p>{{ $unpaidFine !== null ? ($unpaidFine > 0 ? 'Denda yang belum dibayar' : 'Tidak ada tunggakan denda') : 'Informasi denda belum dapat diverifikasi' }}</p>
        </article>
        <article class="stat-card">
            <header><span>KOLEKSI DIBACA</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></b></header>
            <strong>{{ $member ? $returnedLoans->count() : '—' }} <small>Buku</small></strong>
            <p>{{ $member ? 'Peminjaman yang telah selesai' : 'Riwayat baca belum tersedia' }}</p>
        </article>
    </section>

    {{-- SECTION: Main content --}}
    <div class="member-content-grid">
        <div class="member-primary-column">
            <section class="loan-panel" id="loan-section" aria-labelledby="loan-title">
                <div class="panel-heading">
                    <h2 id="loan-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span>Buku yang Sedang Dipinjam &amp; Dibawa Pulang</h2>
                    <span>DATA PEMINJAMAN</span>
                </div>
                @if ($activeLoans->isNotEmpty())
                    <div class="loan-list">
                        @foreach ($activeLoans as $loan)
                            @php
                                $book = $loan->relationLoaded('buku') ? $loan->buku : null;
                                $loanDueDays = $loan->tgl_jatuh_tempo ? now()->startOfDay()->diffInDays($loan->tgl_jatuh_tempo, false) : null;
                            @endphp
                            <article class="loan-item">
                                <div class="loan-book">
                                    <div class="loan-cover" aria-hidden="true">
                                        @if ($book?->cover)
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul buku {{ $book->judul_buku }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                            <span class="material-symbols-rounded member-icon" hidden aria-hidden="true">menu_book</span>
                                        @else
                                            <span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>
                                        @endif
                                    </div>
                                    <div>
                                        @if ($book?->kategori)<span class="loan-category">{{ $book->kategori }}</span>@endif
                                        <h3>{{ $book?->judul_buku ?? 'Buku tidak tersedia' }}</h3>
                                        <p>{{ $book?->pengarang ?? 'Penulis belum tersedia' }}</p>
                                        @if ($book?->rak)<small class="loan-location"><span class="material-symbols-rounded member-icon" aria-hidden="true">location_on</span>Rak {{ $book->rak }}</small>@endif
                                    </div>
                                </div>
                                <dl>
                                    <div><dt>Status</dt><dd>{{ ucfirst($loan->status_peminjaman) }}</dd></div>
                                    <div><dt>Dipinjam</dt><dd>{{ $loan->tgl_peminjaman?->format('j F Y') ?? 'Belum tersedia' }}</dd></div>
                                    <div><dt>Jatuh tempo</dt><dd>@if ($loanDueDays === null) Belum tersedia @elseif ($loanDueDays < 0) Terlambat {{ abs($loanDueDays) }} hari @elseif ($loanDueDays === 0) Hari ini @else {{ $loan->tgl_jatuh_tempo->format('j F Y') }} @endif</dd></div>
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>
                        <h3>Belum ada buku yang sedang dipinjam</h3>
                        <p>Data peminjaman aktif akan tampil di bagian ini saat tersedia.</p>
                        <a class="button button-primary" href="{{ route('anggota.katalog') }}">Jelajahi Katalog Buku</a>
                    </div>
                @endif
            </section>

            {{-- SECTION: Recommendations --}}
            <section class="recommendations" id="recommendation-section" aria-labelledby="recommendation-title">
                <div class="recommendation-title">
                    <h2 id="recommendation-title"><i></i>Rekomendasi Buku</h2>
                    <p>Rekomendasi akan tampil saat layanan kurasi koleksi tersedia.</p>
                </div>
                <div class="empty-state recommendation-empty">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span>
                    <h3>Rekomendasi belum tersedia</h3>
                    <p>Belum ada data kurasi buku yang dapat ditampilkan untuk akun ini.</p>
                    <a class="button button-light" href="{{ route('anggota.katalog') }}">Jelajahi Katalog Buku</a>
                </div>
            </section>
        </div>

        <aside class="member-service-column" id="service-section" aria-label="Informasi layanan anggota">
            {{-- COMPONENT: Librarian service card --}}
            <section class="librarian-card">
                <header><span><span class="material-symbols-rounded member-icon" aria-hidden="true">support_agent</span></span><small><i></i>Layanan pustakawan</small></header>
                <h2>Bantuan Layanan<br>Pustakawan</h2>
                <p>Butuh asistensi pencarian rak fisik atau informasi layanan? Tim pustakawan siap membantu melalui kanal resmi perpustakaan.</p>
                <a class="button button-white" href="{{ route('home') }}#kontak"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Informasi Kontak</a>
            </section>

            {{-- COMPONENT: Service information --}}
            <section class="service-card">
                <h2><span class="material-symbols-rounded member-icon" aria-hidden="true">dashboard</span>Informasi Jam Operasional &amp; Layanan Mandiri</h2>
                <article>
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span>
                    <div><small>JAM OPERASIONAL LAYANAN</small><strong>Informasi belum tersedia</strong><p>Hubungi pustakawan untuk memastikan jadwal layanan.</p></div>
                </article>
                <article>
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span>
                    <div><small>DROP BOX MANDIRI</small><strong>Informasi belum tersedia</strong><p>Lokasi layanan akan tampil setelah data tersedia.</p></div>
                </article>
                <article>
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">wifi</span>
                    <div><small>AKSES INTERNET</small><strong>Informasi belum tersedia</strong><p>Detail jaringan tidak ditampilkan pada mode preview.</p></div>
                </article>
            </section>

            {{-- COMPONENT: Community agenda --}}
            <section class="agenda-card">
                <div class="panel-heading"><h2><span class="material-symbols-rounded member-icon" aria-hidden="true">calendar_month</span>Agenda Komunitas &amp; Literasi</h2></div>
                <div class="agenda-empty">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">calendar_month</span>
                    <strong>Agenda belum tersedia</strong>
                    <p>Jadwal kegiatan akan tampil saat sudah diterbitkan.</p>
                </div>
                <a href="{{ route('home') }}#faq">Lihat Informasi Layanan <span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_forward</span></a>
            </section>
        </aside>
    </div>
@endsection
