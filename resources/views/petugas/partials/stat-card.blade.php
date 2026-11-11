@php
    // Parameter: label, value, icon, tone (blue|red|amber|green), suffix?, footer?, footerTone?, valueTone?, labelTone?
    $tones = [
        'blue' => ['bg-blue-50', 'text-blue-700'],
        'red' => ['bg-red-50', 'text-red-600'],
        'amber' => ['bg-amber-50', 'text-amber-600'],
        'green' => ['bg-emerald-50', 'text-emerald-600'],
    ];
    [$iconBg, $iconFg] = $tones[$tone ?? 'blue'];
@endphp
<div class="rounded-2xl bg-white p-5 shadow-[0_1px_3px_rgba(15,27,61,0.06)] ring-1 ring-slate-100">
    <div class="flex items-start justify-between gap-3">
        <p class="text-xs font-semibold uppercase tracking-wide {{ $labelTone ?? 'text-slate-500' }}">{{ $label }}</p>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $iconBg }} {{ $iconFg }}">
            @include('petugas.partials.icon', ['name' => $icon])
        </span>
    </div>
    <p class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-extrabold tracking-tight {{ $valueTone ?? 'text-[#0F1B3D]' }}">{{ $value }}</span>
        @isset($suffix)
            <span class="text-sm font-medium text-slate-500">{{ $suffix }}</span>
        @endisset
    </p>
    @isset($footer)
        <p class="mt-3 text-xs {{ $footerTone ?? 'text-slate-500' }}">{{ $footer }}</p>
    @endisset
</div>
