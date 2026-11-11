@extends('layouts.anggota')

@section('title', 'Peminjaman Saya | SILARAS')

@section('page_class', 'member-page-loans')

@push('styles')
    @vite('resources/css/anggota/peminjaman.css')
@endpush

@section('content')
    @php $penolakanTerbaru = $penolakanTerbaru ?? collect(); @endphp
    <section class="loan-page" aria-labelledby="loan-page-title">
        <header class="loan-page-heading">
            <div>
                <h1 id="loan-page-title">Peminjaman Saya</h1>
                <p>Pantau pengajuan, peminjaman aktif, jatuh tempo, dan riwayat pengembalian buku.</p>
            </div>
            <a class="button button-primary" href="{{ route('anggota.katalog') }}">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>
                Cari Buku
            </a>
        </header>

        @if (session('success'))
            <div role="status" class="loan-page-notice is-success">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">check_circle</span>
                <div><strong>Berhasil</strong><p>{{ session('success') }}</p></div>
            </div>
        @endif
        @if (session('error') || $errors->any())
            <div role="alert" class="loan-page-notice">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>
                <div><strong>Perhatian</strong><p>{{ session('error') ?? $errors->first() }}</p></div>
            </div>
        @endif

        @if ($penolakanTerbaru->isNotEmpty())
            <section class="loan-page-card mt-4" aria-labelledby="loan-rejection-title">
                <header class="loan-page-card-heading">
                    <h2 id="loan-rejection-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>Informasi pengajuan</h2>
                    <span>{{ $penolakanTerbaru->count() }} pemberitahuan</span>
                </header>
                @foreach ($penolakanTerbaru as $notification)
                    <p class="mt-3 text-sm text-slate-700">{{ $notification->isi }}</p>
                    <small class="mt-1 block text-xs text-slate-500">{{ $notification->created_at?->format('d M Y H:i') }}</small>
                @endforeach
            </section>
        @endif

        <section class="loan-page-card mt-5" aria-labelledby="loan-list-title">
            <header class="loan-page-card-heading">
                <h2 id="loan-list-title"><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span>Daftar peminjaman</h2>
                <span>{{ $peminjaman->count() }} transaksi</span>
            </header>

            @forelse ($peminjaman as $item)
                @php
                    $return = $item->pengembalian;
                    $book = $item->buku;
                    $isReturnable = in_array($item->status_peminjaman, [
                        \App\Models\Peminjaman::STATUS_DIPINJAM,
                        \App\Models\Peminjaman::STATUS_TERLAMBAT,
                    ], true) && ! $return;
                @endphp
                <article class="loan-page-book mt-4">
                    <div class="loan-page-cover" aria-hidden="true">
                        @if ($book?->cover && \Illuminate\Support\Facades\Storage::disk('public')->exists($book->cover))
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($book->cover) }}" alt="">
                        @else
                            <div><span class="material-symbols-rounded member-icon">menu_book</span><span>{{ $book?->kategori ?: 'Koleksi pustaka' }}</span></div>
                        @endif
                    </div>
                    <div class="loan-page-book-info">
                        <div class="loan-page-book-labels">
                            <span>#{{ $item->id_peminjaman }} · {{ $book?->kategori ?: 'Buku' }}</span>
                            <b>{{ ucfirst($item->status_peminjaman) }}</b>
                        </div>
                        <h3>{{ $book?->judul_buku ?? 'Buku tidak tersedia' }}</h3>
                        <p>{{ $book?->pengarang ?? 'Pengarang belum tersedia' }}</p>
                        <dl>
                            <div><dt>Tanggal pengajuan</dt><dd>{{ $item->tgl_peminjaman?->format('d M Y') ?? 'Belum tersedia' }}</dd></div>
                            <div><dt>Durasi</dt><dd>{{ $item->durasi_peminjaman_hari ? $item->durasi_peminjaman_hari . ' hari' : 'Belum ditentukan' }}</dd></div>
                            <div><dt>Jatuh tempo</dt><dd>{{ $item->tgl_jatuh_tempo?->format('d M Y') ?? 'Belum ditetapkan' }}</dd></div>
                            @if ($return)
                                <div><dt>{{ $return->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI ? 'Tanggal kembali' : 'Tanggal pengajuan kembali' }}</dt><dd>{{ $return->status_pengembalian === \App\Models\Pengembalian::STATUS_SELESAI ? ($return->tgl_pengembalian?->format('d M Y') ?? 'Belum tersedia') : ($return->created_at?->format('d M Y') ?? 'Belum tersedia') }}</dd></div>
                            @endif
                        </dl>
                        @if ($item->status_peminjaman === \App\Models\Peminjaman::STATUS_DITOLAK && $item->alasan_penolakan)
                            <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800"><strong>Alasan penolakan:</strong> {{ $item->alasan_penolakan }}</p>
                        @endif
                        <div class="loan-page-actions">
                            @if ($item->status_peminjaman === \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI)
                                <form action="{{ route('anggota.peminjaman.batalkan', $item->id_peminjaman) }}" method="POST" data-confirm="Batalkan pengajuan peminjaman ini?" data-confirm-title="Batalkan pengajuan">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="button button-secondary">Batalkan Pengajuan</button>
                                </form>
                            @elseif ($isReturnable)
                                <form action="{{ route('anggota.pengembalian.ajukan', $item->id_peminjaman) }}" method="POST" data-confirm="Ajukan pengembalian buku ini kepada Petugas?" data-confirm-title="Ajukan pengembalian">
                                    @csrf
                                    <button type="submit" class="button button-primary">
                                        <span class="material-symbols-rounded member-icon" aria-hidden="true">assignment_return</span>
                                        Ajukan Pengembalian
                                    </button>
                                </form>
                            @elseif ($return?->status_pengembalian === \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI)
                                <span class="loan-page-readonly">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">schedule</span>
                                    <span><strong>Menunggu konfirmasi Petugas</strong><small>Pengajuan {{ $return->created_at?->format('d M Y H:i') }}</small></span>
                                </span>
                            @elseif ($item->status_peminjaman === \App\Models\Peminjaman::STATUS_DIKEMBALIKAN)
                                <span class="loan-page-readonly">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">task_alt</span>
                                    <span><strong>Pengembalian selesai</strong><small>{{ $return?->tgl_pengembalian?->format('d M Y') ?? 'Tanggal tidak tersedia' }}</small></span>
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="loan-page-empty">
                    <span><span class="material-symbols-rounded member-icon" aria-hidden="true">bookmarks</span></span>
                    <h3>Belum ada peminjaman</h3>
                    <p>Pengajuan peminjaman akan muncul di halaman ini setelah dikirim dari katalog.</p>
                    <a class="button button-primary" href="{{ route('anggota.katalog') }}">Jelajahi katalog</a>
                </div>
            @endforelse
        </section>

        <aside class="loan-page-sop" aria-label="Informasi alur pengembalian">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">info</span>
            <div>
                <h2>Alur pengembalian</h2>
                <p>Setelah diajukan, buku tetap tercatat dipinjam sampai Petugas menerima dan mengonfirmasi pengembaliannya. Denda keterlambatan dihitung sampai tanggal konfirmasi.</p>
            </div>
            <nav><a href="{{ route('anggota.pengembalian.index') }}">Lihat pengembalian</a><a href="{{ route('anggota.riwayat') }}">Riwayat</a></nav>
        </aside>
    </section>
@endsection
