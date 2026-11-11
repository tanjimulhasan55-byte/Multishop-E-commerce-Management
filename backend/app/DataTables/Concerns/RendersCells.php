<?php

namespace App\DataTables\Concerns;

use Illuminate\Support\Facades\Blade;

/**
 * Cells are rendered through Blade (which escapes user content), then
 * declared as raw columns for DataTables.
 */
trait RendersCells
{
    protected function blade(string $template, array $data = []): string
    {
        return Blade::render($template, $data);
    }

    protected function badge(string $label, string $color): string
    {
        return $this->blade('<x-admin.badge :color="$color">{{ $label }}</x-admin.badge>', compact('label', 'color'));
    }

    protected function media(?string $url, string $title, ?string $subtitle = null, string $fallbackIcon = 'photo'): string
    {
        return $this->blade(<<<'BLADE'
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 ring-1 ring-slate-200">
                    @if ($url)<img src="{{ $url }}" alt="" class="h-full w-full object-cover" loading="lazy">@else<x-icon :name="$icon" class="h-5 w-5 text-slate-300" />@endif
                </div>
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ $title }}</p>
                    @if ($subtitle)<p class="truncate text-xs text-slate-500">{{ $subtitle }}</p>@endif
                </div>
            </div>
        BLADE, ['url' => $url, 'title' => $title, 'subtitle' => $subtitle, 'icon' => $fallbackIcon]);
    }

    protected function actions(?string $show = null, ?string $edit = null, ?string $delete = null, string $confirm = 'Delete this item?'): string
    {
        return $this->blade('<x-admin.row-actions :show="$show" :edit="$edit" :delete="$delete" :confirm="$confirm" />',
            compact('show', 'edit', 'delete', 'confirm'));
    }

    protected function money(mixed $amount): string
    {
        return '<span class="whitespace-nowrap font-semibold tabular-nums text-slate-900">৳ '.e(number_format((float) $amount, 2)).'</span>';
    }

    protected function date(?\DateTimeInterface $date): string
    {
        return $date ? '<span class="whitespace-nowrap text-slate-500">'.e($date->format('d M Y')).'</span>' : '—';
    }

    protected function likeTerm(string $keyword): string
    {
        return '%'.addcslashes($keyword, '%_\\').'%';
    }
}
