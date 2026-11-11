@php($editing = $product !== null)
<x-admin-layout :title="$editing ? 'Edit product' : 'New product'"
                :breadcrumbs="[['label' => 'Products', 'url' => route('admin.products.index')], ['label' => $editing ? $product->name : 'New']]">
    <x-admin.page-header :title="$editing ? $product->name : 'Add a product'"
                         :description="$editing ? 'SKU '.$product->sku.' · created by '.($product->creator?->name ?? '—') : 'New products join the shared catalog. Shops then list them at their own price.'">
        <x-slot:actions>
            <a href="{{ route('admin.products.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="General">
                <div class="space-y-5">
                    <x-admin.translatable name="name" label="Product name" :value="$product?->getTranslations('name') ?? []" required />
                    <x-admin.translatable name="description" label="Description" textarea rows="6" :value="$product?->getTranslations('description') ?? []" />
                </div>
            </x-admin.card>
            <x-admin.card title="Pricing & identification">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="base_price" type="number" step="0.01" min="0" label="Base price (৳)" :value="$product?->base_price" required
                                   hint="Suggested retail price. Each shop sets its own selling price." />
                    <x-admin.input name="sku" label="Base SKU" :value="$product?->sku" placeholder="Auto-generated if empty" class="uppercase"
                                   hint="Variant SKUs are built from it, e.g. TSHIRT-01-BLK-XL." />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Organisation">
                <div class="space-y-5">
                    <x-admin.select name="status" label="Status" :options="$statuses" :value="$product?->status->value ?? 'active'" required
                                    hint="Only active products can be listed and seen by customers." />
                    <x-admin.select name="category_id" label="Category" :options="$categories" :value="$product?->category_id" placeholder="— None —" tom />
                </div>
            </x-admin.card>
            <x-admin.card>
                <x-admin.image-upload name="thumbnail" label="Thumbnail" :current="$product?->thumbnailUrls()['thumb'] ?? null" remove-name="remove_thumbnail" />
            </x-admin.card>
            @if ($editing)
                <x-admin.card title="Listed by" :description="$product->listings_count.' shop(s)'">
                    <div class="space-y-2">
                        @forelse ($product->listings as $listing)
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-slate-700">{{ $listing->shop?->name }}</span>
                                <span class="tabular-nums text-slate-500">from ৳ {{ number_format((float) $listing->price, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">Not listed by any shop yet — customers can't see it.</p>
                        @endforelse
                    </div>
                </x-admin.card>
            @endif
            <x-admin.form-actions :cancel="route('admin.products.index')" :label="$editing ? 'Save changes' : 'Create product'" />
        </div>
    </form>

    @if ($editing)
        @php($usedValueIds = $product->variants->flatMap(fn ($v) => $v->attributeValues->pluck('id'))->unique()->all())
        <section id="variants" class="mt-8 grid gap-6 lg:grid-cols-3">
            {{-- Generator --}}
            <form method="POST" action="{{ route('admin.products.variants.generate', $product) }}" class="card lg:col-span-1">
                @csrf
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Generate variants</h2>
                        <p class="text-xs text-slate-500">Pick options — every combination gets its own SKU.</p>
                    </div>
                </div>
                <div class="card-body space-y-5">
                    @forelse ($attributes as $attribute)
                        <div>
                            <p class="mb-2 text-sm font-semibold text-slate-700">{{ $attribute->name }}</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($attribute->values as $value)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="attribute_value_ids[]" value="{{ $value->id }}" class="peer sr-only"
                                               @checked(in_array($value->id, old('attribute_value_ids', $usedValueIds)))>
                                        <span class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-sm font-medium ring-1 ring-inset ring-slate-200 transition
                                                     hover:bg-slate-50 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-checked:ring-brand-500">
                                            @if ($value->color_hex)
                                                <span class="h-3.5 w-3.5 rounded-full ring-1 ring-slate-300" style="background: {{ $value->color_hex }}"></span>
                                            @endif
                                            {{ $value->value }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No attributes yet. <a href="{{ route('admin.attributes.create') }}" class="font-medium text-brand-600">Create one</a> (e.g. Size, Color).</p>
                    @endforelse
                    @error('attribute_value_ids')<p class="form-error">{{ $message }}</p>@enderror

                    <x-admin.input name="base_price" type="number" step="0.01" min="0" label="Base price for new variants (৳)"
                                   placeholder="{{ $product->base_price }}" hint="Leave empty to use the product base price." id="variant_base_price" />
                    <button class="btn-primary w-full"><x-icon name="sparkles" class="h-4 w-4" /> Generate combinations</button>
                    <p class="text-xs text-slate-500">Existing combinations are kept. Shops already selling this product get the new variants at zero stock.</p>
                </div>
            </form>

            {{-- Variant table --}}
            <div class="card overflow-hidden lg:col-span-2">
                <div class="card-header">
                    <h2 class="card-title">Variants</h2>
                    <x-admin.badge color="violet">{{ $product->variants->count() }} SKU{{ $product->variants->count() === 1 ? '' : 's' }}</x-admin.badge>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Options</th>
                                <th class="px-6 py-3">SKU</th>
                                <th class="px-6 py-3">Base price</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($product->variants as $variant)
                                <tr class="align-middle">
                                    <td class="px-6 py-3">
                                        @if ($variant->isDefault())
                                            <span class="text-slate-500">Default (no options)</span>
                                        @else
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach ($variant->options() as $option)
                                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                        @if ($option['color_hex'])<span class="h-2.5 w-2.5 rounded-full ring-1 ring-slate-300" style="background: {{ $option['color_hex'] }}"></span>@endif
                                                        {{ $option['value'] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3" colspan="2">
                                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}" class="flex items-center gap-2">
                                            @csrf @method('PATCH')
                                            <input name="sku" value="{{ $variant->sku }}" class="form-input w-48 py-1.5 font-mono text-xs uppercase" required>
                                            <input name="base_price" type="number" step="0.01" min="0" value="{{ $variant->base_price }}" class="form-input w-28 py-1.5 tabular-nums" required>
                                            <button class="btn-icon" title="Save"><x-icon name="check" class="h-4 w-4" /></button>
                                        </form>
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        @if ($product->variants->count() > 1)
                                            <form method="POST" action="{{ route('admin.variants.destroy', $variant) }}" data-confirm="Delete variant {{ $variant->sku }}?" class="inline">
                                                @csrf @method('DELETE')
                                                <button class="btn-icon-danger" title="Delete"><x-icon name="trash" class="h-4 w-4" /></button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($errors->has('sku') || $errors->has('variant'))
                    <p class="form-error px-6 pb-4">{{ $errors->first('sku') ?: $errors->first('variant') }}</p>
                @endif
            </div>
        </section>
    @endif
</x-admin-layout>
