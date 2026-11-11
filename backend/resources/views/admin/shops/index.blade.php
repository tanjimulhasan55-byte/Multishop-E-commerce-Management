<x-admin-layout title="Shops" :breadcrumbs="[['label' => 'Shops']]">
    <x-admin.page-header title="Shops" description="Every storefront on the marketplace and who runs it.">
        <x-slot:actions>
            @can('create', App\Models\Shop::class)
                <a href="{{ route('admin.shops.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New shop</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.datatable id="shops-table" :url="route('admin.shops.data')" :order="[[4, 'desc']]" :columns="[
        ['data' => 'shop', 'title' => 'Shop'],
        ['data' => 'owner', 'title' => 'Owner'],
        ['data' => 'status', 'title' => 'Status', 'searchable' => false],
        ['data' => 'listings_count', 'title' => 'Products', 'searchable' => false],
        ['data' => 'created_at', 'title' => 'Created', 'searchable' => false],
        ['data' => 'actions'],
    ]">
        <x-slot:filters>
            <x-admin.filter-select name="status" :options="$statuses" placeholder="All statuses" />
            <x-admin.filter-select name="owner_id" :options="$owners" placeholder="All owners" />
        </x-slot:filters>
    </x-admin.datatable>
</x-admin-layout>
