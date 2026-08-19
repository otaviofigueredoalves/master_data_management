<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Providers that are shown when credentials are configured.
     */
    public static function availableProviders(): array
    {
        return collect(['google', 'github', 'microsoft'])
            ->filter(fn (string $provider) => filled(config("services.{$provider}.client_id"))
                && filled(config("services.{$provider}.client_secret")))
            ->values()
            ->all();
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->assertSupported($provider);

        $socialUser = Socialite::driver($provider)->user();

        $user = User::where('email', $socialUser->getEmail())->first();

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'password' => Str::password(),
            ]);
        }

        SocialAccount::updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => (string) $socialUser->getId(),
            ],
            [
                'user_id' => $user->id,
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'avatar' => $socialUser->getAvatar(),
                'token' => $socialUser->token,
                'refresh_token' => $socialUser->refreshToken,
                'expires_at' => $socialUser->expiresIn ? now()->addSeconds($socialUser->expiresIn) : null,
            ],
        );

        Auth::login($user, true);

        return redirect()->intended('/');
    }

    protected function assertSupported(string $provider): void
    {
        abort_unless(in_array($provider, static::availableProviders(), true), 404);
    }
}
