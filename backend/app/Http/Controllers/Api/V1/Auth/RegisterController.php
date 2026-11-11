<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterCustomerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth
 */
class RegisterController extends Controller
{
    /**
     * Register a customer account.
     *
     * @unauthenticated
     */
    public function __invoke(RegisterRequest $request, RegisterCustomerAction $action): JsonResponse
    {
        ['token' => $token, 'user' => $user] = $action->execute($request->toDto());

        return TokenResponse::make($token, $user, 201);
    }
}
