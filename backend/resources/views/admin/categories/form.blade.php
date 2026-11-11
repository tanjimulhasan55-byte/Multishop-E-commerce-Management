@php($editing = $category !== null)
<x-admin-layout :title="$editing ? 'Edit category' : 'New category'"
                :breadcrumbs="[['label' => 'Categories', 'url' => route('admin.categories.index')], ['label' => $editing ? $category->name : 'New']]">
    <x-admin.page-header :title="$editing ? 'Edit '.$category->name : 'Create a category'"
                         :description="$editing ? $category->breadcrumb() : 'Choose a parent to nest it, or leave empty for a top-level category.'">
        <x-slot:actions>
            <a href="{{ route('admin.categories.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.categories.update', $category) : route('admin.categories.store') }}" class="max-w-3xl">
        @csrf
        @if ($editing) @method('PUT') @endif
        <x-admin.card>
            <div class="space-y-5">
                <x-admin.translatable name="name" label="Name" :value="$category?->getTranslations('name') ?? []" required />
                <x-admin.select name="parent_id" label="Parent category" :options="$parents" :value="$category?->parent_id ?? $defaultParent"
                                placeholder="— Top level —" tom hint="A category cannot be moved under itself or its own sub-categories." />
                <div class="grid items-end gap-5 sm:grid-cols-2">
                    <x-admin.input name="sort_order" type="number" min="0" label="Sort order" :value="$category?->sort_order ?? 0" />
                    <x-admin.toggle name="is_active" label="Active" hint="Shown in the storefront" :checked="$category?->is_active ?? true" />
                </div>
            </div>
            <x-slot:footer>
                <x-admin.form-actions :cancel="route('admin.categories.index')" :label="$editing ? 'Save changes' : 'Create category'" />
            </x-slot:footer>
        </x-admin.card>
    </form>
</x-admin-layout>
