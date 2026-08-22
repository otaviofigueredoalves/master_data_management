<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Não autenticado.', 401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        /** @var TenantContext|null $context */
        $context = app(TenantContext::class);

        if ($context?->membershipRoleName() && in_array($context->membershipRoleName(), $roles, true)) {
            return $next($request);
        }

        return $this->deny($request, 'Você não possui a role necessária para esta ação.', 403);
    }

    protected function deny(Request $request, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
