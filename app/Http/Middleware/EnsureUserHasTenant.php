<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EnsureUserHasTenant
{
    /**
     * Resolve the active tenant for the request and bind the TenantContext.
     *
     * Priority: X-Tenant-ID header > session > user.current_tenant_id. If the
     * user owns exactly one active membership it is selected automatically.
     * Platform super-admins may act inside any tenant without a membership.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $this->deny($request, 'Não autenticado.', 401);
        }

        $tenantId = $request->header('X-Tenant-ID')
            ?? session('current_tenant_id')
            ?? $user->current_tenant_id;

        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        if ($tenant) {
            $membership = $user->membershipIn($tenant);

            if ($membership || $user->isSuperAdmin()) {
                session(['current_tenant_id' => $tenant->id]);

                $this->bind($tenant, $membership);

                return $next($request);
            }

            return $this->deny($request, 'Você não possui acesso a este tenant.', 403);
        }

        // No tenant was requested: auto-select the single active membership.
        $membership = $user->tenantMemberships()->active()->first();

        if ($membership) {
            $tenant = $membership->tenant;

            $user->forceFill(['current_tenant_id' => $tenant->id])->save();
            session(['current_tenant_id' => $tenant->id]);

            $this->bind($tenant, $membership);

            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            $this->bind(null, null);

            return $next($request);
        }

        return $this->deny($request, 'Selecione um tenant para continuar.', 403);
    }

    protected function bind(?Tenant $tenant, mixed $membership): void
    {
        app()->instance(TenantContext::class, new TenantContext($tenant, $membership));
    }

    protected function deny(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return redirect()
            ->route('tenants.select')
            ->withErrors(['tenant' => $message]);
    }
}
