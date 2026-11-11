<?php

namespace App\Http\Controllers\Api\V1\Admin\ShopOwners;

use App\Enums\RoleName;
use App\Http\Controllers\Api\V1\Concerns\Paginates;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @tags Admin
 */
class IndexShopOwnersController extends Controller
{
    use Paginates;

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $owners = QueryBuilder::for(User::query()->role(RoleName::ShopOwner->value)->with('roles')->withCount('shops'))
            ->allowedFilters(
                AllowedFilter::exact('status'),
                AllowedFilter::callback('search', function (Builder $q, $v) {
                    $like = '%'.addcslashes((string) $v, '%_\\').'%';
                    $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like));
                }),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return UserResource::collection($owners);
    }
}
