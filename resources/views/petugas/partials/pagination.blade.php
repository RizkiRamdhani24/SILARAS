@php
    // Parameter: from, to, total, noun, last?  (tampilan contoh; ganti dengan $items->links() setelah backend siap)
    $terakhir = $last ?? 10;
    $halaman = $terakhir <= 3 ? range(1, $terakhir) : [1, 2, 3, '…', $terakhir];
@endphp
<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4 text-sm text-slate-500">
    <p>Menampilkan <b class="text-[#0F1B3D]">{{ $from }} - {{ $to }}</b> dari <b class="text-[#0F1B3D]">{{ $total }}</b> {{ $noun }}</p>
    <nav class="flex items-center gap-1" aria-label="Halaman">
        <button type="button" class="grid size-9 place-items-center rounded-lg text-slate-400" aria-label="Sebelumnya" disabled>
            @include('petugas.partials.icon', ['name' => 'chevron-left', 'class' => 'size-4'])
        </button>
        @foreach ($halaman as $p)
            @if ($p === '…')
                <span class="px-1">…</span>
            @else
                <button type="button" @if ($p === 1) aria-current="page" @endif
                        @class([
                            'grid size-9 place-items-center rounded-lg text-sm font-semibold',
                            'bg-blue-700 text-white' => $p === 1,
                            'bg-[#EEF3FC] text-slate-600 hover:bg-blue-50' => $p !== 1,
                        ])>{{ $p }}</button>
            @endif
        @endforeach
        <button type="button" class="grid size-9 place-items-center rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Berikutnya">
            @include('petugas.partials.icon', ['name' => 'chevron-right', 'class' => 'size-4'])
        </button>
    </nav>
</div>
