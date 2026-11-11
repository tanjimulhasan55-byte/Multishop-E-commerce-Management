<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\Payments\SslCommerzGateway;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, fn () => new SslCommerzGateway(
            storeId: config('services.sslcommerz.store_id'),
            storePassword: config('services.sslcommerz.store_password'),
            sandbox: (bool) config('services.sslcommerz.sandbox'),
            timeout: (int) config('services.sslcommerz.timeout', 30),
        ));
    }

    public function boot(): void
    {
        // `php artisan serve` starts PHP with a stripped environment. On Windows
        // that drops TEMP/TMP, so PHP cannot create upload temp files and every
        // image upload fails. Pass them through.
        if ($this->app->runningInConsole()) {
            ServeCommand::$passthroughVariables = array_values(array_unique([
                ...ServeCommand::$passthroughVariables, 'TEMP', 'TMP', 'TMPDIR',
            ]));
        }

        // Catch N+1 queries, silently discarded attributes and typos early.
        Model::shouldBeStrict(! $this->app->isProduction());
        // Safety net against N+1: relations touched on one model of a collection
        // are eager-loaded for the whole collection.
        Model::automaticallyEagerLoadRelationships();

        Password::defaults(fn () => $this->app->isProduction()
            ? Password::min(10)->letters()->mixedCase()->numbers()->uncompromised()
            : Password::min(8)->letters()->numbers());

        $this->configureAuthorization();
        $this->configureRateLimiting();
        $this->configureApiDocs();

        JsonApiResource::configure(version: '1.1');
    }

    private function configureAuthorization(): void
    {
        // "admin" is the super-admin role: every ability is granted. All other
        // roles (system or custom) get exactly the permissions assigned to them.
        Gate::before(fn (User $user) => $user->hasRole(RoleName::Admin->value) ? true : null);

        Gate::policy(Role::class, RolePolicy::class);

        Gate::define('viewApiDocs', fn (?User $user = null) => $this->app->isLocal()
            || $user?->can(PermissionName::DashboardView->value));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getKey() ?: $request->ip()));

        // Brute-force protection: per email+IP and per IP.
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)
            ->by($request->user()?->getKey() ?: $request->ip()));

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)
            ->by($request->user()?->getKey() ?: $request->ip()));
    }

    private function configureApiDocs(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer', 'JWT'));
            });
    }
}
