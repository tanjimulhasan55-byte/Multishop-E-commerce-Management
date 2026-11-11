<?php

namespace App\Http\Controllers\Api\V1\Admin\Shops;

use App\Actions\Shops\SaveShopAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreShopRequest;
use App\Http\Resources\Api\V1\ShopResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin
 */
class StoreShopController extends Controller
{
    public function __invoke(StoreShopRequest $request, SaveShopAction $action): JsonResponse
    {
        return (new ShopResource($action->execute($request->toDto())->load('media')))
            ->response()->setStatusCode(201);
    }
}
