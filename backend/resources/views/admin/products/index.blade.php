<x-admin-layout title="Products" :breadcrumbs="[['label' => 'Products']]">
    <x-admin.page-header title="Product catalog" description="The shared catalog. Products created here or by shop owners can be listed by any shop.">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <a href="{{ route('admin.products.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New product</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.datatable id="products-table" :url="route('admin.products.data')" :order="[[0, 'asc']]" :columns="[
        ['data' => 'product', 'title' => 'Product'],
        ['data' => 'sku', 'title' => 'SKU'],
        ['data' => 'variants', 'title' => 'Variants', 'orderable' => false, 'searchable' => false],
        ['data' => 'base_price', 'title' => 'Base price', 'searchable' => false],
        ['data' => 'shops', 'title' => 'Shops', 'searchable' => false],
        ['data' => 'status', 'title' => 'Status', 'searchable' => false],
        ['data' => 'creator', 'title' => 'Created by', 'orderable' => false],
        ['data' => 'actions'],
    ]">
        <x-slot:filters>
            <x-admin.filter-select name="status" :options="$statuses" placeholder="All statuses" />
            <x-admin.filter-select name="category_id" :options="$categories" placeholder="All categories" />
            <x-admin.filter-select name="assignment" placeholder="Listed & not listed"
                                   :options="['assigned' => 'Listed in a shop', 'unassigned' => 'Not listed (hidden from customers)']" />
        </x-slot:filters>
    </x-admin.datatable>
</x-admin-layout>
