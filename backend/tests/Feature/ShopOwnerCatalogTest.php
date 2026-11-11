<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/** Shared catalog + per-shop listings, managed by shop owners via the JWT API. */
class ShopOwnerCatalogTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        Storage::fake('public');
    }

    public function test_product_created_by_an_owner_joins_the_shared_catalog_for_other_owners(): void
    {
        $rahim = $this->owner();
        $nusrat = $this->owner();

        $this->withHeaders($this->jwt($rahim))->postJson('/api/v1/shop/products', [
            'name' => ['en' => 'Handmade Basket', 'bn' => 'হাতে তৈরি ঝুড়ি'],
            'base_price' => 450,
            'status' => 'active',
        ])->assertCreated()->assertJsonPath('data.attributes.is_mine', true);

        $this->withHeaders($this->jwt($nusrat))->getJson('/api/v1/shop/catalog?filter[search]=Basket')
            ->assertOk()
            ->assertJsonPath('data.0.attributes.name', 'Handmade Basket')
            ->assertJsonPath('data.0.attributes.is_mine', false)
            ->assertJsonPath('data.0.attributes.can.update', false);
    }

    public function test_owner_lists_catalog_product_with_own_price_details_and_thumbnail(): void
    {
        $owner = $this->owner();
        $shop = $this->shopFor($owner);
        $product = $this->product($this->admin(), ['base_price' => 1000]);

        $response = $this->withHeaders($this->jwt($owner))->post("/api/v1/shop/shops/{$shop->slug}/listings", [
            'product_id' => $product->id,
            'price' => 899.50,
            'stock' => 12,
            'title' => ['en' => 'Our special edition'],
            'thumbnail' => UploadedFile::fake()->image('thumb.jpg', 400, 400),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.attributes.price', '899.50')
            ->assertJsonPath('data.attributes.stock', 12)
            ->assertJsonPath('data.attributes.title', 'Our special edition')
            ->assertJsonPath('data.attributes.has_own_thumbnail', true);

        $listingId = $response->json('data.id');

        $this->withHeaders($this->jwt($owner))->patchJson("/api/v1/shop/listings/{$listingId}", [
            'price' => 950, 'stock' => 3, 'title' => ['en' => 'Renamed'], 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.attributes.price', '950.00')->assertJsonPath('data.attributes.title', 'Renamed');
    }

    public function test_owner_cannot_manage_another_owners_shop_or_listing(): void
    {
        $owner = $this->owner();
        $intruder = $this->owner();
        $shop = $this->shopFor($owner);
        $listing = $this->list($shop, $this->product($this->admin()));

        $headers = $this->jwt($intruder);
        $this->withHeaders($headers)->getJson("/api/v1/shop/shops/{$shop->slug}/listings")->assertForbidden();
        $this->withHeaders($headers)->postJson("/api/v1/shop/shops/{$shop->slug}/listings", ['product_id' => 1, 'price' => 1, 'stock' => 1])->assertForbidden();
        $this->withHeaders($headers)->patchJson("/api/v1/shop/listings/{$listing->id}", ['price' => 1])->assertForbidden();
        $this->withHeaders($headers)->deleteJson("/api/v1/shop/listings/{$listing->id}")->assertForbidden();
    }

    public function test_owner_cannot_edit_products_created_by_others_and_customers_cannot_use_owner_api(): void
    {
        $product = $this->product($this->admin());

        $this->withHeaders($this->jwt($this->owner()))
            ->patchJson("/api/v1/shop/products/{$product->id}", ['name' => ['en' => 'Hacked'], 'base_price' => 1, 'status' => 'active'])
            ->assertForbidden();

        $this->withHeaders($this->jwt($this->customer()))->getJson('/api/v1/shop/catalog')->assertForbidden();
        $this->flushHeaders()->getJson('/api/v1/shop/catalog')->assertUnauthorized();
    }

    public function test_same_product_cannot_be_listed_twice_in_a_shop(): void
    {
        $owner = $this->owner();
        $shop = $this->shopFor($owner);
        $product = $this->product($this->admin());
        $this->list($shop, $product);

        $this->withHeaders($this->jwt($owner))
            ->postJson("/api/v1/shop/shops/{$shop->slug}/listings", ['product_id' => $product->id, 'price' => 10, 'stock' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.meta.field', 'product_id');
    }
}
