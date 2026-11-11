<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class MeController extends Controller
{
    /** The authenticated user, with roles and effective permissions. */
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }
}
