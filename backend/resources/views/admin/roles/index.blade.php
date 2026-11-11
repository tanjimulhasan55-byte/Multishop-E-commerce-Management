<x-admin-layout title="Roles & Permissions" :breadcrumbs="[['label' => 'Roles & Permissions']]">
    <x-admin.page-header title="Roles & permissions"
                         description="Create roles and decide exactly what each one may access. Administrator always has full access; system roles can't be deleted.">
        <x-slot:actions>
            @can('create', Spatie\Permission\Models\Role::class)
                <a href="{{ route('admin.roles.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> New role</a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.datatable id="roles-table" :url="route('admin.roles.data')" :columns="[
        ['data' => 'name', 'title' => 'Role'],
        ['data' => 'type', 'title' => 'Type', 'orderable' => false, 'searchable' => false],
        ['data' => 'permissions_count', 'title' => 'Permissions', 'searchable' => false],
        ['data' => 'users_count', 'title' => 'Users', 'searchable' => false],
        ['data' => 'actions'],
    ]" />
</x-admin-layout>
