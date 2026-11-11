<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class TokenResponse
{
    public static function make(string $token, User $user, int $status = 200): JsonResponse
    {
        $user->loadMissing('roles');

        return (new UserResource($user))
            ->additional(['meta' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            ]])
            ->response()
            ->setStatusCode($status);
    }
}
