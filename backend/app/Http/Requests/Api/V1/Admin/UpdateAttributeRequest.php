<?php

namespace App\Http\Requests\Api\V1\Admin;

class UpdateAttributeRequest extends StoreAttributeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('attribute')) ?? false;
    }
}
