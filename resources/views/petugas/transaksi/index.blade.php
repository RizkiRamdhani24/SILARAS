@extends('petugas.layout')

@section('title', 'Riwayat Transaksi')

@php
    $formatRupiah = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $jenisLabel = [
        'peminjaman' => 'Peminjaman',
        'pengembalian' => 'Pengembalian',
        'denda' => 'Pembayaran denda',
    ];
    $jenisClass = [
        'peminjaman' => 'bg-indigo-100 text-indigo-700',
        'pengembalian' => 'bg-blue-100 text-blue-700',
        'denda' => 'bg-amber-100 text-amber-800',
    ];
    $statusOptions = [
        'menunggu validasi' => 'Menunggu validasi',
        'dipinjam' => 'Dipinjam',
        'terlambat' => 'Terlambat',
        'pengembalian diajukan' => 'Pengembalian diajukan',
        'dikembalikan' => 'Dikembalikan',
        'ditolak' => 'Ditolak',
        'dibatalkan' => 'Dibatalkan',
        'hilang' => 'Hilang',
        'selesai' => 'Selesai',
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'diverifikasi' => 'Diverifikasi',
    ];
    $selectedTransaction = $transactions->first();
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 pt-6">
        <div class="max-w-3xl">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Riwayat Transaksi &amp; Audit Sirkulasi</h1>
            <p class="mt-1 text-sm text-slate-600">Rekaman peminjaman, pengembalian, dan pembayaran denda yang tersimpan di database.</p>
        </div>
    </div>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan transaksi">
        @include('petugas.partials.stat-card', [
            'label' => 'Total catatan transaksi',
            'value' => number_format($totalTransactions, 0, ',', '.'),
            'icon' => 'dashboard',
            'tone' => 'blue',
            'footer' => 'Peminjaman, pengembalian, dan pembayaran denda',
        ])
        @include('petugas.partials.stat-card', [
            'label' => 'Peminjaman',
            'value' => number_format($loanCount, 0, ',', '.'),
            'icon' => 'borrow',
            'tone' => 'blue',
            'footer' => 'Seluruh status peminjaman',
        ])
        @include('petugas.partials.stat-card', [
            'label' => 'Pengembalian selesai',
            'value' => number_format($completedReturns, 0, ',', '.'),
            'icon' => 'return',
            'tone' => 'green',
            'footer' => number_format($returnCount, 0, ',', '.') . ' catatan pengembalian',
        ])
        @include('petugas.partials.stat-card', [
            'label' => 'Denda terverifikasi',
            'value' => $formatRupiah($paidAmount),
            'icon' => 'cash',
            'tone' => 'blue',
            'valueTone' => 'text-blue-700',
            'footer' => number_format($verifiedPaymentCount, 0, ',', '.') . ' pembayaran terverifikasi',
        ])
    </section>

    <div class="mt-6 grid gap-6 min-[1400px]:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="min-w-0 space-y-4" aria-labelledby="judul-tabel">
            <h2 id="judul-tabel" class="sr-only">Daftar transaksi</h2>
            <form method="GET" action="{{ route('petugas.transaksi') }}" class="rounded-2xl bg-white p-4 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="flex flex-wrap gap-2 text-sm font-semibold">
                    @foreach (['' => 'Semua transaksi', 'peminjaman' => 'Peminjaman', 'pengembalian' => 'Pengembalian', 'denda' => 'Pembayaran denda'] as $value => $label)
                        <a href="{{ route('petugas.transaksi', array_merge(request()->except('page', 'jenis'), $value !== '' ? ['jenis' => $value] : [])) }}"
                           @class([
                               'rounded-xl px-4 py-2.5',
                               'bg-blue-700 text-white' => $filters['jenis'] === $value,
                               'bg-[#EEF1F8] text-slate-600 hover:bg-blue-50' => $filters['jenis'] !== $value,
                           ]) aria-current="{{ $filters['jenis'] === $value ? 'page' : 'false' }}">
                            {{ $label }}
                            <span class="ml-1 text-xs opacity-80">
                                ({{ number_format(match ($value) {
                                    'peminjaman' => $loanCount,
                                    'pengembalian' => $returnCount,
                                    'denda' => $paymentCount,
                                    default => $totalTransactions,
                                }, 0, ',', '.') }})
                            </span>
                        </a>
                    @endforeach
                </div>

                <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="relative sm:col-span-2 xl:col-span-2">
                        <span class="sr-only">Cari transaksi</span>
                        <span class="pointer-events-none absolute inset-y-0 left-3 grid place-items-center text-slate-400">@include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-4'])</span>
                        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari anggota, buku, ID transaksi"
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] py-2.5 pl-10 pr-4 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <label>
                        <span class="sr-only">Filter status</span>
                        <select name="status" class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                            <option value="">Semua status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Tanggal mulai</span>
                        <input type="date" name="dari" value="{{ $filters['dari'] }}" aria-label="Tanggal mulai"
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <label>
                        <span class="sr-only">Tanggal akhir</span>
                        <input type="date" name="sampai" value="{{ $filters['sampai'] }}" aria-label="Tanggal akhir"
                               class="w-full rounded-xl border-0 bg-[#EEF1F8] px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                    </label>
                    <div class="flex gap-2 sm:col-span-2 xl:col-span-5">
                        @if ($filters['jenis'] !== '')
                            <input type="hidden" name="jenis" value="{{ $filters['jenis'] }}">
                        @endif
                        <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan filter</button>
                        <a href="{{ route('petugas.transaksi') }}" class="rounded-xl bg-[#EEF1F8] px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Reset</a>
                    </div>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl bg-white shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
                <div class="overflow-x-auto">
                    <table id="tabel-transaksi" class="w-full min-w-[56rem] text-left text-sm">
                        <thead class="bg-[#EEF3FC] text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th scope="col" class="px-5 py-3">ID transaksi &amp; referensi</th>
                                <th scope="col" class="px-3 py-3">Jenis &amp; waktu</th>
                                <th scope="col" class="px-3 py-3">Anggota</th>
                                <th scope="col" class="px-3 py-3">Buku terkait</th>
                                <th scope="col" class="px-3 py-3">Petugas</th>
                                <th scope="col" class="px-3 py-3 pr-5">Status / nilai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($transactions as $transaction)
                                @php
                                    $prefix = ['peminjaman' => 'PMJ', 'pengembalian' => 'PGM', 'denda' => 'DND'][$transaction->category];
                                    $referencePrefix = $transaction->category === 'denda' ? 'TAG' : 'PMJ';
                                    $occurredAt = $transaction->occurred_at
                                        ? \Illuminate\Support\Carbon::parse($transaction->occurred_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB'
                                        : 'Waktu tidak tersedia';
                                    $statusLabel = ucfirst(str_replace('_', ' ', $transaction->status));
                                    $detailsLabel = match ($transaction->category) {
                                        'peminjaman' => $transaction->details ? 'Jatuh tempo ' . $transaction->details : 'Belum ada tanggal jatuh tempo',
                                        'pengembalian' => 'Kondisi: ' . $transaction->details,
                                        default => 'Metode: ' . ucfirst($transaction->details),
                                    };
                                @endphp
                                <tr class="cursor-pointer align-top hover:bg-[#F7F9FD] focus-within:bg-blue-50"
                                    tabindex="0"
                                    aria-label="Lihat detail transaksi {{ $prefix }}-{{ $transaction->source_id }}"
                                    data-transaction-id="{{ $prefix }}-{{ $transaction->source_id }}"
                                    data-reference-id="{{ $referencePrefix }}-{{ $transaction->reference_id }}"
                                    data-type="{{ $jenisLabel[$transaction->category] }}"
                                    data-occurred-at="{{ $occurredAt }}"
                                    data-member="{{ $transaction->member_name }}"
                                    data-member-id="{{ $transaction->member_id }}"
                                    data-book="{{ $transaction->book_title }}"
                                    data-book-id="{{ $transaction->book_id }}"
                                    data-staff="{{ $transaction->staff_name }}"
                                    data-staff-id="{{ $transaction->staff_id }}"
                                    data-status="{{ $statusLabel }}"
                                    data-details="{{ $detailsLabel }}"
                                    data-amount="{{ $formatRupiah($transaction->amount) }}">
                                    <td class="px-5 py-4">
                                        <p class="font-bold text-blue-700">{{ $prefix }}-{{ $transaction->source_id }}</p>
                                        <p class="text-xs text-slate-500">Ref: {{ $referencePrefix }}-{{ $transaction->reference_id }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <span class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $jenisClass[$transaction->category] }}">{{ $jenisLabel[$transaction->category] }}</span>
                                        <p class="mt-1 text-xs text-slate-500">{{ $occurredAt }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-semibold">{{ $transaction->member_name }}</p>
                                        <p class="text-xs text-slate-500">ID anggota: {{ $transaction->member_id }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p>{{ $transaction->book_title }}</p>
                                        <p class="text-xs text-slate-500">ID buku: {{ $transaction->book_id }}</p>
                                    </td>
                                    <td class="px-3 py-4">
                                        <p class="font-medium">{{ $transaction->staff_name }}</p>
                                        <p class="text-xs text-slate-500">{{ $transaction->staff_id !== '-' ? 'ID petugas: ' . $transaction->staff_id : 'Belum divalidasi petugas' }}</p>
                                    </td>
                                    <td class="px-3 py-4 pr-5">
                                        <p class="font-semibold">{{ $statusLabel }}</p>
                                        <p @class(['mt-1 font-bold', 'text-red-600' => (float) $transaction->amount > 0])>{{ $formatRupiah($transaction->amount) }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center">
                                        <p class="font-semibold text-slate-700">Belum ada transaksi yang sesuai.</p>
                                        <p class="mt-1 text-sm text-slate-500">Coba ubah filter atau periksa kembali setelah transaksi tercatat.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $transactions->links() }}
                </div>
            </div>
        </section>

        <aside class="min-w-0 space-y-6" aria-label="Detail transaksi dan aktivitas petugas">
            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-inspeksi">
                <h2 id="judul-inspeksi" class="flex items-center gap-2 text-base font-extrabold">
                    @include('petugas.partials.icon', ['name' => 'search', 'class' => 'size-5 text-blue-700']) Detail Transaksi
                </h2>
                <p class="mt-1 text-xs text-slate-500">Pilih baris transaksi untuk melihat detailnya.</p>
                @if ($selectedTransaction)
                    <div class="mt-4 rounded-xl bg-[#EEF3FC] p-4 text-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ID transaksi</p>
                        <p id="in-id" class="font-bold text-blue-700">{{ ['peminjaman' => 'PMJ', 'pengembalian' => 'PGM', 'denda' => 'DND'][$selectedTransaction->category] }}-{{ $selectedTransaction->source_id }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Anggota</p>
                        <p id="in-nama" class="font-bold">{{ $selectedTransaction->member_name }}</p>
                        <p id="in-anggota-id" class="text-xs text-slate-500">ID anggota: {{ $selectedTransaction->member_id }}</p>
                        <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Buku</p>
                        <p id="in-judul" class="font-semibold">{{ $selectedTransaction->book_title }}</p>
                        <p id="in-buku-id" class="text-xs text-slate-500">ID buku: {{ $selectedTransaction->book_id }}</p>
                    </div>
                    <dl class="mt-4 space-y-2 text-sm">
                        @php($selectedReferencePrefix = $selectedTransaction->category === 'denda' ? 'TAG' : 'PMJ')
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Jenis</dt><dd id="in-jenis" class="text-right font-semibold">{{ $jenisLabel[$selectedTransaction->category] }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Referensi</dt><dd id="in-referensi" class="text-right font-semibold">{{ $selectedReferencePrefix }}-{{ $selectedTransaction->reference_id }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Waktu</dt><dd id="in-waktu" class="text-right font-semibold">{{ $selectedTransaction->occurred_at ? \Illuminate\Support\Carbon::parse($selectedTransaction->occurred_at)->timezone('Asia/Jakarta')->format('d/m/Y H:i') . ' WIB' : 'Waktu tidak tersedia' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Status</dt><dd id="in-status" class="text-right font-semibold">{{ ucfirst(str_replace('_', ' ', $selectedTransaction->status)) }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Keterangan</dt><dd id="in-ket" class="text-right font-semibold">
                            @if ($selectedTransaction->category === 'peminjaman')
                                {{ $selectedTransaction->details ? 'Jatuh tempo ' . $selectedTransaction->details : 'Belum ada tanggal jatuh tempo' }}
                            @elseif ($selectedTransaction->category === 'pengembalian')
                                Kondisi: {{ $selectedTransaction->details }}
                            @else
                                Metode: {{ ucfirst($selectedTransaction->details) }}
                            @endif
                        </dd></div>
                    </dl>
                    <div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm">
                        <div class="flex items-center justify-between gap-3 text-xs"><span class="font-semibold text-amber-800">Nilai transaksi</span><b id="in-nilai" class="text-lg text-amber-800">{{ $formatRupiah($selectedTransaction->amount) }}</b></div>
                    </div>
                    <p class="mt-4 rounded-xl bg-[#F3F5FA] p-3 text-xs text-slate-600">
                        Petugas: <b id="in-petugas" class="text-[#0F1B3D]">{{ $selectedTransaction->staff_name }}</b>
                        <span id="in-petugas-id">{{ $selectedTransaction->staff_id !== '-' ? '(ID ' . $selectedTransaction->staff_id . ')' : '(belum divalidasi)' }}</span>
                    </p>
                @else
                    <p class="mt-4 rounded-xl bg-[#F3F5FA] p-4 text-sm text-slate-600">Detail transaksi akan tampil setelah ada catatan transaksi yang sesuai.</p>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100" aria-labelledby="judul-aktivitas-petugas">
                <div class="flex items-center justify-between gap-2">
                    <h2 id="judul-aktivitas-petugas" class="flex items-center gap-2 text-base font-extrabold">
                        @include('petugas.partials.icon', ['name' => 'id-card', 'class' => 'size-5 text-blue-700']) Aktivitas Petugas Hari Ini
                    </h2>
                    <span class="text-xs text-slate-500">{{ now('Asia/Jakarta')->format('d/m/Y') }}</span>
                </div>
                <ul class="mt-4 space-y-3 text-sm">
                    @forelse ($petugasHariIni as $petugas)
                        <li class="rounded-xl bg-[#EEF3FC] p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-semibold">{{ $petugas->nama_petugas }}</p>
                                <span class="rounded-md bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">Petugas</span>
                            </div>
                            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-600">
                                <span>Pinjam: {{ $petugas->peminjaman_hari_ini }}</span>
                                <span>Kembali: {{ $petugas->pengembalian_hari_ini }}</span>
                                <span>Denda: {{ $petugas->denda_hari_ini }}</span>
                            </p>
                        </li>
                    @empty
                        <li class="rounded-xl bg-[#F3F5FA] p-3 text-sm text-slate-600">Belum ada data petugas.</li>
                    @endforelse
                </ul>
            </section>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const rows = document.querySelectorAll('#tabel-transaksi tbody tr[data-transaction-id]');

            const showTransaction = (row) => {
                const data = row.dataset;
                const set = (id, value) => {
                    const element = document.getElementById(id);
                    if (element) element.textContent = value;
                };

                set('in-id', data.transactionId);
                set('in-nama', data.member);
                set('in-anggota-id', `ID anggota: ${data.memberId}`);
                set('in-judul', data.book);
                set('in-buku-id', `ID buku: ${data.bookId}`);
                set('in-jenis', data.type);
                set('in-referensi', data.referenceId);
                set('in-waktu', data.occurredAt);
                set('in-status', data.status);
                set('in-ket', data.details);
                set('in-nilai', data.amount);
                set('in-petugas', data.staff);
                set('in-petugas-id', data.staffId === '-' ? '(belum divalidasi)' : `(ID ${data.staffId})`);

                rows.forEach((item) => item.classList.toggle('bg-blue-50/60', item === row));
            };

            rows.forEach((row) => {
                row.addEventListener('click', () => showTransaction(row));
                row.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        showTransaction(row);
                    }
                });
            });
        })();
    </script>
@endpush
