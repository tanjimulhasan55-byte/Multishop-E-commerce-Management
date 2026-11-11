<?php

namespace App\Actions\Users;

use App\DTOs\Users\UserData;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create or update any account and sync its roles.
 */
final class SaveUserAction
{
    public function execute(UserData $data, ?User $user = null, ?User $actor = null): User
    {
        if ($user !== null) {
            $this->guardAgainstLockout($user, $data, $actor);
            $this->guardShopOwnership($user, $data);
        }

        return DB::transaction(function () use ($data, $user) {
            $user ??= new User;

            $user->fill([
                'name' => $data->name,
                'email' => $data->email,
                'phone' => $data->phone,
                'status' => $data->status,
            ]);

            if ($data->password !== null) {
                $user->password = $data->password;
            }

            $user->save();
            $user->syncRoles($data->roles);

            return $user;
        });
    }

    /** Admins cannot suspend themselves or strip their own admin role. */
    private function guardAgainstLockout(User $user, UserData $data, ?User $actor): void
    {
        $removesAdmin = $user->hasRole(RoleName::Admin) && ! in_array(RoleName::Admin->value, $data->roles, true);

        if ($actor?->is($user) && ($removesAdmin || $data->status !== UserStatus::Active)) {
            throw ValidationException::withMessages([
                'roles' => __('You cannot remove your own administrator access or suspend yourself.'),
            ]);
        }

        if ($removesAdmin && User::role(RoleName::Admin->value)->count() <= 1) {
            throw ValidationException::withMessages([
                'roles' => __('At least one administrator must remain.'),
            ]);
        }
    }

    /** A user who owns shops must keep the shop_owner role. */
    private function guardShopOwnership(User $user, UserData $data): void
    {
        if (! in_array(RoleName::ShopOwner->value, $data->roles, true) && $user->shops()->exists()) {
            throw ValidationException::withMessages([
                'roles' => __('This user owns shops. Reassign the shops before removing the Shop Owner role.'),
            ]);
        }
    }
}
