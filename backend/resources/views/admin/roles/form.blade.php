@php
    $editing = $role !== null;
    $isSystem = $editing && App\Enums\RoleName::isSystem($role->name);
    $selected = old('permissions', $granted);
@endphp
<x-admin-layout :title="$editing ? 'Edit role' : 'New role'"
                :breadcrumbs="[['label' => 'Roles & Permissions', 'url' => route('admin.roles.index')], ['label' => $editing ? Str::headline($role->name) : 'New']]">
    <x-admin.page-header :title="$editing ? 'Edit role: '.Str::headline($role->name) : 'Create a role'"
                         description="Tick the permissions this role grants. Users can hold several roles; their permissions add up.">
        <x-slot:actions>
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          x-data="{
              selected: @js(array_values($selected)),
              toggleGroup(perms) {
                  const all = perms.every(p => this.selected.includes(p));
                  this.selected = all ? this.selected.filter(p => !perms.includes(p)) : [...new Set([...this.selected, ...perms])];
              },
              groupState(perms) {
                  const n = perms.filter(p => this.selected.includes(p)).length;
                  return n === 0 ? 'none' : (n === perms.length ? 'all' : 'some');
              },
          }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6">
                <x-admin.card title="Role">
                    <x-admin.input name="name" label="Role name" :value="$role?->name" required :readonly="$isSystem"
                                   :hint="$isSystem ? 'System role names are fixed.' : 'Lowercase, e.g. support_agent (spaces become underscores).'" />
                </x-admin.card>
                <div class="card bg-gradient-to-br from-brand-600 to-violet-700 p-6 text-white">
                    <p class="text-sm font-medium text-brand-100">Selected permissions</p>
                    <p class="mt-1 text-4xl font-bold tabular-nums"><span x-text="selected.length"></span><span class="text-xl text-brand-200"> / {{ count(App\Enums\PermissionName::cases()) }}</span></p>
                    <p class="mt-3 text-xs text-brand-100">Users with this role see only the menus and actions granted here — enforced on every request by policies, in the panel and the API.</p>
                </div>
                <x-admin.form-actions :cancel="route('admin.roles.index')" :label="$editing ? 'Save role' : 'Create role'" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                @foreach ($groups as $group => $permissions)
                    @php($values = array_map(fn ($p) => $p->value, $permissions))
                    <div class="card overflow-hidden">
                        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3">
                            <h3 class="text-sm font-semibold text-slate-800">{{ $group }}</h3>
                            <button type="button" class="text-xs font-semibold text-brand-600 hover:text-brand-700"
                                    @click="toggleGroup(@js($values))"
                                    x-text="groupState(@js($values)) === 'all' ? 'Clear' : 'Select all'"></button>
                        </div>
                        <div class="space-y-1 p-3">
                            @foreach ($permissions as $permission)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-1.5 hover:bg-slate-50">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->value }}" class="form-checkbox" x-model="selected">
                                    <span class="min-w-0 flex-1 text-sm text-slate-700">{{ $permission->label() }}</span>
                                    <code class="hidden text-[10px] text-slate-400 xl:inline">{{ $permission->value }}</code>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                @error('permissions.*')<p class="form-error sm:col-span-2">{{ $message }}</p>@enderror
            </div>
        </div>
    </form>
</x-admin-layout>
