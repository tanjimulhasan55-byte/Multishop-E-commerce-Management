{{-- Card with optional title / description / header actions / footer. --}}
@props(['title' => null, 'description' => null, 'padded' => true])
<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <div>
                @if ($title)<h2 class="card-title">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div @class(['card-body' => $padded])>{{ $slot }}</div>
    @isset($footer)
        <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">{{ $footer }}</div>
    @endisset
</div>
