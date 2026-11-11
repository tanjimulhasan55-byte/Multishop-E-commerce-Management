<?php

namespace App\Actions\Catalog;

use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Validation\ValidationException;

final class DeleteAttributeAction
{
    public function execute(ProductAttribute $attribute): void
    {
        $used = AttributeValue::query()->where('attribute_id', $attribute->getKey())->whereHas('variants')->exists();

        if ($used) {
            throw ValidationException::withMessages([
                'attribute' => __('This attribute is used by product variants and cannot be deleted.'),
            ]);
        }

        $attribute->delete();
    }
}
