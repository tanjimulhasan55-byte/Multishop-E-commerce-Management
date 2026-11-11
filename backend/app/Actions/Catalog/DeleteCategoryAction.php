<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use Illuminate\Validation\ValidationException;

final class DeleteCategoryAction
{
    /** Products keep existing; their category_id is nulled by the FK. */
    public function execute(Category $category): void
    {
        if ($category->children()->exists()) {
            throw ValidationException::withMessages([
                'category' => __('Move or delete the sub-categories first.'),
            ]);
        }

        $category->delete();
    }
}
