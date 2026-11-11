{{-- Boolean switch that always submits 0/1 (hidden fallback + checkbox). --}}
@props(['name', 'label', 'checked' => false, 'hint' => null])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
<div x-data="{ on: @js((bool) old($key, $checked)) }">
    <input type="hidden" name="{{ $name }}" :value="on ? 1 : 0">
    <button type="button" role="switch" :aria-checked="on" @click="on = !on" class="flex items-center gap-3 text-left">
        <span class="relative inline-flex h-6 w-11 shrink-0 rounded-full transition-colors" :class="on ? 'bg-brand-600' : 'bg-slate-200'">
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="on && 'translate-x-5'"></span>
        </span>
        <span>
            <span class="block text-sm font-medium text-slate-700">{{ $label }}</span>
            @if ($hint)<span class="block text-xs text-slate-500">{{ $hint }}</span>@endif
        </span>
    </button>
</div>
