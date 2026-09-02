<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\TenantSelectController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Vue SPA — owns the whole browser experience (landing, auth and workspace).
Route::get('/', fn () => view('spa'))->name('home');

Route::get('/docs', fn () => view('docs'))->name('docs');

// Convenience for interviews/local demos — never exposed outside local env.
if (app()->environment('local', 'testing')) {
    Route::get('/demo-login', function () {
        $user = User::where('email', 'demo@mdmsaas.test')->first();

        abort_unless($user, 404, 'Execute php artisan db:seed antes do demo login.');

        auth()->login($user);

        session(['current_tenant_id' => $user->current_tenant_id]);

        return redirect()->intended('/');
    })->name('demo.login');
}

// Tenant selection helper for browser sessions (used by the tenant.auth middleware).
Route::middleware('auth')->group(function () {
    Route::get('/tenants/select', [TenantSelectController::class, 'create'])->name('tenants.select');
    Route::post('/tenants/switch', [TenantSelectController::class, 'store'])->name('tenants.switch');
});

// Socialite — only exposes providers that have credentials configured.
Route::prefix('auth')->group(function () {
    Route::get('/{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->middleware(['guest', 'throttle:5,1'])
        ->whereIn('provider', ['google', 'github', 'microsoft'])
        ->name('socialite.redirect');

    Route::get('/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'github', 'microsoft'])
        ->name('socialite.callback');
});

// Hand any other GET to the SPA so the Vue Router (history mode) can resolve it.
// Explicit routes above (Fortify, docs, demo-login, tenants.select, socialite)
// are always matched first; the fallback only receives unmatched requests.
Route::fallback(function (Request $request) {
    if ($request->isMethod('GET') && ! $request->is('api/*') && ! $request->expectsJson()) {
        return view('spa');
    }

    abort(404);
});
