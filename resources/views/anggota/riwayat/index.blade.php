@extends('layouts.anggota')

@section('title', 'Riwayat Peminjaman Buku | SILARAS')

@push('styles')
    @vite('resources/css/anggota/riwayat.css')
@endpush

@section('content')
    @php
        $historyMember = $member ?? auth('anggota')->user();
        $historyProvided = isset($loans);
        $historyLoans = $loans ?? collect();
        $historyCount = $historyProvided ? $historyLoans->count() : null;
        $returnedLoans = $historyProvided
            ? $historyLoans->filter(
                fn ($loan) => $loan->status_peminjaman === \App\Models\Peminjaman::STATUS_DIKEMBALIKAN
            )
            : collect();
        $completeReturnData = $returnedLoans->filter(
            fn ($loan) => $loan->relationLoaded('pengembalian')
                && $loan->pengembalian?->tgl_pengembalian
                && $loan->tgl_jatuh_tempo
        );
        $onTimeCount = $completeReturnData->filter(
            fn ($loan) => $loan->pengembalian->tgl_pengembalian->lte($loan->tgl_jatuh_tempo)
        )->count();
        $punctuality = $completeReturnData->isNotEmpty()
            ? round(($onTimeCount / $completeReturnData->count()) * 100)
            : null;
        $fineDataAvailable = $historyProvided
            && $historyLoans->isNotEmpty()
            && $historyLoans->every(fn ($loan) => $loan->relationLoaded('pengembalian'));
        $unpaidFine = $fineDataAvailable
            ? $historyLoans->pluck('pengembalian')->filter()
                ->where('status_denda', \App\Models\Pengembalian::DENDA_BELUM_DIBAYAR)
                ->sum(fn ($return) => (float) $return->denda)
            : null;
    @endphp

    <section class="history-page" aria-labelledby="history-page-title">
        <header class="history-page-heading">
            <div>
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Riwayat sirkulasi</span>
                <h1 id="history-page-title">Riwayat Peminjaman &amp; Catatan Sirkulasi Warga</h1>
                <p>Daftar peminjaman yang tersedia untuk akun anggota ini, beserta informasi pengembalian dan denda bila datanya terhubung.</p>
            </div>
            <button class="history-page-export" type="button" disabled aria-disabled="true"><span class="material-symbols-rounded member-icon" aria-hidden="true">description</span>Unduhan rekap belum tersedia</button>
        </header>

        <section class="history-page-metrics" aria-label="Ringkasan riwayat peminjaman">
            <article class="history-page-metric">
                <header><span>Total riwayat</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span></b></header>
                <strong>{{ $historyCount ?? '—' }}@if ($historyCount !== null) <small>riwayat</small>@endif</strong>
                <p>{{ $historyProvided ? 'Data peminjaman yang diterima halaman ini.' : 'Riwayat belum dihubungkan ke halaman ini.' }}</p>
            </article>
            <article class="history-page-metric">
                <header><span>Kepatuhan waktu</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span></b></header>
                <strong class="{{ $punctuality !== null ? 'is-green' : '' }}">{{ $punctuality !== null ? $punctuality . '%' : 'Belum tersedia' }}</strong>
                <p>{{ $punctuality !== null ? 'Dihitung dari pengembalian dengan data tanggal lengkap.' : 'Memerlukan jatuh tempo dan tanggal pengembalian.' }}</p>
            </article>
            <article class="history-page-metric">
                <header><span>Denda tertunggak</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">inventory_2</span></b></header>
                <strong>{{ $unpaidFine !== null ? 'Rp ' . number_format($unpaidFine, 0, ',', '.') : 'Belum tersedia' }}</strong>
                <p>{{ $unpaidFine !== null ? 'Dihitung dari denda berstatus belum dibayar.' : 'Memerlukan relasi pengembalian untuk setiap riwayat.' }}</p>
            </article>
        </section>

        <section class="history-page-card" aria-labelledby="history-table-title">
            <header class="history-page-card-heading">
                <div>
                    <h2 id="history-table-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Daftar riwayat peminjaman</h2>
                    <p>{{ $historyProvided ? 'Tampilan data peminjaman yang diterima halaman ini.' : 'Koneksi data riwayat belum tersedia.' }}</p>
                </div>
                <span>{{ $historyCount !== null ? $historyCount . ' data' : 'Belum terhubung' }}</span>
            </header>

            @if (!$historyProvided)
                <div class="history-page-empty">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>
                    <h3>Riwayat belum terhubung</h3>
                    <p>Halaman ini belum menerima koleksi riwayat peminjaman dari integrasi backend. Anda tetap dapat menjelajahi buku yang tersedia melalui katalog.</p>
                    <a class="button button-primary" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Buka katalog</a>
                </div>
            @elseif ($historyLoans->isEmpty())
                <div class="history-page-empty">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">library_books</span>
                    <h3>Belum ada riwayat peminjaman</h3>
                    <p>Belum ada peminjaman yang tercatat untuk akun ini. Pilih buku dari katalog untuk melihat ketersediaan koleksi.</p>
                    <a class="button button-primary" href="{{ route('anggota.katalog') }}"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Jelajahi katalog</a>
                </div>
            @else
                <div class="history-page-table-wrap" tabindex="0" aria-label="Tabel riwayat peminjaman, geser secara horizontal bila diperlukan">
                    <table class="history-page-table">
                        <thead>
                            <tr>
                                <th scope="col">No. transaksi</th>
                                <th scope="col">Judul buku &amp; pengarang</th>
                                <th scope="col">Tgl pinjam</th>
                                <th scope="col">Jatuh tempo</th>
                                <th scope="col">Tgl kembali</th>
                                <th scope="col">Status</th>
                                <th scope="col">Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($historyLoans as $loan)
                                @php
                                    $book = $loan->relationLoaded('buku') ? $loan->buku : null;
                                    $return = $loan->relationLoaded('pengembalian') ? $loan->pengembalian : null;
                                    $isLate = $loan->status_peminjaman === \App\Models\Peminjaman::STATUS_TERLAMBAT
                                        || ($return?->tgl_pengembalian && $loan->tgl_jatuh_tempo && $return->tgl_pengembalian->gt($loan->tgl_jatuh_tempo));
                                    $statusClass = $isLate
                                        ? 'is-late'
                                        : ($loan->status_peminjaman === \App\Models\Peminjaman::STATUS_DIKEMBALIKAN ? 'is-returned' : 'is-active');
                                @endphp
                                <tr>
                                    <td><code>#{{ $loan->getKey() ?? '—' }}</code></td>
                                    <td>
                                        <strong>{{ $book?->judul_buku ?: 'Data buku belum tersedia' }}</strong>
                                        <small>{{ $book?->pengarang ?: 'Pengarang belum tersedia' }}@if ($book?->rak) · Rak {{ $book->rak }}@endif</small>
                                    </td>
                                    <td>{{ $loan->tgl_peminjaman?->locale('id')->translatedFormat('j M Y') ?? 'Belum tersedia' }}</td>
                                    <td>{{ $loan->tgl_jatuh_tempo?->locale('id')->translatedFormat('j M Y') ?? 'Belum tersedia' }}</td>
                                    <td>{{ $return?->tgl_pengembalian?->locale('id')->translatedFormat('j M Y') ?? 'Belum tersedia' }}</td>
                                    <td><span class="history-page-status {{ $statusClass }}"><i></i>{{ ucfirst($loan->status_peminjaman ?: 'belum tersedia') }}</span></td>
                                    <td>
                                        @if ($return?->denda !== null)
                                            <strong>Rp {{ number_format((float) $return->denda, 0, ',', '.') }}</strong>
                                            <small>{{ $return->status_denda ?: 'Status denda belum tersedia' }}</small>
                                        @else
                                            <span>Belum tersedia</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="history-page-info" aria-labelledby="history-info-title">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>
            <div>
                <h2 id="history-info-title">Informasi status pengembalian</h2>
                <p>Detail tanggal, denda, dan status pengembalian mengikuti catatan yang tersedia pada layanan perpustakaan. Hubungi pustakawan bila data memerlukan klarifikasi.</p>
            </div>
            <a href="{{ route('home') }}#kontak">Hubungi pustakawan</a>
        </section>
    </section>
@endsection
