<?php

namespace App\Http\Controllers\Api\V1\Shop;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShopResource;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Shop Owner
 */
class ListMyShopsController extends Controller
{
    /** Shops owned by the authenticated shop owner. */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $shops = Shop::query()->ownedBy($request->user())
            ->with('media')
            ->withCount('listings')
            ->orderBy('name_en')
            ->get();

        return ShopResource::collection($shops);
    }
}
