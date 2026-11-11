<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth
 */
class LoginController extends Controller
{
    /**
     * Log in and receive a JWT.
     *
     * Rate limited to 5 attempts per minute per email + IP.
     *
     * @unauthenticated
     */
    public function __invoke(LoginRequest $request, LoginAction $action): JsonResponse
    {
        ['token' => $token, 'user' => $user] = $action->execute($request->toDto());

        return TokenResponse::make($token, $user);
    }
}
