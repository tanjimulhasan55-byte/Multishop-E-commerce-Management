<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Negotiates the content locale from ?lang= or Accept-Language (en|bn).
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en']);

        $locale = $request->query('lang');
        $locale = is_string($locale) && in_array($locale, $supported, true)
            ? $locale
            : $request->getPreferredLanguage($supported);

        app()->setLocale($locale ?? config('app.locale'));

        $response = $next($request);
        $response->headers->set('Content-Language', app()->getLocale());
        $response->headers->set('Vary', trim($response->headers->get('Vary').', Accept-Language', ', '));

        return $response;
    }
}
