<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Suspending a user takes effect immediately — even for JWTs that were
 * issued before the suspension and have not expired yet.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'errors' => [[
                        'status' => '403',
                        'title' => 'Account suspended',
                        'detail' => __('Your account has been suspended.'),
                    ]],
                ], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('Your account has been suspended.')]);
        }

        return $next($request);
    }
}
