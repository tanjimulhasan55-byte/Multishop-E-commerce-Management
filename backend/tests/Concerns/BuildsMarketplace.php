<?php

namespace Tests\Concerns;

use App\Actions\Catalog\CreateProductAction;
use App\Actions\Catalog\SaveAttributeAction;
use App\Actions\Catalog\Variants\GenerateVariantsAction;
use App\Actions\Listings\AddProductToShopAction;
use App\DTOs\Catalog\AttributeData;
use App\DTOs\Catalog\ProductData;
use App\DTOs\Listings\ListingData;
use App\Enums\RoleName;
use App\Enums\ShopStatus;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/** Small fixture builders that go through the real Actions. */
trait BuildsMarketplace
{
    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function userWithRole(string $role): User
    {
        return User::factory()->create()->assignRole($role);
    }

    protected function admin(): User
    {
        return $this->userWithRole(RoleName::Admin->value);
    }

    protected function owner(): User
    {
        return $this->userWithRole(RoleName::ShopOwner->value);
    }

    protected function customer(): User
    {
        return $this->userWithRole(RoleName::Customer->value);
    }

    protected function shopFor(?User $owner, ShopStatus $status = ShopStatus::Active, string $name = 'Test Shop'): Shop
    {
        return Shop::create([
            'owner_id' => $owner?->id,
            'name' => ['en' => $name.' '.uniqid()],
            'status' => $status,
        ]);
    }

    protected function product(User $creator, array $overrides = []): Product
    {
        return app(CreateProductAction::class)->execute(ProductData::fromArray([
            'name' => ['en' => 'Product '.uniqid()],
            'base_price' => 100,
            'status' => 'active',
            ...$overrides,
        ]), $creator);
    }

    protected function list(Shop $shop, Product $product, float $price = 150, int $stock = 10): ShopProduct
    {
        return app(AddProductToShopAction::class)->execute($shop, $product, ListingData::fromArray([
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]));
    }

    protected function sizeAttribute(): ProductAttribute
    {
        return app(SaveAttributeAction::class)->execute(AttributeData::fromArray([
            'name' => ['en' => 'Size'],
            'type' => 'select',
            'sort_order' => 1, // after Color: SKUs read PRODUCT-COLOR-SIZE
            'values' => [
                ['value' => ['en' => 'M'], 'code' => 'M'],
                ['value' => ['en' => 'XL'], 'code' => 'XL'],
            ],
        ]));
    }

    protected function colorAttribute(): ProductAttribute
    {
        return app(SaveAttributeAction::class)->execute(AttributeData::fromArray([
            'name' => ['en' => 'Color'],
            'type' => 'color',
            'values' => [
                ['value' => ['en' => 'Red'], 'code' => 'RED', 'color_hex' => '#FF0000'],
                ['value' => ['en' => 'Blue'], 'code' => 'BLU', 'color_hex' => '#0000FF'],
            ],
        ]));
    }

    protected function generate(Product $product, array $valueIds): void
    {
        app(GenerateVariantsAction::class)->execute($product, $valueIds);
    }

    /** @return array<string, string> */
    protected function jwt(User $user): array
    {
        return ['Authorization' => 'Bearer '.JWTAuth::fromUser($user), 'Accept' => 'application/json'];
    }
}
