<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;

class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SocialiteFactory::class, function ($app) {
            return $app->make('Laravel\Socialite\Manager');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make('config')->set('services', array_merge($this->app->make('config')->get('services', []), [
            'google' => [
                'client_id' => env('SOCIALITE_GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
                'client_secret' => env('SOCIALITE_GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET')),
                'redirect' => env('SOCIALITE_GOOGLE_REDIRECT_URL', env('GOOGLE_REDIRECT_URL')),
            ],
            'microsoft' => [
                'client_id' => env('SOCIALITE_MICROSOFT_CLIENT_ID', env('MICROSOFT_CLIENT_ID')),
                'client_secret' => env('SOCIALITE_MICROSOFT_CLIENT_SECRET', env('MICROSOFT_CLIENT_SECRET')),
                'redirect' => env('SOCIALITE_MICROSOFT_REDIRECT_URL', env('MICROSOFT_REDIRECT_URL')),
            ],
            'github' => [
                'client_id' => env('SOCIALITE_GITHUB_CLIENT_ID', env('GITHUB_CLIENT_ID')),
                'client_secret' => env('SOCIALITE_GITHUB_CLIENT_SECRET', env('GITHUB_CLIENT_SECRET')),
                'redirect' => env('SOCIALITE_GITHUB_REDIRECT_URL', env('GITHUB_REDIRECT_URL')),
            ],
        ]));
    }
}
