<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Auth
 */
class LogoutController extends Controller
{
    /** Invalidate (blacklist) the current token. */
    public function __invoke(): Response
    {
        Auth::guard('api')->logout();

        return response()->noContent();
    }
}
