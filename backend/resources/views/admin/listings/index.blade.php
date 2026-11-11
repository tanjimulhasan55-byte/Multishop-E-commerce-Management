<x-admin-layout title="Shop listings" :breadcrumbs="[['label' => 'Shop listings']]">
    <x-admin.page-header title="Shop listings"
                         description="Which shop sells which catalog product, at what price. Only visible listings appear in the storefront." />

    <x-admin.datatable id="listings-table" :url="route('admin.listings.data')" :order="[[2, 'asc']]" :columns="[
        ['data' => 'product', 'title' => 'Product'],
        ['data' => 'shop', 'title' => 'Shop', 'orderable' => false],
        ['data' => 'price', 'title' => 'Price', 'searchable' => false],
        ['data' => 'stock', 'title' => 'Stock', 'searchable' => false],
        ['data' => 'variants_count', 'title' => 'Variants', 'searchable' => false],
        ['data' => 'visibility', 'title' => 'Storefront', 'orderable' => false, 'searchable' => false],
        ['data' => 'actions'],
    ]">
        <x-slot:filters>
            <x-admin.filter-select name="shop_id" :options="$shops" placeholder="All shops" />
            <x-admin.filter-select name="is_active" :options="['1' => 'Active', '0' => 'Inactive']" placeholder="Active & inactive" />
            <x-admin.filter-select name="stock" :options="['low' => 'Low stock (< 5)']" placeholder="Any stock" />
        </x-slot:filters>
    </x-admin.datatable>
</x-admin-layout>
