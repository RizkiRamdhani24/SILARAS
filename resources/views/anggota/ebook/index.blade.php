@extends('layouts.anggota')

@section('title', 'Ebook | SILARAS')

@section('content')
    <section class="catalog-hero" aria-labelledby="ebook-title">
        <div>
            <span class="catalog-eyebrow"><span class="material-symbols-rounded member-icon" aria-hidden="true">auto_stories</span> Koleksi digital anggota</span>
            <h1 id="ebook-title">Ebook</h1>
            <p>Temukan dan unduh ebook yang tersedia di SILARAS.</p>
        </div>
    </section>

    <section class="catalog-toolbar" aria-label="Pencarian ebook">
        <form action="{{ route('anggota.ebook.index') }}" method="GET" role="search">
            <label class="catalog-search">
                <span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>
                <span class="sr-only">Cari ebook</span>
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari judul atau penulis ebook...">
            </label>
            <button class="button button-primary" type="submit"><span class="material-symbols-rounded member-icon" aria-hidden="true">search</span>Cari Ebook</button>
        </form>
    </section>

    <section aria-label="Daftar ebook">
        @if ($ebooks->isNotEmpty())
            <div class="catalog-grid">
                @foreach ($ebooks as $ebook)
                    <article class="catalog-card">
                        <div class="catalog-cover">
                            @if ($ebook->cover)
                                <img src="{{ $ebook->cover }}" alt="Sampul ebook {{ $ebook->judul }}" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                                <div class="catalog-cover-placeholder" hidden aria-label="Sampul ebook tidak tersedia">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">auto_stories</span>
                                    <span>Ebook</span>
                                </div>
                            @else
                                <div class="catalog-cover-placeholder" aria-label="Sampul ebook tidak tersedia">
                                    <span class="material-symbols-rounded member-icon" aria-hidden="true">auto_stories</span>
                                    <span>Ebook</span>
                                </div>
                            @endif
                        </div>
                        <div class="catalog-card-body">
                            <h2>{{ $ebook->judul }}</h2>
                            <p>{{ $ebook->penulis }}</p>
                            <a class="catalog-detail" href="{{ route('anggota.ebook.unduh', $ebook->getKey()) }}">
                                <span class="material-symbols-rounded member-icon" aria-hidden="true">download</span>Unduh
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <p>Belum ada ebook yang tersedia.</p>
        @endif
    </section>
@endsection
