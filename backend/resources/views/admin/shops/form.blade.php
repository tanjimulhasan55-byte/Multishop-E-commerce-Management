@php($editing = $shop !== null)
<x-admin-layout :title="$editing ? 'Edit shop' : 'New shop'"
                :breadcrumbs="[['label' => 'Shops', 'url' => route('admin.shops.index')], ['label' => $editing ? $shop->name : 'New']]">
    <x-admin.page-header :title="$editing ? 'Edit '.$shop->name : 'Create a shop'"
                         description="Shops sell products from the shared catalog at their own prices.">
        <x-slot:actions>
            <a href="{{ route('admin.shops.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $editing ? route('admin.shops.update', $shop) : route('admin.shops.store') }}"
          class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Shop details">
                <div class="space-y-5">
                    <x-admin.translatable name="name" label="Shop name" :value="$shop?->getTranslations('name') ?? []" required />
                    <x-admin.translatable name="description" label="Description" textarea :value="$shop?->getTranslations('description') ?? []" />
                </div>
            </x-admin.card>
            <x-admin.card title="Contact">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="email" type="email" label="Email" :value="$shop?->email" />
                    <x-admin.input name="phone" label="Phone" :value="$shop?->phone" placeholder="01XXXXXXXXX" />
                    <div class="sm:col-span-2"><x-admin.input name="address" label="Address" :value="$shop?->address" /></div>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Ownership & status">
                <div class="space-y-5">
                    <x-admin.select name="owner_id" label="Shop owner" :options="$owners" :value="$shop?->owner_id" placeholder="— Unassigned —" tom
                                    hint="Only users with the Shop Owner role are listed." />
                    <x-admin.select name="status" label="Status" :options="$statuses" :value="$shop?->status->value ?? 'active'" required
                                    hint="Inactive shops and all their products are hidden from customers." />
                </div>
            </x-admin.card>
            <x-admin.card>
                <x-admin.image-upload name="logo" label="Logo" :current="$shop?->thumbnailUrls()['thumb'] ?? null" remove-name="remove_logo" />
            </x-admin.card>
            <x-admin.form-actions :cancel="route('admin.shops.index')" :label="$editing ? 'Save changes' : 'Create shop'" />
        </div>
    </form>
</x-admin-layout>
