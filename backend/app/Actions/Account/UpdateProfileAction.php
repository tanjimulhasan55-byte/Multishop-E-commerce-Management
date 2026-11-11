<?php

namespace App\Actions\Account;

use App\DTOs\Account\ProfileData;
use App\Models\User;

final class UpdateProfileAction
{
    public function execute(User $user, ProfileData $data): User
    {
        $user->fill([
            'name' => $data->name,
            'email' => strtolower($data->email),
            'phone' => $data->phone,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }
}
