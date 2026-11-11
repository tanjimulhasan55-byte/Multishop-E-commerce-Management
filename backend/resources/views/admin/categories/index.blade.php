<x-admin-layout title="Categories" :breadcrumbs="[['label' => 'Categories']]">
    <x-admin.page-header title="Categories" description="A nested category tree (up to 5 levels). Filtering by a category includes all of its sub-categories.">
        <x-slot:actions>
            @can('create', App\Models\Category::class)
                <a href="{{ route('admin.categories.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New category</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.datatable id="categories-table" :url="route('admin.categories.data')" :page-length="25" :columns="[
        ['data' => 'category', 'title' => 'Category'],
        ['data' => 'slug', 'title' => 'Slug'],
        ['data' => 'children_count', 'title' => 'Sub-categories', 'searchable' => false],
        ['data' => 'products_count', 'title' => 'Products', 'searchable' => false],
        ['data' => 'is_active', 'title' => 'Status', 'searchable' => false],
        ['data' => 'actions'],
    ]" />
</x-admin-layout>
