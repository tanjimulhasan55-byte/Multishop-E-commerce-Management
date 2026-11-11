<?php

namespace Database\Seeders;

use App\Actions\Catalog\CreateProductAction;
use App\Actions\Catalog\SaveAttributeAction;
use App\Actions\Catalog\Variants\GenerateVariantsAction;
use App\Actions\Listings\AddProductToShopAction;
use App\Actions\Listings\RefreshListingAggregatesAction;
use App\DTOs\Catalog\AttributeData;
use App\DTOs\Catalog\ProductData;
use App\DTOs\Listings\ListingData;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\RoleName;
use App\Enums\ShopStatus;
use App\Enums\UserStatus;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Shop;
use App\Models\ShopProduct;
use App\Models\User;
use Database\Seeders\Support\PlaceholderImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Realistic bilingual (English / Bangla) demo data, created through the
 * same Actions the application uses.
 *
 * Demonstrates the core rules: "Portable Projector" and "Bamboo Cutting
 * Board" exist in the catalog but are not assigned to any shop, "Aloe Vera
 * Gel" is a draft, and "Rajshahi Book Corner" is inactive — none of those
 * are visible in the customer storefront.
 */
class DemoDataSeeder extends Seeder
{
    private int $imageSeed = 0;

    public function run(): void
    {
        // Generate image conversions synchronously while seeding so the demo
        // has thumbnails even before a queue worker is started.
        config(['media-library.queue_conversions_by_default' => false]);

        DB::transaction(function () {
            $users = $this->users();
            $categories = $this->categories();
            $attributes = $this->attributes();
            $products = $this->products($categories, $attributes, $users);
            $shops = $this->shops($users);
            $this->listings($shops, $products);
            $this->orders($users['customer'], $shops);
        });
    }

    /** @return array<string, User> */
    private function users(): array
    {
        $make = function (string $name, string $email, string $phone, array $roles) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'phone' => $phone,
                'password' => 'password',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);
            $user->syncRoles($roles);

            return $user;
        };

        return [
            'admin' => $make('System Admin', 'admin@multishop.com', '01700000001', [RoleName::Admin->value]),
            'manager' => $make('Catalog Manager', 'manager@multishop.com', '01700000002', ['catalog_manager']),
            'rahim' => $make('Rahim Uddin', 'rahim@multishop.com', '01711111111', [RoleName::ShopOwner->value]),
            'karim' => $make('Karim Hossain', 'karim@multishop.com', '01722222222', [RoleName::ShopOwner->value]),
            'nusrat' => $make('Nusrat Jahan', 'nusrat@multishop.com', '01733333333', [RoleName::ShopOwner->value]),
            'customer' => $make('Ayesha Rahman', 'customer@multishop.com', '01744444444', [RoleName::Customer->value]),
        ];
    }

    /** @return array<string, Category> keyed by slug */
    private function categories(): array
    {
        // [en, bn, children]
        $tree = [
            ['Electronics', 'ইলেকট্রনিক্স', [
                ['Audio', 'অডিও', []],
                ['Wearables', 'পরিধানযোগ্য ডিভাইস', []],
                ['Accessories', 'এক্সেসরিজ', []],
            ]],
            ['Fashion', 'ফ্যাশন', [
                ['Men', 'পুরুষ', [['T-Shirts', 'টি-শার্ট', []], ['Panjabi', 'পাঞ্জাবি', []]]],
                ['Women', 'নারী', [['Sarees', 'শাড়ি', []]]],
                ['Footwear', 'জুতা', []],
                ['Bags & Wallets', 'ব্যাগ ও মানিব্যাগ', []],
            ]],
            ['Home & Kitchen', 'ঘর ও রান্নাঘর', []],
            ['Groceries', 'মুদি পণ্য', []],
            ['Books', 'বই', []],
            ['Beauty & Health', 'সৌন্দর্য ও স্বাস্থ্য', []],
        ];

        $out = [];
        $walk = function (array $nodes, ?Category $parent) use (&$walk, &$out) {
            foreach ($nodes as $i => [$en, $bn, $children]) {
                $category = Category::create([
                    'parent_id' => $parent?->id,
                    'name' => ['en' => $en, 'bn' => $bn],
                    'is_active' => true,
                    'sort_order' => $i,
                ]);
                $out[$category->slug] = $category;
                $walk($children, $category);
            }
        };
        $walk($tree, null);

        return $out;
    }

    /** @return array<string, ProductAttribute> */
    private function attributes(): array
    {
        $save = app(SaveAttributeAction::class);
        $make = fn (array $name, string $type, int $order, array $values) => $save->execute(AttributeData::fromArray([
            'name' => $name, 'type' => $type, 'sort_order' => $order, 'values' => $values,
        ]));

        return [
            'color' => $make(['en' => 'Color', 'bn' => 'রং'], ProductAttribute::TYPE_COLOR, 0, [
                ['value' => ['en' => 'Black', 'bn' => 'কালো'], 'code' => 'BLK', 'color_hex' => '#111827'],
                ['value' => ['en' => 'White', 'bn' => 'সাদা'], 'code' => 'WHT', 'color_hex' => '#F9FAFB'],
                ['value' => ['en' => 'Navy', 'bn' => 'নেভি'], 'code' => 'NVY', 'color_hex' => '#1E3A8A'],
                ['value' => ['en' => 'Maroon', 'bn' => 'মেরুন'], 'code' => 'MRN', 'color_hex' => '#7F1D1D'],
            ]),
            'size' => $make(['en' => 'Size', 'bn' => 'সাইজ'], ProductAttribute::TYPE_SELECT, 1, [
                ['value' => ['en' => 'S'], 'code' => 'S'],
                ['value' => ['en' => 'M'], 'code' => 'M'],
                ['value' => ['en' => 'L'], 'code' => 'L'],
                ['value' => ['en' => 'XL'], 'code' => 'XL'],
                ['value' => ['en' => 'XXL'], 'code' => 'XXL'],
            ]),
            'shoe' => $make(['en' => 'Shoe Size', 'bn' => 'জুতার মাপ'], ProductAttribute::TYPE_SELECT, 2, [
                ['value' => ['en' => 'EU 40'], 'code' => '40'],
                ['value' => ['en' => 'EU 41'], 'code' => '41'],
                ['value' => ['en' => 'EU 42'], 'code' => '42'],
                ['value' => ['en' => 'EU 43'], 'code' => '43'],
            ]),
        ];
    }

    /** @return array<string, Product> keyed by a short handle */
    private function products(array $categories, array $attributes, array $users): array
    {
        $create = app(CreateProductAction::class);
        $generate = app(GenerateVariantsAction::class);

        $values = fn (string $attr, array $codes) => AttributeValue::query()
            ->where('attribute_id', $attributes[$attr]->id)->whereIn('code', $codes)->pluck('id')->all();

        // handle => [category slug, en, bn, price, creator, description, sku, status, options]
        $rows = [
            'earbuds' => ['audio', 'Wireless Bluetooth Earbuds', 'ওয়্যারলেস ব্লুটুথ ইয়ারবাড', 2450, 'admin', 'True wireless earbuds with 24-hour battery life and noise isolation.', 'EARBUD-01', null,
                ['color' => ['BLK', 'WHT']]],
            'watch' => ['wearables', 'Smart Fitness Watch', 'স্মার্ট ফিটনেস ঘড়ি', 3990, 'admin', 'Heart-rate, SpO2 and sleep tracking with a 1.8" AMOLED display.', 'WATCH-01', null, []],
            'powerbank' => ['accessories', '20000mAh Power Bank', '২০০০০ এমএএইচ পাওয়ার ব্যাংক', 1850, 'admin', 'Fast-charging power bank with dual USB-C ports.', null, null, []],
            'keyboard' => ['accessories', 'Mechanical Keyboard', 'মেকানিক্যাল কীবোর্ড', 4200, 'rahim', 'Hot-swappable mechanical keyboard with RGB backlight.', 'KB-RGB', null, []],
            'tshirt' => ['t-shirts', 'Premium Cotton T-Shirt', 'প্রিমিয়াম সুতি টি-শার্ট', 650, 'admin', '180 GSM combed cotton crew-neck t-shirt.', 'TSHIRT-01', null,
                ['color' => ['BLK', 'WHT', 'NVY'], 'size' => ['S', 'M', 'L', 'XL']]],
            'panjabi' => ['panjabi', 'Cotton Panjabi', 'সুতি পাঞ্জাবি', 1650, 'admin', 'Breathable premium cotton panjabi, perfect for festivals.', 'PANJ-01', null,
                ['color' => ['WHT', 'MRN'], 'size' => ['M', 'L', 'XL', 'XXL']]],
            'saree' => ['sarees', 'Handloom Saree', 'তাঁতের শাড়ি', 3200, 'karim', 'Traditional handloom saree woven by Tangail artisans.', null, null, []],
            'sneakers' => ['footwear', 'Everyday Sneakers', 'দৈনন্দিন স্নিকার্স', 2800, 'karim', 'Lightweight breathable sneakers with cushioned sole.', 'SNKR-01', null,
                ['color' => ['BLK', 'WHT'], 'shoe' => ['40', '41', '42', '43']]],
            'wallet' => ['bags-wallets', 'Leather Wallet', 'চামড়ার মানিব্যাগ', 890, 'admin', 'Genuine leather bi-fold wallet with RFID protection.', null, null, []],
            'frypan' => ['home-kitchen', 'Non-stick Fry Pan', 'নন-স্টিক ফ্রাই প্যান', 1350, 'admin', '28cm granite-coated non-stick fry pan.', null, null, []],
            'kettle' => ['home-kitchen', 'Electric Kettle 1.8L', 'ইলেকট্রিক কেটলি ১.৮ লিটার', 1550, 'admin', 'Stainless steel kettle with auto shut-off.', null, null, []],
            'rice' => ['groceries', 'Miniket Rice 5kg', 'মিনিকেট চাল ৫ কেজি', 420, 'admin', 'Premium polished miniket rice.', null, null, []],
            'honey' => ['groceries', 'Sundarbans Honey 500g', 'সুন্দরবনের মধু ৫০০ গ্রাম', 650, 'nusrat', 'Pure natural honey collected from the Sundarbans.', null, null, []],
            'oil' => ['groceries', 'Mustard Oil 1L', 'সরিষার তেল ১ লিটার', 310, 'admin', 'Cold-pressed mustard oil.', null, null, []],
            'laravel' => ['books', 'Learning Laravel', 'লারাভেল শেখা', 750, 'admin', 'A practical guide to modern Laravel development.', null, null, []],
            'stories' => ['books', 'Bangla Short Stories', 'বাংলা ছোটগল্প সংকলন', 480, 'admin', 'A curated anthology of classic Bangla short stories.', null, null, []],
            'facewash' => ['beauty-health', 'Herbal Face Wash', 'হারবাল ফেস ওয়াশ', 380, 'admin', 'Neem and turmeric face wash for daily use.', null, null, []],
            // In the catalog but not assigned to any shop => never visible to customers.
            'projector' => ['electronics', 'Portable Projector', 'পোর্টেবল প্রজেক্টর', 12500, 'admin', 'Mini HD projector (catalog only — not listed by any shop).', null, null, []],
            'board' => ['home-kitchen', 'Bamboo Cutting Board', 'বাঁশের কাটিং বোর্ড', 560, 'rahim', 'Eco-friendly bamboo board (catalog only).', null, null, []],
            'aloe' => ['beauty-health', 'Aloe Vera Gel', 'অ্যালোভেরা জেল', 290, 'admin', 'Soothing aloe vera gel (draft — not in the catalog yet).', null, ProductStatus::Draft, []],
        ];

        $products = [];
        foreach ($rows as $handle => [$cat, $en, $bn, $price, $creator, $desc, $sku, $status, $options]) {
            $product = $create->execute(ProductData::fromArray([
                'name' => ['en' => $en, 'bn' => $bn],
                'description' => ['en' => $desc],
                'category_id' => $categories[$cat]->id,
                'sku' => $sku,
                'base_price' => $price,
                'status' => ($status ?? ProductStatus::Active)->value,
            ]), $users[$creator]);

            $this->attachImage($product, $en, 'thumbnail');

            if ($options !== []) {
                $ids = collect($options)->flatMap(fn ($codes, $attr) => $values($attr, $codes))->all();
                $generate->execute($product, $ids);

                // XL / XXL cost a little more.
                $product->variants()->whereHas('attributeValues', fn ($q) => $q->whereIn('code', ['XL', 'XXL']))
                    ->update(['base_price' => $price + 50]);
            }

            $products[$handle] = $product;
        }

        return $products;
    }

    /** @return array<string, Shop> */
    private function shops(array $users): array
    {
        $data = [
            'gadget' => ['Dhaka Gadget House', 'ঢাকা গ্যাজেট হাউস', 'rahim', 'Genuine gadgets and accessories with warranty.', 'Banani, Dhaka', ShopStatus::Active],
            'fashion' => ['Chattogram Fashion Hub', 'চট্টগ্রাম ফ্যাশন হাব', 'karim', 'Traditional and modern clothing.', 'GEC Circle, Chattogram', ShopStatus::Active],
            'mart' => ['Sylhet Fresh Mart', 'সিলেট ফ্রেশ মার্ট', 'nusrat', 'Daily groceries, fashion basics and home essentials.', 'Zindabazar, Sylhet', ShopStatus::Active],
            'books' => ['Rajshahi Book Corner', 'রাজশাহী বুক কর্নার', 'karim', 'Books for every reader (currently inactive).', 'Shaheb Bazar, Rajshahi', ShopStatus::Inactive],
        ];

        $shops = [];
        foreach ($data as $key => [$en, $bn, $owner, $desc, $address, $status]) {
            $shop = Shop::create([
                'owner_id' => $users[$owner]->id,
                'name' => ['en' => $en, 'bn' => $bn],
                'description' => ['en' => $desc],
                'email' => $key.'@multishop.com',
                'phone' => '0181'.str_pad((string) (count($shops) + 1), 7, '0', STR_PAD_LEFT),
                'address' => $address,
                'status' => $status,
            ]);
            $this->attachImage($shop, $en, 'logo');
            $shops[$key] = $shop;
        }

        return $shops;
    }

    /** The same catalog product listed by different shops at different prices. */
    private function listings(array $shops, array $products): void
    {
        $add = app(AddProductToShopAction::class);
        $refresh = app(RefreshListingAggregatesAction::class);

        $plan = [
            'gadget' => ['earbuds' => 1.00, 'watch' => 0.97, 'powerbank' => 1.05, 'keyboard' => 1.00, 'wallet' => 1.10],
            'fashion' => ['tshirt' => 1.00, 'panjabi' => 0.95, 'saree' => 1.00, 'sneakers' => 1.00, 'wallet' => 1.00],
            'mart' => ['tshirt' => 0.92, 'frypan' => 1.00, 'kettle' => 0.98, 'rice' => 1.00, 'honey' => 1.00, 'oil' => 1.02, 'powerbank' => 1.08, 'facewash' => 1.00],
            'books' => ['laravel' => 1.00, 'stories' => 1.00],
        ];

        $stockCycle = [25, 8, 50, 3, 0, 12, 40, 6];
        $i = 0;

        foreach ($plan as $shopKey => $items) {
            foreach ($items as $handle => $factor) {
                $product = $products[$handle];

                $listing = $add->execute($shops[$shopKey], $product, ListingData::fromArray([
                    'price' => round((float) $product->base_price * $factor),
                    'compare_at_price' => $factor < 1 ? $product->base_price : null,
                    'stock' => 10,
                    'is_active' => true,
                ]));

                // Per-variant shop pricing (variant base price × shop factor) and varied stock.
                foreach ($listing->variants()->with('variant')->get() as $sv) {
                    $sv->update([
                        'price' => round((float) $sv->variant->base_price * $factor),
                        'compare_at_price' => $factor < 1 ? $sv->variant->base_price : null,
                        'stock' => $stockCycle[$i++ % count($stockCycle)],
                    ]);
                }
                $refresh->execute($listing);

                // Shop-specific title + thumbnail override example.
                if ($shopKey === 'gadget' && $handle === 'earbuds') {
                    $listing->update(['title' => ['en' => 'Earbuds Pro (Official Warranty)', 'bn' => 'ইয়ারবাড প্রো (অফিসিয়াল ওয়ারেন্টি)']]);
                    $this->attachImage($listing, 'Earbuds Pro', 'thumbnail');
                }
            }
        }
    }

    private function orders(User $customer, array $shops): void
    {
        $samples = [
            ['gadget', 2, OrderStatus::Delivered, PaymentStatus::Paid, PaymentMethod::SslCommerz, 12],
            ['mart', 3, OrderStatus::Processing, PaymentStatus::Unpaid, PaymentMethod::CashOnDelivery, 3],
            ['fashion', 2, OrderStatus::Pending, PaymentStatus::Unpaid, PaymentMethod::CashOnDelivery, 0],
        ];

        foreach ($samples as [$shopKey, $lineCount, $status, $paymentStatus, $method, $daysAgo]) {
            $shop = $shops[$shopKey];

            $variants = ShopProduct::query()->where('shop_id', $shop->id)
                ->with(['product', 'variants.variant.attributeValues.attribute'])
                ->get()
                ->flatMap(fn (ShopProduct $l) => $l->variants->where('stock', '>', 0))
                ->take($lineCount);

            $subtotal = $variants->sum(fn ($sv) => (float) $sv->price);

            $order = Order::create([
                'number' => Order::generateNumber(),
                'user_id' => $customer->id,
                'shop_id' => $shop->id,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_method' => $method,
                'subtotal' => $subtotal,
                'shipping_total' => 0,
                'total' => $subtotal,
                'currency' => 'BDT',
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'shipping_address' => 'House 12, Road 5, Dhanmondi, Dhaka 1205',
                'placed_at' => now()->subDays($daysAgo),
            ]);

            foreach ($variants as $sv) {
                $listing = $sv->listing;
                $order->items()->create([
                    'shop_product_id' => $listing->id,
                    'shop_product_variant_id' => $sv->id,
                    'product_id' => $listing->product_id,
                    'product_variant_id' => $sv->product_variant_id,
                    'product_name' => $listing->displayName('en'),
                    'variant_label' => $sv->variant->label('en'),
                    'sku' => $sv->variant->sku,
                    'unit_price' => $sv->price,
                    'quantity' => 1,
                    'line_total' => $sv->price,
                ]);
            }
        }
    }

    private function attachImage($model, string $label, string $collection): void
    {
        $path = PlaceholderImage::make($label, $this->imageSeed++);

        $model->addMedia($path)
            ->usingFileName(Str::slug($label).'.png')
            ->toMediaCollection($collection);
    }
}
