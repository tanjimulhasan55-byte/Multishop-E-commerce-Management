@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false])
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))
<div>
    @if ($label)
        <label for="{{ $attributes->get('id', $errorKey) }}" class="form-label">
            {{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif
    <input id="{{ $attributes->get('id', $errorKey) }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'password') value="{{ old($errorKey, $value) }}" @endif
           @required($required)
           {{ $attributes->except('id')->class(['form-input', 'is-invalid' => $errors->has($errorKey)]) }}>
    @if ($hint)<p class="form-hint">{{ $hint }}</p>@endif
    @error($errorKey)<p class="form-error">{{ $message }}</p>@enderror
</div>
