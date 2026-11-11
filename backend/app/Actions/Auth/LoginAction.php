<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

final class LoginAction
{
    /**
     * Issue a JWT for valid, active credentials.
     *
     * @return array{token: string, user: User}
     *
     * @throws ValidationException
     */
    public function execute(LoginData $data): array
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $token = $guard->attempt($data->credentials());

        // Same message for unknown email and wrong password (no user enumeration).
        if (! $token) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        /** @var User $user */
        $user = $guard->user();

        if (! $user->isActive()) {
            $guard->invalidate();

            throw ValidationException::withMessages(['email' => __('Your account has been suspended.')]);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return ['token' => $token, 'user' => $user];
    }
}
