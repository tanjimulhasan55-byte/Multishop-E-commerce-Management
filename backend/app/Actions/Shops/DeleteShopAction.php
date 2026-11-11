<?php

namespace App\Actions\Shops;

use App\Models\Shop;

final class DeleteShopAction
{
    /**
     * Soft delete keeps orders referencing the shop valid; all its listings
     * become invisible to customers at once through the visibility scope.
     */
    public function execute(Shop $shop): void
    {
        $shop->delete();
    }
}
