{{-- A filter dropdown for <x-admin.datatable>: reloads the table on change. --}}
@props(['name', 'options' => [], 'placeholder' => 'All'])
<select data-dt-filter="{{ $name }}" {{ $attributes->merge(['class' => 'form-input w-auto py-2 pr-10']) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
