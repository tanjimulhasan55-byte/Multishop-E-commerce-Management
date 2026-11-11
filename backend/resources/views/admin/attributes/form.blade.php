@php
    $editing = $attribute !== null;
    $initialValues = old('values', $attribute?->values->map(fn ($v) => [
        'id' => $v->id,
        'value' => $v->getTranslations('value'),
        'code' => $v->code,
        'color_hex' => $v->color_hex,
    ])->all() ?? [['id' => null, 'value' => ['en' => '', 'bn' => ''], 'code' => '', 'color_hex' => null]]);
@endphp
<x-admin-layout :title="$editing ? 'Edit attribute' : 'New attribute'"
                :breadcrumbs="[['label' => 'Attributes', 'url' => route('admin.attributes.index')], ['label' => $editing ? $attribute->name : 'New']]">
    <x-admin.page-header :title="$editing ? 'Edit '.$attribute->name : 'Create an attribute'"
                         description="Each value's code is used in generated variant SKUs, e.g. TSHIRT-01-BLK-XL.">
        <x-slot:actions>
            <a href="{{ route('admin.attributes.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.attributes.update', $attribute) : route('admin.attributes.store') }}"
          class="grid gap-6 lg:grid-cols-3"
          x-data="{
              type: @js(old('type', $attribute?->type ?? 'select')),
              values: @js(array_values($initialValues)),
              add() { this.values.push({ id: null, value: { en: '', bn: '' }, code: '', color_hex: '#6366F1' }) },
              remove(i) { this.values.splice(i, 1) },
              move(i, d) { const j = i + d; if (j < 0 || j >= this.values.length) return; [this.values[i], this.values[j]] = [this.values[j], this.values[i]] },
          }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card h-fit">
            <div class="card-header"><h2 class="card-title">Attribute</h2></div>
            <div class="card-body space-y-5">
                <x-admin.translatable name="name" label="Name" :value="$attribute?->getTranslations('name') ?? []" required />
                <div>
                    <label class="form-label">Display as</label>
                    <select name="type" x-model="type" class="form-input">
                        <option value="select">Buttons / dropdown (Size, Material…)</option>
                        <option value="color">Colour swatches</option>
                    </select>
                </div>
                <x-admin.input name="sort_order" type="number" min="0" label="Sort order" :value="$attribute?->sort_order ?? 0"
                               hint="Lower comes first in SKUs and pickers." />
            </div>
        </div>

        <div class="card lg:col-span-2">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Values</h2>
                    <p class="text-xs text-slate-500">Drag order with the arrows. Values used by variants can't be removed.</p>
                </div>
                <button type="button" class="btn-secondary btn-sm" @click="add()"><x-icon name="plus" class="h-4 w-4" /> Add value</button>
            </div>
            <div class="card-body space-y-3">
                @error('values')<p class="form-error !mt-0">{{ $message }}</p>@enderror
                <template x-for="(v, i) in values" :key="i">
                    <div class="grid grid-cols-12 items-center gap-3 rounded-2xl bg-slate-50/80 p-3 ring-1 ring-slate-100">
                        <input type="hidden" :name="`values[${i}][id]`" :value="v.id ?? ''">
                        <div class="col-span-12 sm:col-span-4">
                            <input type="text" class="form-input" placeholder="Value (English) e.g. XL" required
                                   :name="`values[${i}][value][en]`" x-model="v.value.en">
                        </div>
                        <div class="col-span-12 sm:col-span-3">
                            <input type="text" class="form-input" placeholder="বাংলা (ঐচ্ছিক)"
                                   :name="`values[${i}][value][bn]`" x-model="v.value.bn">
                        </div>
                        <div class="col-span-5 sm:col-span-2">
                            <input type="text" class="form-input font-mono uppercase" placeholder="Code" maxlength="12"
                                   :name="`values[${i}][code]`" x-model="v.code" title="SKU code (auto from value if empty)">
                        </div>
                        <div class="col-span-3 sm:col-span-1" x-show="type === 'color'">
                            <input type="color" class="h-10 w-full cursor-pointer rounded-xl border-0 bg-white p-1 ring-1 ring-slate-200"
                                   :name="type === 'color' ? `values[${i}][color_hex]` : ''" x-model="v.color_hex">
                        </div>
                        <div class="col-span-4 flex justify-end gap-1 sm:col-span-2" :class="type !== 'color' && 'sm:col-span-3'">
                            <button type="button" class="btn-icon" @click="move(i, -1)" title="Move up">↑</button>
                            <button type="button" class="btn-icon" @click="move(i, 1)" title="Move down">↓</button>
                            <button type="button" class="btn-icon-danger" @click="remove(i)" title="Remove"><x-icon name="trash" class="h-4 w-4" /></button>
                        </div>
                    </div>
                </template>
                @foreach ($errors->getMessages() as $key => $messages)
                    @if (str_starts_with($key, 'values.'))<p class="form-error">{{ $messages[0] }}</p>@endif
                @endforeach
            </div>
            <div class="flex justify-end gap-3 border-t border-slate-100 px-6 py-4">
                <a href="{{ route('admin.attributes.index') }}" class="btn-secondary">Cancel</a>
                <button class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $editing ? 'Save changes' : 'Create attribute' }}</button>
            </div>
        </div>
    </form>
</x-admin-layout>
