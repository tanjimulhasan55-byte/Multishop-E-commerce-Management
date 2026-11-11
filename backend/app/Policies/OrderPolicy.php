<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(P::OrdersView->value);
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can(P::OrdersView->value)
            || (int) $order->user_id === (int) $user->getKey()
            || $this->managesShopOf($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can(P::OrdersPlace->value);
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->can(P::OrdersUpdate->value) || $this->managesShopOf($user, $order);
    }

    public function pay(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->getKey();
    }

    private function managesShopOf(User $user, Order $order): bool
    {
        return $user->can(P::OwnOrdersManage->value) && $user->ownsShop($order->shop_id);
    }
}
