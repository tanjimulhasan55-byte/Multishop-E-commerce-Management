<?php

namespace App\Http\Controllers\Api\V1\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Storefront
 */
class ListCategoriesController extends Controller
{
    /**
     * Active categories.
     *
     * @unauthenticated
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get()
        );
    }
}
