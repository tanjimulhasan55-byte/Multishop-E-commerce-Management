@php($isOwners = $activeRole === 'shop_owner')
<x-admin-layout :title="$isOwners ? 'Shop owners' : 'Users'" :breadcrumbs="[['label' => $isOwners ? 'Shop owners' : 'Users']]">
    <x-admin.page-header :title="$isOwners ? 'Shop owners' : 'Users'"
                         :description="$isOwners ? 'Accounts that manage shops and their product listings.' : 'Every account and the roles that decide what it can access.'">
        <x-slot:actions>
            @can('create', App\Models\User::class)
                <a href="{{ route('admin.users.create', $isOwners ? ['role' => 'shop_owner'] : []) }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" /> {{ $isOwners ? 'New shop owner' : 'New user' }}
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index') }}" @class(['btn-sm', 'btn-primary' => ! $activeRole, 'btn-secondary' => $activeRole])>All</a>
        @foreach ($roles as $value => $label)
            <a href="{{ route('admin.users.index', ['role' => $value]) }}"
               @class(['btn-sm', 'btn-primary' => $activeRole === $value, 'btn-secondary' => $activeRole !== $value])>{{ $label }}</a>
        @endforeach
    </div>

    <x-admin.datatable id="users-table" :url="route('admin.users.data', array_filter(['role' => $activeRole]))" :columns="[
        ['data' => 'user', 'title' => 'User'],
        ['data' => 'roles', 'title' => 'Roles', 'orderable' => false, 'searchable' => false],
        ['data' => 'status', 'title' => 'Status', 'searchable' => false],
        ['data' => 'shops_count', 'title' => 'Shops', 'searchable' => false],
        ['data' => 'last_login_at', 'title' => 'Last login', 'searchable' => false],
        ['data' => 'actions'],
    ]">
        <x-slot:filters>
            <x-admin.filter-select name="status" :options="$statuses" placeholder="All statuses" />
        </x-slot:filters>
    </x-admin.datatable>
</x-admin-layout>
