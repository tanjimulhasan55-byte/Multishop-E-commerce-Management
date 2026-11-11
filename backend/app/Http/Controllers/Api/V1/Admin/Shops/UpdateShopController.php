<?php

namespace App\Http\Controllers\Api\V1\Admin\Shops;

use App\Actions\Shops\SaveShopAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateShopRequest;
use App\Http\Resources\Api\V1\ShopResource;
use App\Models\Shop;

/**
 * @tags Admin
 */
class UpdateShopController extends Controller
{
    public function __invoke(UpdateShopRequest $request, Shop $shop, SaveShopAction $action): ShopResource
    {
        return new ShopResource($action->execute($request->toDto(), $shop)->load('media'));
    }
}
