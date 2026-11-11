<?php

namespace App\Actions\Users;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class DeleteUserAction
{
    public function execute(User $user, User $actor): void
    {
        if ($actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('You cannot delete your own account here.')]);
        }

        if ($user->hasRole(RoleName::Admin) && User::role(RoleName::Admin->value)->count() <= 1) {
            throw ValidationException::withMessages(['user' => __('At least one administrator must remain.')]);
        }

        if ($user->shops()->exists()) {
            throw ValidationException::withMessages(['user' => __('This user owns shops. Reassign or delete them first.')]);
        }

        // Soft delete: keeps order history; the email stays reserved.
        $user->delete();
    }
}
