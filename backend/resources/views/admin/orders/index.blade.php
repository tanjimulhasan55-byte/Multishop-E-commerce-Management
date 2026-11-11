<x-admin-layout title="Orders" :breadcrumbs="[['label' => 'Orders']]">
    <x-admin.page-header title="Orders" description="Orders across all shops. Shop owners manage their own from the shop panel." />

    <x-admin.datatable id="orders-table" :url="route('admin.orders.data')" :order="[[6, 'desc']]" :columns="[
        ['data' => 'number', 'title' => 'Order'],
        ['data' => 'customer', 'title' => 'Customer'],
        ['data' => 'shop', 'title' => 'Shop', 'orderable' => false, 'searchable' => false],
        ['data' => 'total', 'title' => 'Total', 'searchable' => false],
        ['data' => 'status', 'title' => 'Status', 'searchable' => false],
        ['data' => 'payment_status', 'title' => 'Payment', 'searchable' => false],
        ['data' => 'placed_at', 'title' => 'Placed', 'searchable' => false],
        ['data' => 'actions'],
    ]">
        <x-slot:filters>
            <x-admin.filter-select name="status" :options="$statuses" placeholder="All statuses" />
            <x-admin.filter-select name="payment_status" :options="$paymentStatuses" placeholder="All payments" />
            <x-admin.filter-select name="shop_id" :options="$shops" placeholder="All shops" />
        </x-slot:filters>
    </x-admin.datatable>
</x-admin-layout>
