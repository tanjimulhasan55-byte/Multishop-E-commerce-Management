<?php

namespace App\Http\Requests\Api\V1\Admin;

class UpdateCategoryRequest extends StoreCategoryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('category')) ?? false;
    }
}
