<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Listings\RemoveListingAction;
use App\DataTables\ListingsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Read-only overview of what every shop sells (owners edit their own listings in the SPA). */
class ListingController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ShopProduct::class);

        return view('admin.listings.index', [
            'shops' => Shop::query()->orderBy('name_en')->get()->mapWithKeys(fn (Shop $s) => [$s->id => $s->name])->all(),
        ]);
    }

    public function data(Request $request, ListingsDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', ShopProduct::class);

        return $table->toJson($request);
    }

    public function destroy(ShopProduct $listing, RemoveListingAction $action): RedirectResponse
    {
        $this->authorize('delete', $listing);
        $action->execute($listing);

        return back()->with('success', 'Product removed from the shop.');
    }
}
