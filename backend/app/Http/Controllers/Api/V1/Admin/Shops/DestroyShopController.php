<?php

namespace App\Http\Controllers\Api\V1\Admin\Shops;

use App\Actions\Shops\DeleteShopAction;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Response;

/**
 * @tags Admin
 */
class DestroyShopController extends Controller
{
    public function __invoke(Shop $shop, DeleteShopAction $action): Response
    {
        $this->authorize('delete', $shop);

        $action->execute($shop);

        return response()->noContent();
    }
}
