<?php

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AttributeResource;
use App\Models\ProductAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Catalog
 */
class ListAttributesController extends Controller
{
    /** All variant attributes (Size, Color …) with their values. */
    public function __invoke(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ProductAttribute::class);

        return AttributeResource::collection(
            ProductAttribute::query()->with('values')->orderBy('sort_order')->orderBy('name_en')->get()
        );
    }
}
