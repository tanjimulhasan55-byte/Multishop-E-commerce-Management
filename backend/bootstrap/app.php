<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\UnauthorizedException as PermissionUnauthorized;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\QueryBuilder\Exceptions\InvalidQuery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Versioned API: one route file per area (routes/api/v1/*.php).
            Route::middleware('api')
                ->prefix('api/v1')
                ->name('api.v1.')
                ->group(function () {
                    foreach (['auth', 'account', 'storefront', 'shop', 'payments', 'admin'] as $file) {
                        require base_path("routes/api/v1/{$file}.php");
                    }
                });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->api(prepend: [SetLocale::class]);
        $middleware->throttleApi('api');

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // SSLCommerz posts back to these from its own domain.
        $middleware->validateCsrfTokens(except: ['api/v1/payments/*']);

        $middleware->redirectGuestsTo(fn (Request $request) => $request->expectsJson() ? null : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // JSON:API error objects for every /api response.
        $isApi = fn (Request $request) => $request->is('api/*');

        $error = fn (int $status, string $title, ?string $detail = null, array $extra = []) => response()->json([
            'errors' => [array_filter([
                'status' => (string) $status,
                'title' => $title,
                'detail' => $detail,
                ...$extra,
            ])],
        ], $status, ['Content-Type' => 'application/vnd.api+json']);

        $exceptions->render(function (ValidationException $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            $errors = collect($e->errors())->flatMap(fn (array $messages, string $field) => collect($messages)
                ->map(fn ($message) => [
                    'status' => '422',
                    'title' => 'Validation Error',
                    'detail' => $message,
                    'source' => ['pointer' => '/'.str_replace('.', '/', $field)],
                    'meta' => ['field' => $field],
                ]))->values();

            return response()->json(['message' => $e->getMessage(), 'errors' => $errors], 422,
                ['Content-Type' => 'application/vnd.api+json']);
        });

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $isApi($request)
            ? $error(401, 'Unauthenticated', __('Please sign in to continue.'))
            : null);

        $exceptions->render(fn (AuthorizationException|PermissionUnauthorized $e, Request $request) => $isApi($request)
            ? $error(403, 'Forbidden', $e->getMessage() ?: __('This action is unauthorized.'))
            : null);

        $exceptions->render(fn (InvalidQuery $e, Request $request) => $isApi($request)
            ? $error(400, 'Invalid Query', $e->getMessage())
            : null);

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($isApi, $error) {
            if (! $isApi($request)) {
                return null;
            }

            // Don't leak model class names.
            $detail = $e->getPrevious() instanceof ModelNotFoundException
                ? __('The requested resource was not found.')
                : __('The requested endpoint was not found.');

            return $error(404, 'Not Found', $detail);
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) use ($isApi, $error) {
            if (! $isApi($request)) {
                return null;
            }

            $status = $e->getStatusCode();
            $title = Response::$statusTexts[$status] ?? 'Error';

            return $error($status, $title, $e->getMessage() ?: null);
        });

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi, $error) {
            if (! $isApi($request) || config('app.debug')) {
                return null;
            }

            return $error(500, 'Server Error', __('Something went wrong. Please try again.'));
        });
    })->create();
