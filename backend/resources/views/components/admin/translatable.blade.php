{{--
    English / Bangla input pair with language tabs.
    <x-admin.translatable name="name" label="Name" :value="$model?->getTranslations('name')" required />
--}}
@props(['name', 'label', 'value' => [], 'textarea' => false, 'required' => false, 'rows' => 4])
@php
    $locales = ['en' => 'English', 'bn' => 'বাংলা'];
    $hasBnError = $errors->has("{$name}.bn");
@endphp
<div x-data="{ tab: '{{ $hasBnError ? 'bn' : 'en' }}' }">
    <div class="mb-1.5 flex items-center justify-between">
        <label class="form-label !mb-0">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
        <div class="inline-flex rounded-lg bg-slate-100 p-0.5 text-xs font-medium">
            @foreach ($locales as $code => $localeLabel)
                <button type="button" @click="tab = '{{ $code }}'"
                        :class="tab === '{{ $code }}' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-md px-2.5 py-1 transition">
                    {{ $localeLabel }}
                    @if ($errors->has("{$name}.{$code}"))<span class="ml-1 inline-block h-1.5 w-1.5 rounded-full bg-rose-500"></span>@endif
                </button>
            @endforeach
        </div>
    </div>

    @foreach ($locales as $code => $localeLabel)
        @php($field = "{$name}[{$code}]")
        @php($key = "{$name}.{$code}")
        <div x-show="tab === '{{ $code }}'" @if ($code !== 'en') x-cloak @endif>
            @if ($textarea)
                <textarea name="{{ $field }}" rows="{{ $rows }}" lang="{{ $code }}"
                          placeholder="{{ $code === 'bn' ? 'ঐচ্ছিক — খালি রাখলে ইংরেজি দেখাবে' : '' }}"
                          @class(['form-input', 'is-invalid' => $errors->has($key)])
                          @if ($required && $code === 'en') required @endif>{{ old($key, $value[$code] ?? '') }}</textarea>
            @else
                <input type="text" name="{{ $field }}" value="{{ old($key, $value[$code] ?? '') }}" lang="{{ $code }}"
                       placeholder="{{ $code === 'bn' ? 'ঐচ্ছিক — খালি রাখলে ইংরেজি দেখাবে' : '' }}"
                       @class(['form-input', 'is-invalid' => $errors->has($key)])
                       @if ($required && $code === 'en') required @endif>
            @endif
            @error($key)<p class="form-error">{{ $message }}</p>@enderror
        </div>
    @endforeach
</div>
