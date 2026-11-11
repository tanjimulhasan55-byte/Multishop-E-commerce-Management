<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

/**
 * @tags Auth
 */
class RefreshTokenController extends Controller
{
    /**
     * Exchange a (possibly expired, still refreshable) token for a new one.
     * The old token is blacklisted.
     *
     * @unauthenticated
     */
    public function __invoke(): JsonResponse
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        try {
            $token = $guard->refresh();
        } catch (JWTException) {
            abort(401, __('Your session has expired. Please sign in again.'));
        }

        $user = $guard->setToken($token)->user();

        abort_if($user === null || ! $user->isActive(), 401, __('Your session has expired. Please sign in again.'));

        return TokenResponse::make($token, $user);
    }
}
