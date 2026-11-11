{{-- Drag-and-drop-ish image picker with live preview and "remove" toggle. --}}
@props(['name', 'label' => 'Image', 'current' => null, 'removeName' => null])
<div x-data="{ preview: @js($current), removed: false }">
    <label class="form-label">{{ $label }}</label>
    <div class="flex items-center gap-5">
        <div class="relative flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-slate-100 ring-1 ring-inset ring-slate-200">
            <template x-if="preview && !removed">
                <img :src="preview" alt="" class="h-full w-full object-cover">
            </template>
            <template x-if="!preview || removed">
                <x-icon name="photo" class="h-8 w-8 text-slate-300" />
            </template>
        </div>
        <div class="space-y-2">
            <label class="btn-secondary btn-sm cursor-pointer">
                <x-icon name="photo" class="h-4 w-4" /> Choose image
                <input type="file" name="{{ $name }}" accept="image/jpeg,image/png,image/webp" class="sr-only"
                       @change="const f = $event.target.files[0]; if (f) { preview = URL.createObjectURL(f); removed = false }">
            </label>
            @if ($removeName && $current)
                <label class="flex items-center gap-2 text-xs text-slate-500">
                    <input type="checkbox" name="{{ $removeName }}" value="1" class="form-checkbox" x-model="removed"> Remove current image
                </label>
            @endif
            <p class="text-xs text-slate-500">JPG, PNG or WebP · max 2 MB · min 100×100px</p>
        </div>
    </div>
    @error($name)<p class="form-error">{{ $message }}</p>@enderror
</div>
