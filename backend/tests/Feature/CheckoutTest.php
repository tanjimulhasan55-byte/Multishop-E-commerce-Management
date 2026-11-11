<?php

namespace Tests\Feature;

use App\Models\ShopProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function checkout(array $headers, int $shopId, int $variantId, int $qty)
    {
        return $this->withHeaders($headers)->postJson('/api/v1/account/orders', [
            'shop_id' => $shopId,
            'items' => [['variant_id' => $variantId, 'quantity' => $qty]],
            'customer_name' => 'Ayesha',
            'customer_phone' => '01711111111',
            'shipping_address' => 'House 1, Road 2, Dhaka',
            'payment_method' => 'cod',
            // A malicious client-side price must be ignored.
            'total' => 1,
        ]);
    }

    public function test_order_uses_server_prices_and_decrements_variant_stock(): void
    {
        $shop = $this->shopFor($this->owner());
        $listing = $this->list($shop, $this->product($this->admin()), 250, 5);
        $variant = $listing->variants()->first();
        $customer = $this->customer();

        $this->checkout($this->jwt($customer), $shop->id, $variant->id, 2)
            ->assertCreated()
            ->assertJsonPath('data.attributes.total', '500.00')
            ->assertJsonPath('data.attributes.items.0.quantity', 2);

        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame(3, $listing->fresh()->stock);
    }

    public function test_cannot_oversell_or_buy_from_another_shop_or_hidden_listing(): void
    {
        $shopA = $this->shopFor($this->owner());
        $shopB = $this->shopFor($this->owner());
        $listing = $this->list($shopA, $this->product($this->admin()), 100, 1);
        $variant = $listing->variants()->first();
        $headers = $this->jwt($this->customer());

        $this->checkout($headers, $shopA->id, $variant->id, 2)->assertUnprocessable()->assertJsonPath('errors.0.meta.field', 'items.0.quantity');
        $this->checkout($headers, $shopB->id, $variant->id, 1)->assertUnprocessable();

        $listing->update(['is_active' => false]);
        $this->checkout($headers, $shopA->id, $variant->id, 1)->assertUnprocessable();
    }

    public function test_cancelling_an_order_restocks_and_illegal_transitions_are_rejected(): void
    {
        $owner = $this->owner();
        $shop = $this->shopFor($owner);
        $listing = $this->list($shop, $this->product($this->admin()), 100, 5);
        $variant = $listing->variants()->first();

        $number = $this->checkout($this->jwt($this->customer()), $shop->id, $variant->id, 3)->json('data.id');
        $this->assertSame(2, ShopProductVariant::find($variant->id)->stock);

        $ownerHeaders = $this->jwt($owner);
        $this->withHeaders($ownerHeaders)->patchJson("/api/v1/shop/orders/{$number}/status", ['status' => 'delivered'])->assertUnprocessable();
        $this->withHeaders($ownerHeaders)->patchJson("/api/v1/shop/orders/{$number}/status", ['status' => 'cancelled'])->assertOk();

        $this->assertSame(5, ShopProductVariant::find($variant->id)->stock);
        $this->withHeaders($this->jwt($this->owner()))->patchJson("/api/v1/shop/orders/{$number}/status", ['status' => 'processing'])->assertForbidden();
    }

    public function test_customer_cannot_view_someone_elses_order(): void
    {
        $shop = $this->shopFor($this->owner());
        $variant = $this->list($shop, $this->product($this->admin()))->variants()->first();
        $number = $this->checkout($this->jwt($this->customer()), $shop->id, $variant->id, 1)->json('data.id');

        $this->withHeaders($this->jwt($this->customer()))->getJson("/api/v1/account/orders/{$number}")->assertForbidden();
    }
}
