<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        protected TwoFactorAuthenticationProvider $twoFactorProvider,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string'],
        ]);

        if (! Auth::guard('web')->attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais informadas estão incorretas.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        // Two-factor authentication is enforced on API token issuance.
        if ($user->two_factor_confirmed_at && $user->two_factor_secret) {
            $code = $credentials['code'] ?? null;

            if (! $code) {
                return $this->errorResponse(
                    'Autenticação de dois fatores necessária. Envie o código no campo "code".',
                    422,
                );
            }

            $valid = $this->twoFactorProvider->verify(
                decrypt($user->two_factor_secret),
                $code,
            );

            if (! $valid) {
                return $this->errorResponse('Código de dois fatores inválido.', 422);
            }
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($credentials['device_name'] ?? 'api', ['*']);

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'user' => $user->load('tenants'),
        ], 'Login realizado com sucesso.');
    }

    public function register(Request $request): JsonResponse
    {
        $user = app(CreateNewUser::class)->create($request->all());

        $token = $user->createToken($request->input('device_name', 'api'), ['*']);

        return $this->successResponse([
            'token' => $token->plainTextToken,
            'user' => $user,
        ], 'Usuário criado com sucesso.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Revoke the bearer token that authenticated (or accompanied) the request.
        // Sanctum resolves the web session before the token, so the token is not
        // always visible through currentAccessToken() — resolve it explicitly.
        if ($bearer = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($bearer);

            if ($accessToken && (int) $accessToken->tokenable_id === (int) $user->getAuthIdentifier()) {
                $accessToken->delete();
            }
        } elseif ($user->currentAccessToken() instanceof PersonalAccessToken) {
            $user->currentAccessToken()->delete();
        }

        // Stateful requests (SPA/browser) also authenticate through the web
        // session — end it as well.
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->successResponse(null, 'Sessão encerrada com sucesso.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user()->load('tenants')
        );
    }

    public function updateProfile(Request $request): JsonResponse
    {
        app(UpdateUserProfileInformation::class)->update($request->user(), $request->all());

        return $this->successResponse($request->user()->fresh(), 'Perfil atualizado com sucesso.');
    }
}
