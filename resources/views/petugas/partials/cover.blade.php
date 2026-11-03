@php
    // Placeholder sampul buku. Ganti dengan <img> dari kolom sampul buku jika backend sudah menyediakannya.
    // Parameter: i (angka untuk memilih warna), class? (ukuran)
    $warna = [
        'from-blue-700 to-sky-400',
        'from-slate-700 to-slate-400',
        'from-emerald-700 to-teal-400',
        'from-amber-600 to-orange-300',
        'from-indigo-700 to-violet-400',
        'from-rose-600 to-pink-300',
    ];
@endphp
<span class="grid shrink-0 place-items-center rounded-md bg-gradient-to-br {{ $warna[($i ?? 0) % count($warna)] }} text-white shadow-sm {{ $class ?? 'h-14 w-10' }}" aria-hidden="true">
    @include('petugas.partials.icon', ['name' => 'book', 'class' => 'size-4 opacity-80'])
</span>
