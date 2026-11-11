<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Catalog\AttributeData;
use App\Http\Requests\Concerns\TranslatableRules;
use App\Models\ProductAttribute;
use App\Services\Catalog\SkuGenerator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An attribute ("Size") with its full list of values ("S", "M", "XL").
 * Values are submitted as an ordered list; existing ones carry their id.
 */
class StoreAttributeRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ProductAttribute::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            ...$this->translatable('name', true, 60),
            'type' => ['required', Rule::in([ProductAttribute::TYPE_SELECT, ProductAttribute::TYPE_COLOR])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'values' => ['required', 'array', 'min:1', 'max:200'],
            'values.*.id' => ['nullable', 'integer'],
            'values.*.value' => ['required', 'array:en,bn'],
            'values.*.value.en' => ['required', 'string', 'max:60'],
            'values.*.value.bn' => ['nullable', 'string', 'max:60'],
            'values.*.code' => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9]+$/'],
            'values.*.color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $values = collect($this->input('values', []));

            $dupes = $values->map(fn ($v) => mb_strtolower(trim((string) data_get($v, 'value.en'))))->duplicates();
            if ($dupes->isNotEmpty()) {
                $validator->errors()->add('values', __('Each value must be unique (duplicate: :v).', ['v' => $dupes->first()]));
            }

            $codes = $values->map(fn ($v) => SkuGenerator::code((string) (data_get($v, 'code') ?: data_get($v, 'value.en'))))->duplicates();
            if ($codes->isNotEmpty()) {
                $validator->errors()->add('values', __('SKU codes must be unique per attribute (duplicate: :c). Set a custom code.', ['c' => $codes->first()]));
            }
        }];
    }

    public function attributes(): array
    {
        return [
            ...$this->translatableAttributes('name', 'Attribute name'),
            'values.*.value.en' => 'value (English)',
            'values.*.color_hex' => 'colour',
        ];
    }

    public function toDto(): AttributeData
    {
        return AttributeData::fromArray($this->validated());
    }
}
