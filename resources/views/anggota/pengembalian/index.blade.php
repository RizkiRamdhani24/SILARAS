@extends('layouts.anggota')

@section('title', 'Pengembalian Koleksi & Hitung Denda | SILARAS')

@push('styles')
    @vite('resources/css/anggota/pengembalian.css')
@endpush

@section('content')
    @php
        $returnMember = $member ?? auth('anggota')->user();
        $returnDataProvided = isset($loans);
        $returnLoans = $returnDataProvided ? collect($loans) : collect();
        $activeStatuses = [
            \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI,
            \App\Models\Peminjaman::STATUS_DIPINJAM,
            \App\Models\Peminjaman::STATUS_TERLAMBAT,
            \App\Models\Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
        ];
        $activeReturnLoans = $returnDataProvided
            ? $returnLoans->whereIn('status_peminjaman', $activeStatuses)
            : collect();
        $completedReturnLoans = $returnDataProvided
            ? $returnLoans->filter(
                fn ($loan) => $loan->status_peminjaman === \App\Models\Peminjaman::STATUS_DIKEMBALIKAN
                    && $loan->relationLoaded('pengembalian')
                    && $loan->pengembalian
            )
            : collect();
        $returnRelationsAvailable = $returnDataProvided
            && $returnLoans->isNotEmpty()
            && $returnLoans->every(fn ($loan) => $loan->relationLoaded('pengembalian'));
        $outstandingFine = $returnRelationsAvailable
            ? $returnLoans->pluck('pengembalian')->filter()
                ->where('status_denda', \App\Models\Pengembalian::DENDA_BELUM_DIBAYAR)
                ->sum(fn ($return) => (float) $return->denda)
            : null;
    @endphp

    <section class="return-page" aria-labelledby="return-page-title">
        <header class="return-page-heading">
            <div>
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>Layanan sirkulasi anggota</span>
                <h1 id="return-page-title">Pengembalian Koleksi &amp; Hitung Denda</h1>
                <p>Tinjau koleksi pinjaman, status pengembalian, dan nominal denda yang sudah tercatat. Halaman ini tidak menghitung atau memproses transaksi.</p>
            </div>
            <span class="return-page-member-status"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>{{ $returnMember?->status_anggota ? 'Status anggota: ' . ucfirst($returnMember->status_anggota) : 'Status anggota belum tersedia' }}</span>
        </header>

        <section class="return-page-metrics" aria-label="Ringkasan pengembalian anggota">
            <article class="return-page-metric">
                <header><span>Koleksi dalam proses</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span></b></header>
                <strong>{{ $returnDataProvided ? $activeReturnLoans->count() : '—' }}@if ($returnDataProvided) <small>riwayat</small>@endif</strong>
                <p>{{ $returnDataProvided ? 'Peminjaman aktif atau sedang menunggu proses pengembalian.' : 'Data peminjaman belum diterima halaman ini.' }}</p>
            </article>
            <article class="return-page-metric">
                <header><span>Denda belum dibayar</span><b class="is-red"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span></b></header>
                <strong class="{{ $outstandingFine !== null && $outstandingFine > 0 ? 'is-red' : '' }}">{{ $outstandingFine !== null ? 'Rp ' . number_format($outstandingFine, 0, ',', '.') : 'Belum tersedia' }}</strong>
                <p>{{ $outstandingFine !== null ? 'Berdasarkan catatan denda berstatus belum dibayar.' : 'Memerlukan relasi pengembalian untuk setiap riwayat.' }}</p>
            </article>
            <article class="return-page-metric">
                <header><span>Pengembalian tercatat</span><b class="is-green"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span></b></header>
                <strong>{{ $returnDataProvided ? $completedReturnLoans->count() : '—' }}@if ($returnDataProvided) <small>riwayat</small>@endif</strong>
                <p>{{ $returnDataProvided ? 'Riwayat dikembalikan dengan catatan pengembalian terhubung.' : 'Data riwayat belum diterima halaman ini.' }}</p>
            </article>
        </section>

        <div class="return-page-workspace">
            <section class="return-page-card return-page-loans" aria-labelledby="return-loans-title">
                <header class="return-page-card-heading">
                    <div>
                        <h2 id="return-loans-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span>Detail koleksi yang sedang dipinjam</h2>
                        <p>Koleksi aktif yang diterima halaman ini, tanpa membuat status baru.</p>
                    </div>
                    <span>{{ $returnDataProvided ? $activeReturnLoans->count() . ' data' : 'Belum terhubung' }}</span>
                </header>

                @if (!$returnDataProvided)
                    <div class="return-page-empty">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">wifi_off</span>
                        <h3>Data pengembalian belum terhubung</h3>
                        <p>Halaman belum menerima data peminjaman dan pengembalian dari integrasi backend.</p>
                    </div>
                @elseif ($returnLoans->isEmpty())
                    <div class="return-page-empty">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span>
                        <h3>Belum ada data peminjaman</h3>
                        <p>Tidak ada riwayat peminjaman yang diterima untuk akun ini.</p>
                    </div>
                @elseif ($activeReturnLoans->isEmpty())
                    <div class="return-page-empty">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>
                        <h3>Tidak ada koleksi dalam proses</h3>
                        <p>Data yang diterima tidak memuat peminjaman aktif atau pengembalian yang sedang diajukan.</p>
                    </div>
                @else
                    <div class="return-page-loan-list">
                        @foreach ($activeReturnLoans as $loan)
                            @php
                                $book = $loan->relationLoaded('buku') ? $loan->buku : null;
                                $return = $loan->relationLoaded('pengembalian') ? $loan->pengembalian : null;
                                $isLate = $loan->status_peminjaman === \App\Models\Peminjaman::STATUS_TERLAMBAT;
                            @endphp
                            <article class="return-page-loan">
                                <div class="return-page-loan-icon"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></div>
                                <div class="return-page-loan-main">
                                    <div class="return-page-loan-title">
                                        <div>
                                            <small>Koleksi pinjaman #{{ $loan->getKey() ?? '—' }}</small>
                                            <h3>{{ $book?->judul_buku ?: 'Data buku belum tersedia' }}</h3>
                                            <p>{{ $book?->pengarang ?: 'Pengarang belum tersedia' }}</p>
                                        </div>
                                        <span class="return-page-badge {{ $isLate ? 'is-late' : 'is-active' }}">{{ ucfirst($loan->status_peminjaman ?: 'Status belum tersedia') }}</span>
                                    </div>
                                    <dl>
                                        <div><dt>Tanggal pinjam</dt><dd>{{ $loan->tgl_peminjaman?->format('j M Y') ?? 'Belum tersedia' }}</dd></div>
                                        <div><dt>Jatuh tempo</dt><dd>{{ $loan->tgl_jatuh_tempo?->format('j M Y') ?? 'Belum tersedia' }}</dd></div>
                                        <div><dt>Lokasi koleksi</dt><dd>{{ $book?->rak ? 'Rak ' . $book->rak : 'Belum tersedia' }}</dd></div>
                                    </dl>
                                    <div class="return-page-return-state">
                                        <span><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Status pengembalian</span>
                                        @if (!$loan->relationLoaded('pengembalian'))
                                            <strong>Relasi belum dimuat</strong>
                                        @elseif (!$return)
                                            <strong>Belum ada catatan pengembalian</strong>
                                        @else
                                            <strong>{{ ucfirst($return->status_pengembalian ?: 'Belum tersedia') }}</strong>
                                            <small>Kondisi: {{ $return->kondisi_buku ? ucfirst($return->kondisi_buku) : 'Belum tersedia' }}</small>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="return-page-card return-page-preview" aria-labelledby="return-preview-title">
                <header class="return-page-card-heading">
                    <div>
                        <h2 id="return-preview-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">description</span>Proses pengembalian</h2>
                        <p>Preview layanan, belum dapat mengirim transaksi.</p>
                    </div>
                    <span>Nonaktif</span>
                </header>
                <div class="return-page-preview-body">
                    <section>
                        <span>1</span>
                        <div><h3>Pilih koleksi</h3><p>Koleksi aktif perlu dipilih melalui layanan pengembalian yang terintegrasi.</p></div>
                    </section>
                    <section>
                        <span>2</span>
                        <div><h3>Validasi kondisi</h3><p>Kondisi fisik dan tanggal pengembalian harus diverifikasi petugas.</p></div>
                    </section>
                    <section>
                        <span>3</span>
                        <div><h3>Konfirmasi catatan</h3><p>Status dan denda mengikuti catatan resmi layanan perpustakaan.</p></div>
                    </section>
                    <button type="button" disabled aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">wifi</span>Pengajuan belum tersedia</button>
                    <p class="return-page-preview-note">Halaman ini tidak mengirim pengajuan, pembayaran, atau perubahan data.</p>
                </div>
            </aside>
        </div>

        <section class="return-page-card return-page-fines" aria-labelledby="return-fines-title">
            <header class="return-page-card-heading">
                <div>
                    <h2 id="return-fines-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span>Status pengembalian dan denda</h2>
                    <p>Nominal ditampilkan langsung dari catatan pengembalian; tidak ada tarif yang dihitung oleh halaman.</p>
                </div>
            </header>
            @if (!$returnDataProvided || $returnLoans->isEmpty())
                <p class="return-page-inline-empty">{{ $returnDataProvided ? 'Belum ada data untuk ditampilkan.' : 'Data peminjaman belum terhubung.' }}</p>
            @else
                <div class="return-page-status-grid">
                    @foreach ($returnLoans as $loan)
                        @php
                            $book = $loan->relationLoaded('buku') ? $loan->buku : null;
                            $return = $loan->relationLoaded('pengembalian') ? $loan->pengembalian : null;
                        @endphp
                        <article>
                            <div><small>#{{ $loan->getKey() ?? '—' }}</small><h3>{{ $book?->judul_buku ?: 'Data buku belum tersedia' }}</h3></div>
                            @if (!$loan->relationLoaded('pengembalian'))
                                <span class="return-page-badge">Relasi belum dimuat</span>
                            @elseif (!$return)
                                <span class="return-page-badge">Belum ada catatan</span>
                            @else
                                <dl>
                                    <div><dt>Status pengembalian</dt><dd>{{ ucfirst($return->status_pengembalian ?: 'Belum tersedia') }}</dd></div>
                                    <div><dt>Status denda</dt><dd>{{ ucfirst($return->status_denda ?: 'Belum tersedia') }}</dd></div>
                                    <div><dt>Nominal tercatat</dt><dd>{{ $return->denda !== null ? 'Rp ' . number_format((float) $return->denda, 0, ',', '.') : 'Belum tersedia' }}</dd></div>
                                </dl>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="return-page-card return-page-history" aria-labelledby="return-history-title">
            <header class="return-page-card-heading">
                <div>
                    <h2 id="return-history-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Riwayat pengembalian tercatat</h2>
                    <p>Hanya menampilkan peminjaman berstatus dikembalikan dengan relasi pengembalian tersedia.</p>
                </div>
                <span>{{ $returnDataProvided ? $completedReturnLoans->count() . ' riwayat' : 'Belum terhubung' }}</span>
            </header>
            @if (!$returnDataProvided)
                <p class="return-page-inline-empty">Data riwayat belum diterima halaman ini.</p>
            @elseif ($completedReturnLoans->isEmpty())
                <p class="return-page-inline-empty">Belum ada riwayat pengembalian lengkap untuk ditampilkan.</p>
            @else
                <div class="return-page-history-list">
                    @foreach ($completedReturnLoans as $loan)
                        @php
                            $book = $loan->relationLoaded('buku') ? $loan->buku : null;
                            $return = $loan->pengembalian;
                        @endphp
                        <article>
                            <div class="return-page-history-mark"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></div>
                            <div><h3>{{ $book?->judul_buku ?: 'Data buku belum tersedia' }}</h3><p>{{ $book?->pengarang ?: 'Pengarang belum tersedia' }} · #{{ $loan->getKey() ?? '—' }}</p></div>
                            <dl>
                                <div><dt>Tanggal kembali</dt><dd>{{ $return->tgl_pengembalian?->format('j M Y') ?? 'Belum tersedia' }}</dd></div>
                                <div><dt>Kondisi</dt><dd>{{ $return->kondisi_buku ? ucfirst($return->kondisi_buku) : 'Belum tersedia' }}</dd></div>
                                <div><dt>Denda</dt><dd>{{ $return->denda !== null ? 'Rp ' . number_format((float) $return->denda, 0, ',', '.') : 'Belum tersedia' }}</dd></div>
                            </dl>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="return-page-info" aria-labelledby="return-info-title">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>
            <div><h2 id="return-info-title">Catatan layanan pengembalian</h2><p>Pengajuan, pemeriksaan kondisi, pembayaran, dan perubahan status belum tersedia pada halaman ini. Hubungi pustakawan untuk proses resmi atau klarifikasi data.</p></div>
            <a href="{{ route('home') }}#kontak">Hubungi pustakawan</a>
        </section>
    </section>
@endsection
