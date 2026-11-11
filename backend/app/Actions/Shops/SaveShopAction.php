<?php

namespace App\Actions\Shops;

use App\Actions\Media\SyncImageAction;
use App\DTOs\Shops\ShopData;
use App\Models\Shop;

final class SaveShopAction
{
    public function __construct(private readonly SyncImageAction $syncImage) {}

    public function execute(ShopData $data, ?Shop $shop = null): Shop
    {
        $shop ??= new Shop;
        $shop->fill($data->toAttributes())->save();

        $this->syncImage->execute($shop, $data->logo, $data->removeLogo, 'logo');

        return $shop;
    }
}
