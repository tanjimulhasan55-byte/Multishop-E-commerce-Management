<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    /**
     * Each simulated request must authenticate on its own, like real HTTP:
     * forget guards so a user resolved by a previous request (e.g. from
     * another Bearer token) is not reused.
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        // jwt-auth keeps the parsed token in container singletons.
        foreach (['tymon.jwt', 'tymon.jwt.auth', 'tymon.jwt.parser'] as $abstract) {
            $this->app->forgetInstance($abstract);
        }
        JWTAuth::clearResolvedInstances();

        // Session-authenticated tests (actingAs) keep their user; token requests re-authenticate.
        if (isset($server['HTTP_AUTHORIZATION']) || str_starts_with((string) $uri, '/api/') || str_contains((string) $uri, '/api/')) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
