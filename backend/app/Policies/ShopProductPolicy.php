<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\ShopProduct;
use App\Models\User;

class ShopProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::ListingsView->value);
    }

    public function update(User $user, ShopProduct $listing): bool
    {
        return $this->ownsListing($user, $listing);
    }

    public function delete(User $user, ShopProduct $listing): bool
    {
        return $user->can(P::ListingsDelete->value) || $this->ownsListing($user, $listing);
    }

    private function ownsListing(User $user, ShopProduct $listing): bool
    {
        return $user->can(P::OwnListingsManage->value) && $user->ownsShop($listing->shop_id);
    }
}
