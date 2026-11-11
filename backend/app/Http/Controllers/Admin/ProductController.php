<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\CreateProductAction;
use App\Actions\Catalog\DeleteProductAction;
use App\Actions\Catalog\UpdateProductAction;
use App\DataTables\ProductsDataTable;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('admin.products.index', [
            'categories' => self::categoryOptions(),
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function data(Request $request, ProductsDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return $table->toJson($request);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('admin.products.form', [
            'product' => null,
            'categories' => self::categoryOptions(),
            'statuses' => ProductStatus::options(),
        ]);
    }

    public function store(StoreProductRequest $request, CreateProductAction $action): RedirectResponse
    {
        $product = $action->execute($request->toDto(), $request->user());

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created. You can now add variants (size, colour …).');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load([...Product::detailRelations(), 'listings.shop'])->loadCount('listings');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => self::categoryOptions(),
            'statuses' => ProductStatus::options(),
            'attributes' => ProductAttribute::query()->with('values')->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, UpdateProductAction $action): RedirectResponse
    {
        $action->execute($product, $request->toDto());

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product, DeleteProductAction $action): RedirectResponse
    {
        $this->authorize('delete', $product);
        $action->execute($product);

        return redirect()->route('admin.products.index')->with('success', 'Product removed from the catalog.');
    }

    /**
     * Indented options from the category tree ("— — T-Shirts"), ordered by path.
     *
     * @return array<int, string>
     */
    public static function categoryOptions(): array
    {
        return Category::query()->get()
            ->sortBy(fn (Category $c) => self::sortablePath($c))
            ->mapWithKeys(fn (Category $c) => [$c->id => str_repeat('— ', $c->depth).$c->name])
            ->all();
    }

    /** Natural ordering of materialized paths by each ancestor's sort order. */
    private static function sortablePath(Category $c): string
    {
        static $cache = [];
        $cache = $cache ?: Category::query()->pluck('sort_order', 'id')->all();

        return collect(array_filter(explode('/', $c->path)))
            ->map(fn ($id) => str_pad((string) ($cache[$id] ?? 0), 5, '0', STR_PAD_LEFT).str_pad($id, 8, '0', STR_PAD_LEFT))
            ->implode('/');
    }
}
