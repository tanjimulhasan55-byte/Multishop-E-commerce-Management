<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ChangePasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\ChangePasswordRequest;
use Illuminate\Http\Response;

/**
 * @tags Account
 */
class ChangePasswordController extends Controller
{
    public function __invoke(ChangePasswordRequest $request, ChangePasswordAction $action): Response
    {
        $action->execute($request->user(), $request->newPassword());

        return response()->noContent();
    }
}
