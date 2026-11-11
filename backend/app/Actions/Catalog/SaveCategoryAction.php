<?php

namespace App\Actions\Catalog;

use App\DTOs\Catalog\CategoryData;
use App\Models\Category;

final class SaveCategoryAction
{
    public function execute(CategoryData $data, ?Category $category = null): Category
    {
        $category ??= new Category;
        $category->fill($data->toAttributes())->save();

        return $category;
    }
}
