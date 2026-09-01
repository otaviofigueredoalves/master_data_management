<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bound on demand — the EnsureUserHasTenant middleware replaces the
        // instance for the duration of the request.
        $this->app->singleton(TenantContext::class, fn () => new TenantContext(null, null));
    }

    public function boot(): void
    {
        //
    }
}
