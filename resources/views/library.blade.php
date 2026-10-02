<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Library | Koleksi terbuka</title>
    <link rel="stylesheet" href="{{ asset('css/library.css') }}">
</head>

<body>
    <header class="topbar"><a class="brand" href="{{ url('/') }}"><span class="brand-mark">⌘</span><span>E-Library</span></a><span class="api-note">Katalog langsung dari API terbuka</span></header>
    <main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">PERPUSTAKAAN DIGITAL · DEMO</p>
                <h1>Cari buku, koleksi Indonesia, dan manga dari API.</h1>
                <p class="lead">Satu ruang baca untuk menjelajah koleksi nyata dari Gutendex, katalog topik Indonesia, dan Komiku.</p>
                <form id="searchForm" class="searchbar"><span aria-hidden="true">⌕</span><input id="search" type="search" placeholder="Cari judul, penulis, subjek..." autocomplete="off"><button type="submit">Cari</button></form>
            </div>
            <div class="hero-art"><span>01</span><strong>Read<br>curiously.</strong><i>↗</i></div>
        </section>
        <nav class="tabs" aria-label="Sumber koleksi"><button class="tab active" data-source="all">Semua</button><button class="tab" data-source="ebook">Ebook</button><button class="tab" data-source="indonesia">Indonesia</button><button class="tab" data-source="manga">Manga</button><button id="reload" class="reload" title="Muat ulang katalog">↻ <span>Muat ulang</span></button></nav>
        <section class="toolbar">
            <div>
                <p class="section-kicker">JELAJAHI KATALOG</p>
                <h2>Koleksi pilihan untuk dibaca</h2>
            </div>
            <div class="filters"><label>Kategori<select id="category">
                        <option value="all">Semua kategori</option>
                        <option value="sejarah">Sejarah</option>
                        <option value="pendidikan">Pendidikan</option>
                        <option value="budaya">Budaya</option>
                        <option value="ekonomi">Ekonomi</option>
                        <option value="hukum">Hukum</option>
                        <option value="sastra">Sastra</option>
                        <option value="sosial">Sosial</option>
                        <option value="geografi">Geografi</option>
                        <option value="lainnya">Lainnya</option>
                    </select></label><label>Tahun<select id="year">
                        <option value="all">Semua tahun</option>
                    </select></label></div>
        </section>
        <div id="status" class="status" role="status">Menghubungkan ke sumber koleksi...</div>
        <div id="errors" class="errors" hidden></div>
        <section id="grid" class="grid" aria-live="polite"></section>
    </main>
    <div id="modal" class="modal" hidden>
        <div class="modal-panel"><button id="closeModal" class="close" aria-label="Tutup">×</button>
            <div id="detail"></div>
        </div>
    </div>
    <script src="{{ asset('js/library.js') }}"></script>
</body>

</html>