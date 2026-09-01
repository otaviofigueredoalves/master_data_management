<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SyncJob;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $jobs = SyncJob::where('tenant_id', $tenantId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with('integration:id,name,type')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse($jobs);
    }

    public function show(Request $request, SyncJob $syncJob): JsonResponse
    {
        abort_if($syncJob->tenant_id !== app(TenantContext::class)->tenantId(), 404);

        return $this->successResponse($syncJob->load('integration', 'tenant'));
    }
}
