<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/** Attributes -> values -> variant combinations, each with its own SKU. */
class ProductVariantTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_simple_product_gets_one_default_variant_with_the_product_sku(): void
    {
        $product = $this->product($this->admin(), ['sku' => 'lamp-01']);

        $this->assertSame('LAMP-01', $product->sku);
        $this->assertCount(1, $product->variants);
        $this->assertSame('LAMP-01', $product->variants->first()->sku);
        $this->assertTrue($product->variants->first()->isDefault());
    }

    public function test_generating_variants_builds_every_combination_with_unique_skus(): void
    {
        $owner = $this->owner();
        $product = $this->product($owner, ['sku' => 'TEE']);
        $size = $this->sizeAttribute();
        $color = $this->colorAttribute();

        $ids = [...$size->values->pluck('id'), ...$color->values->pluck('id')];

        $response = $this->withHeaders($this->jwt($owner))
            ->postJson("/api/v1/shop/products/{$product->id}/variants/generate", ['attribute_value_ids' => $ids])
            ->assertOk();

        $skus = collect($response->json('data.attributes.variants'))->pluck('sku')->sort()->values()->all();
        $this->assertSame(['TEE-BLU-M', 'TEE-BLU-XL', 'TEE-RED-M', 'TEE-RED-XL'], $skus);
        $this->assertCount(2, $response->json('data.attributes.options'));

        // Re-running is idempotent; the option-less default variant was replaced.
        $this->generate($product->fresh(), $ids);
        $this->assertSame(4, $product->variants()->count());
        $this->assertSame(0, $product->variants()->where('option_signature', '')->count());
    }

    public function test_shops_already_listing_a_product_receive_new_variants_and_can_price_each(): void
    {
        $owner = $this->owner();
        $shop = $this->shopFor($owner);
        $product = $this->product($this->admin());
        $listing = $this->list($shop, $product, 500, 5);
        $size = $this->sizeAttribute();

        $this->generate($product, $size->values->pluck('id')->all());

        $variants = $listing->variants()->with('variant')->get();
        $this->assertCount(2, $variants);
        $this->assertSame(0, (int) $variants->sum('stock'));

        $payload = $variants->map(fn ($v, $i) => [
            'variant_id' => $v->product_variant_id, 'price' => 500 + $i * 100, 'stock' => 4, 'is_active' => true,
        ])->values()->all();

        $this->withHeaders($this->jwt($owner))
            ->patchJson("/api/v1/shop/listings/{$listing->id}", ['variants' => $payload, 'is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.attributes.price', '500.00')   // lowest = "from" price
            ->assertJsonPath('data.attributes.stock', 8);          // total stock

        $this->withHeaders($this->jwt($owner))
            ->patchJson("/api/v1/shop/listings/{$listing->id}", ['variants' => [['variant_id' => 999999, 'price' => 1, 'stock' => 1]]])
            ->assertUnprocessable();
    }

    public function test_storefront_can_filter_by_option_value(): void
    {
        $shop = $this->shopFor($this->owner());
        $product = $this->product($this->admin());
        $size = $this->sizeAttribute();
        $this->generate($product, $size->values->pluck('id')->all());
        $listing = $this->list($shop, $product, 300, 0);

        $xl = $size->values->firstWhere('code', 'XL');
        $listing->variants()->whereHas('variant.attributeValues', fn ($q) => $q->whereKey($xl->id))->update(['stock' => 3]);

        $this->getJson("/api/v1/storefront/listings?filter[options]={$xl->id}")->assertJsonCount(1, 'data');
        $m = $size->values->firstWhere('code', 'M');
        $this->getJson("/api/v1/storefront/listings?filter[options]={$m->id}")->assertJsonCount(0, 'data'); // M is out of stock
    }
}
