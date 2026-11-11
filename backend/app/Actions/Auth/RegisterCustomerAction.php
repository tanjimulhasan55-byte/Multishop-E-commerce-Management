<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterData;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class RegisterCustomerAction
{
    /**
     * Public self-registration always creates a *customer*. Elevated roles
     * can only be granted by an admin.
     *
     * @return array{token: string, user: User}
     */
    public function execute(RegisterData $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data->name,
                'email' => strtolower($data->email),
                'phone' => $data->phone,
                'password' => $data->password,
                'status' => UserStatus::Active,
            ]);

            $user->assignRole(RoleName::Customer->value);

            return $user;
        });

        $token = Auth::guard('api')->login($user);

        return ['token' => $token, 'user' => $user];
    }
}
