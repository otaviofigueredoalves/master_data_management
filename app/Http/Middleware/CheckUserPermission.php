<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckUserPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Não autenticado.', 401);
        }

        // Platform administrators bypass tenant-scoped permissions.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        /** @var TenantContext|null $context */
        $context = app(TenantContext::class);

        if ($context?->membershipHasPermission($permission)) {
            return $next($request);
        }

        return $this->deny($request, 'Você não possui a permissão necessária para esta ação.', 403);
    }

    protected function deny(Request $request, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
