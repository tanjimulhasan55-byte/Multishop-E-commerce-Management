@props(['name', 'label' => null, 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'required' => false, 'tom' => false])
@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $selected = old($errorKey, $value);
@endphp
<div>
    @if ($label)
        <label for="{{ $errorKey }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    @endif
    <select id="{{ $errorKey }}" name="{{ $name }}" @required($required) @if ($tom) data-tom-select @endif
        {{ $attributes->class(['form-input pr-10', 'is-invalid' => $errors->has($errorKey)]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($errorKey)<p class="form-error">{{ $message }}</p>@enderror
</div>
