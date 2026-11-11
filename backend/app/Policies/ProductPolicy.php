<?php

namespace App\Policies;

use App\Enums\PermissionName as P;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([P::ProductsView->value, P::CatalogBrowse->value]);
    }

    public function view(User $user, Product $product): bool
    {
        return $user->canAny([P::ProductsView->value, P::CatalogBrowse->value]);
    }

    public function create(User $user): bool
    {
        return $user->canAny([P::ProductsCreate->value, P::OwnProductsManage->value]);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can(P::ProductsUpdate->value) || $this->isOwnProduct($user, $product);
    }

    public function delete(User $user, Product $product): Response
    {
        if ($user->can(P::ProductsDelete->value)) {
            return Response::allow();
        }

        if (! $this->isOwnProduct($user, $product)) {
            return Response::deny(__('You can only delete products you created.'));
        }

        // A shop owner must not pull a shared product out from under other shops.
        $listedElsewhere = $product->listings()
            ->whereHas('shop', fn ($q) => $q->where('owner_id', '!=', $user->getKey())->orWhereNull('owner_id'))
            ->exists();

        return $listedElsewhere
            ? Response::deny(__('Other shops list this product, so it cannot be deleted. Set it to archived instead.'))
            : Response::allow();
    }

    private function isOwnProduct(User $user, Product $product): bool
    {
        return $user->can(P::OwnProductsManage->value)
            && (int) $product->created_by === (int) $user->getKey();
    }
}
