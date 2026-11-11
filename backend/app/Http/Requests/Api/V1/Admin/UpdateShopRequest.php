<?php

namespace App\Http\Requests\Api\V1\Admin;

class UpdateShopRequest extends StoreShopRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('shop')) ?? false;
    }
}
