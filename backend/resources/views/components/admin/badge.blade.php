@props(['color' => 'slate', 'dot' => true])
<span {{ $attributes->merge(['class' => "badge bg-{$color}-50 text-{$color}-700 ring-{$color}-600/20"]) }}>
    @if ($dot)<span class="h-1.5 w-1.5 rounded-full bg-{{ $color }}-500"></span>@endif
    {{ $slot }}
</span>
