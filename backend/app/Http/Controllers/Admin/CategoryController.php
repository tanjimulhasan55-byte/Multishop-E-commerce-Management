<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\DeleteCategoryAction;
use App\Actions\Catalog\SaveCategoryAction;
use App\DataTables\CategoriesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('admin.categories.index');
    }

    public function data(Request $request, CategoriesDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        return $table->toJson($request);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.form', [
            'category' => null,
            'parents' => $this->parentOptions(),
            'defaultParent' => $request->integer('parent_id') ?: null,
        ]);
    }

    public function store(StoreCategoryRequest $request, SaveCategoryAction $action): RedirectResponse
    {
        $category = $action->execute($request->toDto());

        return redirect()->route('admin.categories.index')->with('success', "Category \"{$category->name}\" created.");
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.form', [
            'category' => $category,
            'parents' => $this->parentOptions($category),
            'defaultParent' => null,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, SaveCategoryAction $action): RedirectResponse
    {
        $action->execute($request->toDto(), $category);

        return redirect()->route('admin.categories.index')->with('success', "Category \"{$category->name}\" updated.");
    }

    public function destroy(Category $category, DeleteCategoryAction $action): RedirectResponse
    {
        $this->authorize('delete', $category);
        $action->execute($category);

        return back()->with('success', 'Category deleted.');
    }

    /** Tree options, excluding the edited node and its subtree (prevents cycles). */
    private function parentOptions(?Category $exclude = null): array
    {
        $options = ProductController::categoryOptions();

        if ($exclude) {
            $blocked = Category::query()->subtreeOf($exclude)->pluck('id')->all();
            $options = array_diff_key($options, array_flip($blocked));
        }

        return $options;
    }
}
