@php
    // Validasi satu pengembalian. Parameter: k (App\Models\Pengembalian)
    // Memakai route milik Exel (PATCH petugas.pengembalian.setujui) dengan isian kondisi_buku: baik | rusak | hilang.
    // Bila route belum digabung ke develop, form diganti keterangan supaya halaman tidak error.
    $bisaValidasi = \App\Support\PetugasData::bisa('petugas.pengembalian.setujui');
@endphp
@if ($k->status_pengembalian === \App\Models\Pengembalian::STATUS_MENUNGGU_VALIDASI)
    @if ($bisaValidasi)
        <form method="POST" action="{{ route('petugas.pengembalian.setujui', $k->id_pengembalian) }}" class="flex flex-wrap items-center justify-end gap-2">
            @csrf
            @method('PATCH')
            <label class="sr-only" for="kondisi-{{ $k->id_pengembalian }}">Kondisi buku</label>
            <select id="kondisi-{{ $k->id_pengembalian }}" name="kondisi_buku" required
                    class="rounded-lg border-0 bg-[#EEF1F8] py-2 pl-3 pr-8 text-xs font-medium focus:ring-2 focus:ring-blue-600 focus:outline-none">
                <option value="baik">Baik (stok +1)</option>
                <option value="rusak">Rusak</option>
                <option value="hilang">Hilang</option>
            </select>
            <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800">
                @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-3.5']) Validasi
            </button>
        </form>
    @else
        <p class="text-right text-xs text-slate-400">Aksi menunggu backend pengembalian</p>
    @endif
@else
    <p class="text-right text-xs text-slate-500">
        Divalidasi{{ $k->petugas ? ' oleh '.$k->petugas->nama_petugas : '' }}
        @if ($k->kondisi_buku)<br>Kondisi: <b class="text-slate-700">{{ ucfirst($k->kondisi_buku) }}</b>@endif
    </p>
@endif
