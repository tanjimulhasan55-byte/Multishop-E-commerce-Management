<?php

namespace App\Actions\Account;

use App\Models\User;

final class ChangePasswordAction
{
    public function execute(User $user, #[\SensitiveParameter] string $password): void
    {
        $user->forceFill(['password' => $password])->save();
    }
}
