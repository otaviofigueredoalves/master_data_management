<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

return [

    // The SPA authenticates with pure bearer tokens, so this list is currently
    // INERT: statefulApi() is intentionally NOT enabled in bootstrap/app.php.
    // Re-enabling stateful handling would force CSRF on /api POSTs from the SPA
    // origin (returns 419) unless the front-end also sends X-XSRF-TOKEN.
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost:8000,localhost,127.0.0.1,::1')),

    'guard' => ['web'],

    'expiration' => null,

    'token_prefixable' => false,

    'middleware' => [
        'verify_csrf_token' => VerifyCsrfToken::class,
        'encrypt_cookies' => EncryptCookies::class,
    ],

];
