<?php

use App\Http\Middleware\CheckUserPermission;
use App\Http\Middleware\CheckUserRole;
use App\Http\Middleware\EnsureUserHasTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant.auth' => EnsureUserHasTenant::class,
            'role' => CheckUserRole::class,
            'permission' => CheckUserPermission::class,
        ]);

        // The SPA is a pure bearer-token client (Sanctum personal access
        // tokens + X-Tenant-ID header). Enabling statefulApi() here would make
        // Sanctum treat requests coming from the SPA origin (localhost:8000) as
        // cookie/session-based and force CSRF verification on every POST — which
        // the SPA never performs — producing 419 "session expired" responses.
        // The API must stay stateless; only the web routes (Fortify/socialite)
        // keep their regular session handling.

        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
