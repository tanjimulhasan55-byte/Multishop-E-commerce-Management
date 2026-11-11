@props(['label', 'value', 'icon', 'hint' => null, 'tone' => 'brand'])
@php
    $tones = [
        'brand' => 'from-brand-500 to-violet-600 shadow-brand-500/30',
        'emerald' => 'from-emerald-400 to-teal-600 shadow-emerald-500/30',
        'amber' => 'from-amber-400 to-orange-500 shadow-amber-500/30',
        'sky' => 'from-sky-400 to-blue-600 shadow-sky-500/30',
        'rose' => 'from-rose-400 to-pink-600 shadow-rose-500/30',
    ];
@endphp
<div class="card relative overflow-hidden p-6">
    <div class="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-gradient-to-br {{ $tones[$tone] }} opacity-[0.07]"></div>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
        </div>
        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br {{ $tones[$tone] }} text-white shadow-lg">
            <x-icon :name="$icon" class="h-5 w-5" />
        </div>
    </div>
    @if ($hint)
        <p class="mt-3 text-xs font-medium text-slate-500">{{ $hint }}</p>
    @endif
</div>
