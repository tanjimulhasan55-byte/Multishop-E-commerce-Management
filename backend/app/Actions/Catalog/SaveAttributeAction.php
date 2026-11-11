<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\AttributeData;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates/updates an attribute and syncs its value list: existing values
 * (by id) are updated, new ones created, missing ones deleted — unless a
 * product variant still uses them.
 */
final class SaveAttributeAction
{
    public function execute(AttributeData $data, ?ProductAttribute $attribute = null): ProductAttribute
    {
        return DB::transaction(function () use ($data, $attribute) {
            $attribute ??= new ProductAttribute;
            $attribute->fill([
                'name' => $data->name,
                'type' => $data->type,
                'sort_order' => $data->sortOrder,
            ])->save();

            $existing = $attribute->values()->get()->keyBy('id');
            $keepIds = collect($data->values)->pluck('id')->filter()->all();

            $removed = $existing->except($keepIds);
            if ($removed->isNotEmpty()) {
                $inUse = AttributeValue::query()->whereKey($removed->keys())->whereHas('variants')->get();
                if ($inUse->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'values' => __('":v" is used by product variants and cannot be removed.', ['v' => $inUse->first()->value]),
                    ]);
                }
                AttributeValue::query()->whereKey($removed->keys())->delete();
            }

            // Two passes avoid transient unique-key clashes when codes are swapped.
            foreach ($data->values as $v) {
                if ($v['id'] && $existing->has($v['id'])) {
                    $existing[$v['id']]->update(['code' => 'TMP'.$v['id'], 'slug' => 'tmp-'.$v['id']]);
                }
            }

            foreach ($data->values as $v) {
                $attributes = [
                    'value' => $v['value'],
                    'slug' => Str::slug($v['value']['en']) ?: Str::lower($v['code']),
                    'code' => $v['code'],
                    'color_hex' => $v['color_hex'],
                    'sort_order' => $v['sort_order'],
                ];

                if ($v['id'] && $existing->has($v['id'])) {
                    $existing[$v['id']]->update($attributes);
                } else {
                    $attribute->values()->create($attributes);
                }
            }

            return $attribute->load('values');
        });
    }
}
