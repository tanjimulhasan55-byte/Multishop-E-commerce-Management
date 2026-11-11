<?php

namespace App\Http\Controllers\Api\V1\Admin\ShopOwners;

use App\Actions\Users\DeleteUserAction;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Admin
 */
class DestroyShopOwnerController extends Controller
{
    public function __invoke(Request $request, User $user, DeleteUserAction $action): Response
    {
        abort_unless($user->hasRole(RoleName::ShopOwner), 404);
        $this->authorize('delete', $user);

        $action->execute($user, $request->user());

        return response()->noContent();
    }
}
