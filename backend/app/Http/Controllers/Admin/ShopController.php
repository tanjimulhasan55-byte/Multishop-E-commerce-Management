<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Shops\DeleteShopAction;
use App\Actions\Shops\SaveShopAction;
use App\DataTables\ShopsDataTable;
use App\Enums\RoleName;
use App\Enums\ShopStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreShopRequest;
use App\Http\Requests\Api\V1\Admin\UpdateShopRequest;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Shop::class);

        return view('admin.shops.index', [
            'owners' => $this->ownerOptions(),
            'statuses' => ShopStatus::options(),
        ]);
    }

    public function data(Request $request, ShopsDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', Shop::class);

        return $table->toJson($request);
    }

    public function create(): View
    {
        $this->authorize('create', Shop::class);

        return view('admin.shops.form', ['shop' => null, 'owners' => $this->ownerOptions(), 'statuses' => ShopStatus::options()]);
    }

    public function store(StoreShopRequest $request, SaveShopAction $action): RedirectResponse
    {
        $shop = $action->execute($request->toDto());

        return redirect()->route('admin.shops.index')->with('success', "Shop \"{$shop->name}\" created.");
    }

    public function edit(Shop $shop): View
    {
        $this->authorize('update', $shop);

        return view('admin.shops.form', ['shop' => $shop->load('media'), 'owners' => $this->ownerOptions(), 'statuses' => ShopStatus::options()]);
    }

    public function update(UpdateShopRequest $request, Shop $shop, SaveShopAction $action): RedirectResponse
    {
        $action->execute($request->toDto(), $shop);

        return redirect()->route('admin.shops.index')->with('success', "Shop \"{$shop->name}\" updated.");
    }

    public function destroy(Shop $shop, DeleteShopAction $action): RedirectResponse
    {
        $this->authorize('delete', $shop);
        $action->execute($shop);

        return back()->with('success', 'Shop deleted.');
    }

    /** @return array<int, string> */
    private function ownerOptions(): array
    {
        return User::query()->role(RoleName::ShopOwner->value)->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
            ->all();
    }
}
