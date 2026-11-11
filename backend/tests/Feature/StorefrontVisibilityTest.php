<?php

namespace Tests\Feature;

use App\Enums\ShopStatus;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsMarketplace;
use Tests\TestCase;

/** Customers only ever see products assigned to (active) shops. */
class StorefrontVisibilityTest extends TestCase
{
    use BuildsMarketplace, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_only_products_listed_in_an_active_shop_are_visible(): void
    {
        $admin = $this->admin();
        $owner = $this->owner();
        $activeShop = $this->shopFor($owner);
        $inactiveShop = $this->shopFor($owner, ShopStatus::Inactive);

        $listed = $this->product($admin, ['name' => ['en' => 'Listed Lamp']]);
        $unassigned = $this->product($admin, ['name' => ['en' => 'Orphan Chair']]);
        $inInactiveShop = $this->product($admin, ['name' => ['en' => 'Hidden Desk']]);

        $this->list($activeShop, $listed);
        $this->list($inactiveShop, $inInactiveShop);

        $titles = collect($this->getJson('/api/v1/storefront/listings')->assertOk()->json('data'))
            ->pluck('attributes.title');

        $this->assertTrue($titles->contains('Listed Lamp'));
        $this->assertFalse($titles->contains('Orphan Chair'));
        $this->assertFalse($titles->contains('Hidden Desk'));
        $this->assertSame(1, $titles->count());
        $this->assertNotNull($unassigned->id);
    }

    public function test_inactive_listing_and_draft_or_deleted_products_are_hidden(): void
    {
        $admin = $this->admin();
        $shop = $this->shopFor($this->owner());

        $inactiveListing = $this->list($shop, $this->product($admin));
        $inactiveListing->update(['is_active' => false]);

        $draft = $this->product($admin);
        $draftListing = $this->list($shop, $draft);
        $draft->update(['status' => 'draft']);

        $deleted = $this->product($admin);
        $deletedListing = $this->list($shop, $deleted);
        $deleted->delete();

        $this->getJson('/api/v1/storefront/listings')->assertOk()->assertJsonCount(0, 'data');

        foreach ([$inactiveListing, $draftListing, $deletedListing] as $listing) {
            $this->getJson("/api/v1/storefront/listings/{$listing->id}")->assertNotFound();
        }
    }

    public function test_listing_shows_shop_specific_price_and_title(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin, ['name' => ['en' => 'Kettle', 'bn' => 'কেটলি'], 'base_price' => 1000]);
        $shopA = $this->shopFor($this->owner());
        $shopB = $this->shopFor($this->owner());

        $this->list($shopA, $product, 1200);
        $listingB = $this->list($shopB, $product, 990);
        $listingB->update(['title' => ['en' => 'Kettle Deluxe']]);

        $this->getJson("/api/v1/storefront/listings/{$listingB->id}")
            ->assertOk()
            ->assertJsonPath('data.attributes.price', '990.00')
            ->assertJsonPath('data.attributes.title', 'Kettle Deluxe');

        // Bangla falls back per field: no Bangla override title -> English override.
        $this->getJson("/api/v1/storefront/listings/{$listingB->id}", ['Accept-Language' => 'bn'])
            ->assertJsonPath('data.attributes.title', 'Kettle Deluxe');
    }

    public function test_category_filter_includes_sub_categories(): void
    {
        $admin = $this->admin();
        $fashion = Category::create(['name' => ['en' => 'Fashion']]);
        $men = Category::create(['name' => ['en' => 'Men'], 'parent_id' => $fashion->id]);
        $shirts = Category::create(['name' => ['en' => 'Shirts'], 'parent_id' => $men->id]);

        $this->assertSame("/{$fashion->id}/{$men->id}/{$shirts->id}/", $shirts->fresh()->path);
        $this->assertSame(2, $shirts->fresh()->depth);

        $shop = $this->shopFor($this->owner());
        $this->list($shop, $this->product($admin, ['category_id' => $shirts->id]));

        $this->getJson('/api/v1/storefront/listings?filter[category]=fashion')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/storefront/listings?filter[category]=shirts')->assertJsonCount(1, 'data');

        // Moving a subtree re-paths every descendant.
        $other = Category::create(['name' => ['en' => 'Sale']]);
        $men->update(['parent_id' => $other->id]);
        $this->assertSame("/{$other->id}/{$men->id}/{$shirts->id}/", $shirts->fresh()->path);
        $this->getJson('/api/v1/storefront/listings?filter[category]=fashion')->assertJsonCount(0, 'data');
    }
}
