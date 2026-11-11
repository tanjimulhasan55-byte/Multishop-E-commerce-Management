<?php

namespace App\Http\Controllers\Api\V1\Admin\Shops;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ShopResource;
use App\Models\Shop;

/**
 * @tags Admin
 */
class ShowShopController extends Controller
{
    public function __invoke(Shop $shop): ShopResource
    {
        $this->authorize('view', $shop);

        return new ShopResource($shop->load(['media', 'owner'])->loadCount('listings'));
    }
}
