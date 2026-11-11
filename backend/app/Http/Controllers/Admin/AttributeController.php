<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Catalog\DeleteAttributeAction;
use App\Actions\Catalog\SaveAttributeAction;
use App\DataTables\AttributesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreAttributeRequest;
use App\Http\Requests\Api\V1\Admin\UpdateAttributeRequest;
use App\Models\ProductAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttributeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', ProductAttribute::class);

        return view('admin.attributes.index');
    }

    public function data(Request $request, AttributesDataTable $table): JsonResponse
    {
        $this->authorize('viewAny', ProductAttribute::class);

        return $table->toJson($request);
    }

    public function create(): View
    {
        $this->authorize('create', ProductAttribute::class);

        return view('admin.attributes.form', ['attribute' => null]);
    }

    public function store(StoreAttributeRequest $request, SaveAttributeAction $action): RedirectResponse
    {
        $attribute = $action->execute($request->toDto());

        return redirect()->route('admin.attributes.index')->with('success', "Attribute \"{$attribute->name}\" created.");
    }

    public function edit(ProductAttribute $attribute): View
    {
        $this->authorize('update', $attribute);

        return view('admin.attributes.form', ['attribute' => $attribute->load('values')]);
    }

    public function update(UpdateAttributeRequest $request, ProductAttribute $attribute, SaveAttributeAction $action): RedirectResponse
    {
        $action->execute($request->toDto(), $attribute);

        return redirect()->route('admin.attributes.index')->with('success', "Attribute \"{$attribute->name}\" updated.");
    }

    public function destroy(ProductAttribute $attribute, DeleteAttributeAction $action): RedirectResponse
    {
        $this->authorize('delete', $attribute);
        $action->execute($attribute);

        return back()->with('success', 'Attribute deleted.');
    }
}
