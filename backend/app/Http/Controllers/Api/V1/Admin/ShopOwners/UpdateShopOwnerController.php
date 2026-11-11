<?php

namespace App\Http\Controllers\Api\V1\Admin\ShopOwners;

use App\Actions\Users\SaveUserAction;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateShopOwnerRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;

/**
 * @tags Admin
 */
class UpdateShopOwnerController extends Controller
{
    public function __invoke(UpdateShopOwnerRequest $request, User $user, SaveUserAction $action): UserResource
    {
        abort_unless($user->hasRole(RoleName::ShopOwner), 404);

        return new UserResource($action->execute($request->toDto(), $user, $request->user())->load('roles'));
    }
}
