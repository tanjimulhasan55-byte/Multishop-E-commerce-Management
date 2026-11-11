<x-admin-layout title="Attributes" :breadcrumbs="[['label' => 'Attributes']]">
    <x-admin.page-header title="Variant attributes" description="Options such as Size or Color. Their values build product variants, each with its own SKU.">
        <x-slot:actions>
            @can('create', App\Models\ProductAttribute::class)
                <a href="{{ route('admin.attributes.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New attribute</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.datatable id="attributes-table" :url="route('admin.attributes.data')" :columns="[
        ['data' => 'name', 'title' => 'Attribute'],
        ['data' => 'type', 'title' => 'Display', 'searchable' => false],
        ['data' => 'values', 'title' => 'Values (SKU code)', 'orderable' => false, 'searchable' => false],
        ['data' => 'actions'],
    ]" />
</x-admin-layout>
