<?php

namespace App\Actions\Orders;

use App\Actions\Listings\RefreshListingAggregatesAction;
use App\DTOs\Orders\CheckoutData;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\ShopProductVariant;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PlaceOrderAction
{
    public function __construct(private readonly RefreshListingAggregatesAction $refresh) {}

    /**
     * Prices and availability always come from the database — never from the
     * client. Shop-variant rows are locked (SELECT … FOR UPDATE) so
     * concurrent checkouts cannot oversell stock.
     */
    public function execute(User $customer, CheckoutData $data): Order
    {
        $order = DB::transaction(function () use ($customer, $data) {
            $shop = Shop::query()->active()->find($data->shopId)
                ?? throw ValidationException::withMessages(['shop_id' => __('This shop is not accepting orders.')]);

            $variants = ShopProductVariant::query()
                ->whereKey(array_map(fn ($i) => $i->variantId, $data->items))
                ->where('is_active', true)
                ->whereHas('listing', fn (Builder $q) => $q->visibleToCustomers()->where('shop_id', $shop->getKey()))
                ->with(['listing.product', 'variant.attributeValues.attribute'])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lines = [];
            $subtotalCents = 0;

            foreach ($data->items as $index => $item) {
                /** @var ShopProductVariant|null $sv */
                $sv = $variants->get($item->variantId)
                    ?? throw ValidationException::withMessages([
                        "items.$index.variant_id" => __('One of the products is no longer available in this shop.'),
                    ]);

                if ($sv->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => __('Only :stock left of ":name".', [
                            'stock' => $sv->stock,
                            'name' => trim($sv->listing->displayName().' '.$sv->variant->label()),
                        ]),
                    ]);
                }

                // Integer cents avoid float rounding errors.
                $unitCents = (int) round(((float) $sv->price) * 100);
                $lineCents = $unitCents * $item->quantity;
                $subtotalCents += $lineCents;

                $lines[] = [$sv, $item->quantity, $unitCents, $lineCents];
            }

            $order = Order::create([
                'number' => Order::generateNumber(),
                'user_id' => $customer->getKey(),
                'shop_id' => $shop->getKey(),
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
                'payment_method' => $data->paymentMethod,
                'subtotal' => $subtotalCents / 100,
                'shipping_total' => 0,
                'total' => $subtotalCents / 100,
                'currency' => 'BDT',
                'customer_name' => $data->customerName,
                'customer_phone' => $data->customerPhone,
                'shipping_address' => $data->shippingAddress,
                'notes' => $data->notes,
                'placed_at' => now(),
            ]);

            foreach ($lines as [$sv, $qty, $unitCents, $lineCents]) {
                $order->items()->create([
                    'shop_product_id' => $sv->shop_product_id,
                    'shop_product_variant_id' => $sv->getKey(),
                    'product_id' => $sv->listing->product_id,
                    'product_variant_id' => $sv->product_variant_id,
                    'product_name' => $sv->listing->displayName('en'),
                    'variant_label' => $sv->variant->label('en'),
                    'sku' => $sv->variant->sku,
                    'unit_price' => $unitCents / 100,
                    'quantity' => $qty,
                    'line_total' => $lineCents / 100,
                ]);

                $sv->decrement('stock', $qty);
            }

            collect($lines)->map(fn ($l) => $l[0]->shop_product_id)->unique()
                ->each(fn ($id) => $this->refresh->execute(ShopProduct::findOrFail($id)));

            return $order;
        });

        // Queued notification; dispatched after commit (see notification).
        $order->shop?->owner?->notify(new NewOrderNotification($order));

        return $order->load('items');
    }
}
