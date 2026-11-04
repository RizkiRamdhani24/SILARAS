@if (request()->routeIs('home'))
    <footer class="silaras-landing-footer">
        <div class="silaras-landing-footer-main">
            <div class="silaras-landing-footer-about">
                <a href="{{ route('home') }}" class="silaras-landing-footer-brand">
                    <img src="{{ asset('images/silaras-logo.png') }}" alt="">
                    <span><strong>SILARAS</strong><small>Pustaka Akademik Digital</small></span>
                </a>
                <p>Menghubungkan civitas akademika dengan sumber ilmu pengetahuan dan literatur melalui layanan pustaka yang mudah diakses.</p>
            </div>
            <nav aria-label="Pustaka">
                <strong>PUSTAKA</strong>
                <a href="#koleksi">Katalog Buku</a>
                <a href="#kategori">Jenis Koleksi</a>
                <a href="#koleksi">Koleksi Referensi</a>
            </nav>
            <nav aria-label="Bantuan">
                <strong>BANTUAN</strong>
                <a href="#faq">Pusat FAQ</a>
                <a href="#faq">Panduan Anggota</a>
                <a href="#kontak">Hubungi Kami</a>
            </nav>
            <nav aria-label="Kebijakan">
                <strong>KEBIJAKAN</strong>
                <a href="#faq">Syarat &amp; Ketentuan</a>
                <a href="#faq">Kebijakan Privasi</a>
            </nav>
        </div>
        <div class="silaras-landing-footer-bottom">
            <span>© {{ now()->year }} SILARAS - Pustaka Akademik Kampus. Hak cipta dilindungi.</span>
            <a href="#kontak" aria-label="Informasi kontak">Kontak Perpustakaan</a>
        </div>
    </footer>
@else
    <footer class="silaras-auth-footer">
        <div class="silaras-auth-footer-brand">
            <img class="silaras-auth-footer-logo" src="{{ asset('images/silaras-logo.png') }}" alt="Logo SILARAS">
            <div>
                <p class="silaras-auth-footer-title">SILARAS Smart Library Portal</p>
                <p class="silaras-auth-footer-copy">© 2024 SILARAS. Hak Cipta Dilindungi Undang-Undang.</p>
            </div>
        </div>

        <nav class="silaras-auth-footer-links" aria-label="Tautan informasi">
            <a href="#">Panduan Keanggotaan</a>
            <a href="#">Kebijakan Privasi</a>
            <a href="{{ route('home') }}#faq">FAQ</a>
        </nav>
    </footer>

    <a class="silaras-auth-support" href="#" aria-label="Bantuan langsung Pustakawan WA">
        <span class="silaras-auth-support-icon" aria-hidden="true">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none">
                <path d="M5 5.75h14a1.75 1.75 0 0 1 1.75 1.75v9A1.75 1.75 0 0 1 19 18.25h-8.5L6 21v-2.75h-1A1.75 1.75 0 0 1 3.25 16.5v-9A1.75 1.75 0 0 1 5 5.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                <path d="M7.5 10h9M7.5 13.5h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            </svg>
        </span>
        <span class="silaras-auth-support-copy">
            <span class="silaras-auth-support-label">BANTUAN LANGSUNG</span>
            <span class="silaras-auth-support-name">Pustakawan WA</span>
        </span>
        <span class="silaras-auth-support-status" aria-hidden="true"></span>
    </a>
@endif
