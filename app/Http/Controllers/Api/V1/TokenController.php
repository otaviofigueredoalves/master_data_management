<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            $request->user()->tokens()->orderByDesc('created_at')->get(['id', 'name', 'abilities', 'created_at', 'last_used_at', 'expires_at'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
        ]);

        $token = $request->user()->createToken(
            $data['name'],
            $data['abilities'] ?? ['*'],
        );

        return $this->successResponse([
            'token' => $token->plainTextToken,
        ], 'Token criado com sucesso.', 201);
    }

    public function destroy(Request $request, string $token): JsonResponse
    {
        $accessToken = PersonalAccessToken::findOrFail($token);

        abort_if($accessToken->tokenable_id !== $request->user()->id, 404);

        $accessToken->delete();

        return $this->successResponse(null, 'Token revogado com sucesso.');
    }
}
