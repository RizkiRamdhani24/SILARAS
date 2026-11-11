@extends('layouts.anggota')

@section('title', 'Pengembalian Buku | SILARAS')

@section('page_class', 'member-page-return')

@push('styles')
    @vite('resources/css/anggota/pengembalian.css')
@endpush

@section('content')
    @php
        $member = $member ?? auth('anggota')->user();
        $memberName = $member?->nama_anggota ?? 'Anggota';
        $activeItems = $peminjaman->filter(fn ($loan) => in_array($loan->status_peminjaman, [
            \App\Models\Peminjaman::STATUS_DIPINJAM,
            \App\Models\Peminjaman::STATUS_TERLAMBAT,
            \App\Models\Peminjaman::STATUS_PENGEMBALIAN_DIAJUKAN,
        ], true));
        $returnedItems = $peminjaman->whereIn('status_peminjaman', [
            \App\Models\Peminjaman::STATUS_DIKEMBALIKAN,
            \App\Models\Peminjaman::STATUS_HILANG,
        ]);
        $pendingCount = $peminjaman->sum(fn ($loan) => $loan->pengembalians
            ->where('status_pengembalian', \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI)
            ->count());
    @endphp

    <section class="return-page" aria-labelledby="return-page-title">
        <header class="return-page-heading">
            <div>
                <span><span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>Pengembalian sirkulasi</span>
                <h1 id="return-page-title">Pengembalian Buku</h1>
                <p>Ajukan pengembalian buku yang sedang dipinjam dan pantau proses konfirmasi Petugas.</p>
            </div>
            <span class="return-page-member-status"><span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>Data akun {{ $memberName }}</span>
        </header>

        @if (session('success'))
            <div class="return-page-info" role="status">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">check_circle</span>
                <div><h2>Berhasil</h2><p>{{ session('success') }}</p></div>
            </div>
        @endif
        @if (session('error') || $errors->any())
            <div class="return-page-info" role="alert">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>
                <div><h2>Perhatian</h2><p>{{ session('error') ?? $errors->first() }}</p></div>
            </div>
        @endif

        <section class="return-page-metrics" aria-label="Ringkasan pengembalian">
            <article class="return-page-metric">
                <header><span>PEMINJAMAN AKTIF</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></b></header>
                <strong>{{ $activeItems->count() }} <small>buku</small></strong>
                <p>Buku masih menjadi tanggung jawab peminjam.</p>
            </article>
            <article class="return-page-metric">
                <header><span>MENUNGGU KONFIRMASI</span><b><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span></b></header>
                <strong>{{ $pendingCount }} <small>pengajuan</small></strong>
                <p>Denda tetap dihitung sampai buku dikonfirmasi diterima.</p>
            </article>
            <article class="return-page-metric">
                <header><span>RIWAYAT SELESAI</span><b class="is-green"><span class="material-symbols-rounded member-icon" aria-hidden="true">task_alt</span></b></header>
                <strong>{{ $returnedItems->count() }} <small>transaksi</small></strong>
                <p>Pengembalian yang telah diselesaikan Petugas.</p>
            </article>
        </section>

        <div class="return-page-workspace">
            <section class="return-page-card" aria-labelledby="return-open-title">
                <header class="return-page-card-heading">
                    <div><h2 id="return-open-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>Peminjaman yang perlu diproses</h2><p>Ajukan pengembalian saat buku diserahkan ke Petugas.</p></div>
                    <span>{{ $activeItems->count() }} transaksi</span>
                </header>
                @forelse ($activeItems as $loan)
                    @php
                        $book = $loan->buku;
                        $return = $loan->pengembalian;
                        $lateDays = $loan->tgl_jatuh_tempo && $loan->tgl_jatuh_tempo->lt(today())
                            ? (int) $loan->tgl_jatuh_tempo->diffInDays(today())
                            : 0;
                    @endphp
                    <article class="return-page-loan">
                        <span class="return-page-loan-icon"><span class="material-symbols-rounded member-icon" aria-hidden="true">menu_book</span></span>
                        <div class="return-page-loan-main">
                            <div class="return-page-loan-title">
                                <div><small>Peminjaman #{{ $loan->id_peminjaman }}</small><h3>{{ $book?->judul_buku ?? 'Buku tidak tersedia' }}</h3><p>{{ $book?->pengarang ?? 'Pengarang belum tersedia' }}</p></div>
                                @if ($lateDays > 0)
                                    <span class="return-page-badge is-late">{{ $lateDays }} hari terlambat</span>
                                @else
                                    <span class="return-page-badge is-active">{{ ucfirst($loan->status_peminjaman) }}</span>
                                @endif
                            </div>
                            <dl>
                                <div><dt>Tanggal pinjam</dt><dd>{{ $loan->tgl_peminjaman?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                                <div><dt>Jatuh tempo</dt><dd>{{ $loan->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                                <div><dt>Status pengembalian</dt><dd>{{ $return ? ucfirst($return->status_pengembalian) : 'Belum diajukan' }}</dd></div>
                            </dl>
                            @if ($return?->status_pengembalian === \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI)
                                <div class="return-page-return-state">
                                    <span><span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span>Pengajuan dikirim {{ $return->created_at?->format('d M Y H:i') }}</span>
                                    <strong>Menunggu konfirmasi Petugas</strong>
                                    <small>Denda dapat bertambah selama menunggu.</small>
                                </div>
                                <form class="mt-3" action="{{ route('anggota.pengembalian.batalkan', $return->id_pengembalian) }}" method="POST" data-confirm="Batalkan pengajuan pengembalian ini?" data-confirm-title="Batalkan pengajuan">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="button button-secondary">Batalkan Pengajuan</button>
                                </form>
                            @else
                                <form class="mt-4" action="{{ route('anggota.pengembalian.ajukan', $loan->id_peminjaman) }}" method="POST" data-confirm="Ajukan pengembalian buku ini kepada Petugas?" data-confirm-title="Ajukan pengembalian">
                                    @csrf
                                    <button type="submit" class="button button-primary"><span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>Ajukan Pengembalian</button>
                                </form>
                            @endif
                            @foreach ($loan->pengembalians->where('status_pengembalian', \App\Models\Pengembalian::STATUS_DIBATALKAN) as $cancelledReturn)
                                <p class="mt-2 text-xs text-slate-500">Pengajuan {{ $cancelledReturn->created_at?->format('d M Y H:i') }} dibatalkan.</p>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <div class="return-page-empty">
                        <span class="material-symbols-rounded member-icon" aria-hidden="true">task_alt</span>
                        <h3>Tidak ada peminjaman aktif</h3>
                        <p>Peminjaman yang dapat dikembalikan akan tampil di bagian ini.</p>
                    </div>
                @endforelse
            </section>

            <aside class="return-page-card" aria-labelledby="return-steps-title">
                <header class="return-page-card-heading">
                    <div><h2 id="return-steps-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>Proses pengembalian</h2><p>Pengajuan belum menandakan buku telah diterima.</p></div>
                </header>
                <div class="return-page-preview-body">
                    <section><span>1</span><div><h3>Serahkan buku</h3><p>Serahkan buku fisik kepada Petugas perpustakaan.</p></div></section>
                    <section><span>2</span><div><h3>Ajukan pengembalian</h3><p>Kirim pengajuan dari transaksi yang sesuai setelah buku diserahkan.</p></div></section>
                    <section><span>3</span><div><h3>Tunggu konfirmasi</h3><p>Petugas memeriksa kondisi dan mencatat tanggal penerimaan.</p></div></section>
                    <p class="return-page-preview-note">Denda keterlambatan berhenti saat Petugas mengonfirmasi penerimaan buku.</p>
                </div>
            </aside>
        </div>

        <section class="return-page-card return-page-history" aria-labelledby="return-history-title">
            <header class="return-page-card-heading">
                <div><h2 id="return-history-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">history</span>Riwayat pengembalian selesai</h2><p>Catatan tanggal aktual, kondisi buku, dan denda final bila ada.</p></div>
                <span>{{ $returnedItems->count() }} transaksi</span>
            </header>
            @forelse ($returnedItems as $loan)
                @php $return = $loan->pengembalianAktif; @endphp
                <article class="return-page-history-list">
                    <div class="return-page-history-mark"><span class="material-symbols-rounded member-icon" aria-hidden="true">task_alt</span></div>
                    <div><h3>{{ $loan->buku?->judul_buku ?? 'Buku tidak tersedia' }}</h3><p>Peminjaman #{{ $loan->id_peminjaman }} · {{ $loan->buku?->pengarang ?? 'Pengarang belum tersedia' }}</p></div>
                    <dl>
                        <div><dt>Tanggal pengembalian</dt><dd>{{ $return?->tgl_pengembalian?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                        <div><dt>Kondisi buku</dt><dd>{{ $return?->kondisi_buku ? ucfirst($return->kondisi_buku) : 'Belum tersedia' }}</dd></div>
                        <div><dt>Denda</dt><dd>Rp {{ number_format((float) ($return?->denda ?? 0), 0, ',', '.') }} · {{ $return?->status_denda ?? 'Tidak ada catatan' }}</dd></div>
                    </dl>
                </article>
            @empty
                <p class="return-page-inline-empty">Belum ada pengembalian yang selesai.</p>
            @endforelse
        </section>

        <aside class="return-page-info" aria-label="Informasi layanan">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">verified_user</span>
            <div><h2>Status transaksi</h2><p>Pengajuan hanya dapat dibuat untuk peminjaman milik akun ini yang masih aktif. Pengajuan ganda dan transaksi milik Anggota lain ditolak oleh backend.</p></div>
            <a href="{{ route('anggota.riwayat') }}">Buka riwayat peminjaman</a>
        </aside>
    </section>
@endsection
