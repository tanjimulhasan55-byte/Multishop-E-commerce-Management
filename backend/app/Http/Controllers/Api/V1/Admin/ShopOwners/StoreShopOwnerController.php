<?php

namespace App\Http\Controllers\Api\V1\Admin\ShopOwners;

use App\Actions\Users\SaveUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreShopOwnerRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin
 */
class StoreShopOwnerController extends Controller
{
    /** Create a shop-owner account (role is always `shop_owner`). */
    public function __invoke(StoreShopOwnerRequest $request, SaveUserAction $action): JsonResponse
    {
        $owner = $action->execute($request->toDto(), actor: $request->user());

        return (new UserResource($owner->load('roles')))->response()->setStatusCode(201);
    }
}
