<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\Shop;
use App\Models\User;

class ShopPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::ShopsView->value);
    }

    public function view(User $user, Shop $shop): bool
    {
        return $user->can(P::ShopsView->value) || $user->ownsShop($shop);
    }

    public function create(User $user): bool
    {
        return $user->can(P::ShopsCreate->value);
    }

    public function update(User $user, Shop $shop): bool
    {
        return $user->can(P::ShopsUpdate->value);
    }

    public function delete(User $user, Shop $shop): bool
    {
        return $user->can(P::ShopsDelete->value);
    }

    /** Add / edit / remove products listed in this shop. */
    public function manageListings(User $user, Shop $shop): bool
    {
        return $user->can(P::OwnListingsManage->value) && $user->ownsShop($shop);
    }

    public function manageOrders(User $user, Shop $shop): bool
    {
        return $user->can(P::OwnOrdersManage->value) && $user->ownsShop($shop);
    }
}
