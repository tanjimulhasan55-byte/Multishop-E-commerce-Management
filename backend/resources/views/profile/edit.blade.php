<x-admin-layout title="My profile" :breadcrumbs="[['label' => 'My profile']]">
    <x-admin.page-header title="My profile" description="Your account details and password." />

    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <x-admin.card>@include('profile.partials.update-profile-information-form')</x-admin.card>
        <x-admin.card>@include('profile.partials.update-password-form')</x-admin.card>
    </div>
</x-admin-layout>
