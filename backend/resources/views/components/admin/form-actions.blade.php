{{-- Cancel + submit pair used at the bottom of every admin form. --}}
@props(['cancel', 'label' => 'Save'])
<div {{ $attributes->merge(['class' => 'flex justify-end gap-3']) }}>
    <a href="{{ $cancel }}" class="btn-secondary">Cancel</a>
    <button type="submit" class="btn-primary"><x-icon name="check" class="h-4 w-4" /> {{ $label }}</button>
</div>
