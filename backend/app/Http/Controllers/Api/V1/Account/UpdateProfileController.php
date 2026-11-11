<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\UpdateProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;

/**
 * @tags Account
 */
class UpdateProfileController extends Controller
{
    public function __invoke(UpdateProfileRequest $request, UpdateProfileAction $action): UserResource
    {
        return new UserResource($action->execute($request->user(), $request->toDto())->load('roles'));
    }
}
