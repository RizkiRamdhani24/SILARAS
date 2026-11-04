@php
    // Tombol setujui / tolak untuk satu peminjaman. Parameter: p (App\Models\Peminjaman dengan relasi buku)
    // Memakai route milik Exel (PATCH petugas.peminjaman.setujui / .tolak). Bila route belum digabung ke develop,
    // tombol tidak ditampilkan dan diganti keterangan, supaya halaman tidak error.
    $bisaSetujui = \App\Support\PetugasData::bisa('petugas.peminjaman.setujui');
    $bisaTolak = \App\Support\PetugasData::bisa('petugas.peminjaman.tolak');
    $stokHabis = ($p->buku?->stock ?? 0) < 1;
@endphp
@if ($p->status_peminjaman === \App\Models\Peminjaman::STATUS_MENUNGGU_VALIDASI)
    <div class="flex items-center justify-end gap-2">
        @if ($bisaSetujui)
            <form method="POST" action="{{ route('petugas.peminjaman.setujui', $p->id_peminjaman) }}">
                @csrf
                @method('PATCH')
                <button type="submit" @disabled($stokHabis) @if ($stokHabis) title="Stok buku habis, tidak bisa disetujui" @endif
                        class="flex items-center gap-1.5 rounded-lg bg-blue-700 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                    @include('petugas.partials.icon', ['name' => 'check', 'class' => 'size-3.5']) Setujui
                </button>
            </form>
        @endif
        @if ($bisaTolak)
            <form method="POST" action="{{ route('petugas.peminjaman.tolak', $p->id_peminjaman) }}"
                  onsubmit="return confirm('Tolak pengajuan {{ \App\Support\PetugasData::kode('PMJ', $p->id_peminjaman) }}?')">
                @csrf
                @method('PATCH')
                <button type="submit" class="grid size-8 place-items-center rounded-lg bg-slate-100 text-slate-600 hover:bg-red-50 hover:text-red-600"
                        aria-label="Tolak {{ \App\Support\PetugasData::kode('PMJ', $p->id_peminjaman) }}">
                    @include('petugas.partials.icon', ['name' => 'x', 'class' => 'size-4'])
                </button>
            </form>
        @endif
        @unless ($bisaSetujui || $bisaTolak)
            <span class="text-xs text-slate-400">Aksi menunggu backend peminjaman</span>
        @endunless
    </div>
@else
    <p class="text-right text-xs text-slate-400">Sudah diproses</p>
@endif
