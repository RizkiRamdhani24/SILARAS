@extends('layouts.anggota')

@section('title', 'Pembayaran Denda | SILARAS')

@section('content')
    @php
        $nominalTagihan = number_format((float) $tagihan->nominal_tagihan, 2, '.', '');
        $nominalTampilan = 'Rp ' . number_format((float) $tagihan->nominal_tagihan, 0, ',', '.');
        $bisaMembayar = $tagihan->status_tagihan === \App\Models\TagihanDenda::STATUS_BELUM_LUNAS
            && ! $tagihan->pembayaran->contains('status_pembayaran', 'menunggu_verifikasi');
    @endphp

    <section class="denda-payment-page" aria-labelledby="denda-payment-title">
        <a href="{{ route('anggota.denda.index') }}" class="denda-payment-back">
            <span class="material-symbols-rounded member-icon" aria-hidden="true">arrow_back</span>
            Kembali ke tagihan
        </a>

        <header class="denda-payment-heading">
            <div>
                <p class="denda-payment-eyebrow">TAGIHAN PERPUSTAKAAN</p>
                <h1 id="denda-payment-title">Pembayaran Denda</h1>
            </div>
            <span class="denda-payment-status is-{{ $tagihan->status_tagihan === \App\Models\TagihanDenda::STATUS_LUNAS ? 'paid' : 'unpaid' }}">
                <span aria-hidden="true"></span>
                {{ $tagihan->status_tagihan === \App\Models\TagihanDenda::STATUS_LUNAS ? 'Lunas' : 'Belum lunas' }}
            </span>
        </header>

        @if (session('success'))
            <div class="denda-payment-alert is-success" role="status">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">check_circle</span>
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="denda-payment-alert is-error" role="alert">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">error</span>
                <span>Periksa kembali pembayaran Anda. {{ $errors->first() }}</span>
            </div>
        @endif

        <div class="denda-payment-layout">
            <article class="denda-bill-card" aria-labelledby="denda-bill-title">
                <div class="denda-bill-heading">
                    <span class="denda-payment-icon material-symbols-rounded" aria-hidden="true">receipt_long</span>
                    <div>
                        <p>Tagihan #{{ $tagihan->id_tagihan_denda }}</p>
                        <h2 id="denda-bill-title">{{ $tagihan->peminjaman?->buku?->judul_buku ?? 'Buku tidak tersedia' }}</h2>
                    </div>
                </div>

                <dl class="denda-bill-details">
                    <div><dt>Denda terlambat</dt><dd>Rp {{ number_format((float) $tagihan->nominal_terlambat, 0, ',', '.') }}</dd></div>
                    <div><dt>Denda rusak</dt><dd>Rp {{ number_format((float) $tagihan->nominal_rusak, 0, ',', '.') }}</dd></div>
                    <div><dt>Denda hilang</dt><dd>Rp {{ number_format((float) $tagihan->nominal_hilang, 0, ',', '.') }}</dd></div>
                </dl>

                <div class="denda-bill-total">
                    <span>Total tagihan</span>
                    <strong>{{ $nominalTampilan }}</strong>
                </div>
            </article>

            @if ($bisaMembayar)
                <section class="denda-qris-card" aria-labelledby="qris-title">
                    <header class="denda-qris-heading">
                        <h2 id="qris-title">Bayar via QRIS</h2>
                        <p>Pembayaran berstatus pending sampai diverifikasi petugas perpustakaan.</p>
                    </header>

                    <div class="denda-qris-instructions">
                        <div class="denda-qris-image-wrap">
                            <img src="{{ route('anggota.denda.qris', $tagihan->id_tagihan_denda, false) }}" alt="QRIS resmi SILARAS untuk pembayaran denda" width="420" height="560" loading="eager">
                        </div>
                        <div class="denda-qris-steps">
                            <h3>Instruksi pembayaran</h3>
                            <ol>
                                <li><span>1</span><p>Buka aplikasi e-wallet atau m-banking.</p></li>
                                <li><span>2</span><p>Pilih fitur Scan QRIS dan pindai QRIS SILARAS.</p></li>
                                <li><span>3</span><p>Bayar sesuai nominal total tagihan denda.</p></li>
                                <li><span>4</span><p>Simpan bukti pembayaran dan unggah melalui formulir di bawah.</p></li>
                            </ol>
                        </div>
                    </div>

                    <form class="denda-payment-form" method="POST" action="{{ route('anggota.denda.pembayaran.store', $tagihan->id_tagihan_denda) }}" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="metode_pembayaran" value="qris">
                        <input type="hidden" name="jumlah_pembayaran" value="{{ $nominalTagihan }}">

                        <div class="denda-payment-amount">
                            <label for="denda-amount">Nominal pembayaran</label>
                            <div>
                                <span class="material-symbols-rounded member-icon" aria-hidden="true">lock</span>
                                <input id="denda-amount" type="text" value="{{ $nominalTampilan }}" readonly aria-readonly="true">
                            </div>
                            <small>Nominal terkunci sesuai total tagihan.</small>
                        </div>

                        <div class="denda-proof-field">
                            <label for="bukti_pembayaran">Bukti pembayaran</label>
                            <label class="denda-proof-picker" for="bukti_pembayaran">
                                <input id="bukti_pembayaran" name="bukti_pembayaran" type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" required data-payment-proof>
                                <span class="material-symbols-rounded member-icon" aria-hidden="true">cloud_upload</span>
                                <strong>Pilih file bukti</strong>
                                <small>JPG atau PNG, maksimal 2 MB</small>
                                <span class="denda-proof-filename" data-proof-filename>Belum ada file dipilih</span>
                                <img class="denda-proof-preview" data-proof-preview alt="Pratinjau bukti pembayaran" hidden>
                            </label>
                            <small class="denda-proof-error" data-proof-error aria-live="polite"></small>
                            @error('bukti_pembayaran')
                                <small class="denda-proof-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <button class="denda-payment-submit" type="submit">
                            <span class="material-symbols-rounded member-icon" aria-hidden="true">send</span>
                            Kirim Pembayaran
                        </button>
                    </form>
                </section>
            @elseif ($tagihan->pembayaran->contains('status_pembayaran', 'menunggu_verifikasi'))
                <div class="denda-payment-pending" role="status">
                    <span class="material-symbols-rounded member-icon" aria-hidden="true">pending</span>
                    <div>
                        <strong>Pembayaran sedang menunggu verifikasi</strong>
                        <p>Petugas perpustakaan akan memeriksa bukti pembayaran Anda. Pembayaran ulang belum dapat diajukan.</p>
                    </div>
                </div>
            @endif
        </div>

        @if ($tagihan->pembayaran->isNotEmpty())
            <section class="denda-payment-history" aria-labelledby="denda-history-title">
                <h2 id="denda-history-title">Riwayat pembayaran</h2>
                <div class="denda-payment-history-list">
                    @foreach ($tagihan->pembayaran->sortByDesc('id_pembayaran_denda') as $payment)
                        <article>
                            <div>
                                <strong>{{ ucfirst($payment->metode_pembayaran) }} · Rp {{ number_format((float) $payment->jumlah_pembayaran, 0, ',', '.') }}</strong>
                                <p>{{ $payment->created_at?->translatedFormat('d F Y, H:i') ?? 'Waktu tidak tersedia' }}</p>
                                @if ($payment->catatan_petugas)
                                    <p>Catatan petugas: {{ $payment->catatan_petugas }}</p>
                                @endif
                            </div>
                            <span class="denda-payment-status is-{{ $payment->status_pembayaran === 'diverifikasi' ? 'paid' : 'unpaid' }}">
                                {{ str_replace('_', ' ', ucfirst($payment->status_pembayaran)) }}
                            </span>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </section>
@endsection
