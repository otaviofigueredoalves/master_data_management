<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Integration;
use App\Models\MdmEntity;
use App\Models\SyncJob;
use App\Models\TenantUser;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var TenantContext $context */
        $context = app(TenantContext::class);

        if (! $context->hasTenant()) {
            return $this->errorResponse('Nenhum tenant ativo para este usuário.', 422);
        }

        $tenantId = $context->tenantId();

        // Per-tenant cached metrics. Only plain arrays are cached — never Eloquent
        // models/collections — so JSON output stays clean regardless of the cache
        // driver. The key is versioned to drop any stale serialized-object entries.
        $metrics = Cache::remember("dashboard:tenant:{$tenantId}:v2", now()->addSeconds(60), function () use ($tenantId) {
            return [
                'entities_total' => MdmEntity::where('tenant_id', $tenantId)->count(),
                'entities_master' => MdmEntity::where('tenant_id', $tenantId)->where('is_master', true)->count(),
                'entities_by_type' => MdmEntity::where('tenant_id', $tenantId)
                    ->selectRaw('type, count(*) as total')
                    ->groupBy('type')
                    ->pluck('total', 'type')
                    ->toArray(),
                'integrations_active' => Integration::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'sync_jobs_last' => SyncJob::where('tenant_id', $tenantId)
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get(['id', 'status', 'type', 'created_at'])
                    ->toArray(),
                'members_active' => TenantUser::where('tenant_id', $tenantId)->where('is_active', true)->count(),
                'recent_audit_logs' => AuditLog::where('tenant_id', $tenantId)
                    ->with('user:id,name')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get()
                    ->toArray(),
            ];
        });

        return $this->successResponse($metrics);
    }
}
