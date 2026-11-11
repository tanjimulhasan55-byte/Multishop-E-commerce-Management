@php
    $editing = $user !== null;
    $selectedRoles = old('roles', $editing ? $user->roles->pluck('name')->all() : $presetRoles);
@endphp
<x-admin-layout :title="$editing ? 'Edit user' : 'New user'"
                :breadcrumbs="[['label' => 'Users', 'url' => route('admin.users.index')], ['label' => $editing ? $user->name : 'New']]">
    <x-admin.page-header :title="$editing ? $user->name : 'Create an account'"
                         description="Roles decide which screens and actions this person can access.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}" class="btn-secondary"><x-icon name="arrow-left" class="h-4 w-4" /> Back</a>
        </x-slot:actions>
    </x-admin.page-header>

    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="grid gap-6 lg:grid-cols-3">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Profile">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><x-admin.input name="name" label="Full name" :value="$user?->name" required /></div>
                    <x-admin.input name="email" type="email" label="Email" :value="$user?->email" required />
                    <x-admin.input name="phone" label="Phone" :value="$user?->phone" placeholder="01XXXXXXXXX" />
                </div>
            </x-admin.card>
            <x-admin.card title="Password" :description="$editing ? 'Leave empty to keep the current password.' : 'Minimum 8 characters with letters and numbers.'">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-admin.input name="password" type="password" label="Password" :required="! $editing" autocomplete="new-password" />
                    <x-admin.input name="password_confirmation" type="password" label="Confirm password" :required="! $editing" autocomplete="new-password" />
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Roles" description="Combine roles; permissions add up.">
                <div class="space-y-2">
                    @foreach ($roles as $role)
                        @php($system = App\Enums\RoleName::tryFrom($role->name))
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl p-3 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50 has-[:checked]:bg-brand-50/60 has-[:checked]:ring-brand-500">
                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="form-checkbox mt-0.5" @checked(in_array($role->name, $selectedRoles))>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-800">{{ $system?->label() ?? Str::headline($role->name) }}</span>
                                <span class="block text-xs text-slate-500">
                                    {{ $role->name === 'admin' ? 'Full access to everything' : $role->permissions_count.' permissions' }}
                                    @if ($system) · system role @endif
                                </span>
                            </span>
                        </label>
                    @endforeach
                    @error('roles')<p class="form-error">{{ $message }}</p>@enderror
                    @error('roles.*')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </x-admin.card>
            <x-admin.card>
                <x-admin.select name="status" label="Account status" :options="$statuses" :value="$user?->status->value ?? 'active'" required
                                hint="Suspended users are signed out of the panel and API immediately." />
            </x-admin.card>
            <x-admin.form-actions :cancel="route('admin.users.index')" :label="$editing ? 'Save changes' : 'Create account'" />
        </div>
    </form>
</x-admin-layout>
