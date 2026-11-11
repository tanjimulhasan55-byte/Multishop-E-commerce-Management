<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\DTOs\Catalog\CategoryData;
use App\Http\Requests\Concerns\TranslatableRules;
use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    use TranslatableRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            ...$this->translatable('name', true, 100),
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id'), $this->validParent()],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /** No cycles (a category can't move under itself or its descendants) and a depth cap. */
    protected function validParent(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $parent = Category::query()->find($value);
            $self = $this->route('category');

            if ($parent === null) {
                return;
            }

            if ($self instanceof Category && ($parent->is($self) || $self->isAncestorOf($parent))) {
                $fail(__('A category cannot be placed under itself or one of its sub-categories.'));

                return;
            }

            if ($parent->depth + 1 >= Category::MAX_DEPTH) {
                $fail(__('Categories can be nested at most :max levels deep.', ['max' => Category::MAX_DEPTH]));
            }
        };
    }

    public function attributes(): array
    {
        return [...$this->translatableAttributes('name', 'Category name'), 'parent_id' => 'parent category'];
    }

    public function toDto(): CategoryData
    {
        return CategoryData::fromArray($this->validated());
    }
}
