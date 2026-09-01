<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditLogger;
use App\Services\IntegrationService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntegrationController extends Controller
{
    public function __construct(
        protected IntegrationService $integrationService,
        protected AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $integrations = Integration::where('tenant_id', $tenantId)
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->withCount('syncJobs')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse($integrations);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $integration = Integration::create(array_merge($data, ['tenant_id' => $tenantId]));

        $this->auditLogger->log('integration.created', $integration, $request->user(), null, $integration->toArray());

        return $this->successResponse($integration, 'Integração criada com sucesso.', 201);
    }

    public function show(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('view', $integration);

        return $this->successResponse($integration->load('syncJobs'));
    }

    public function update(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('update', $integration);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:100'],
            'credentials' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $old = $integration->toArray();

        $integration->update($data);

        $this->auditLogger->log('integration.updated', $integration, $request->user(), $old, $integration->fresh()->toArray());

        return $this->successResponse($integration->fresh(), 'Integração atualizada com sucesso.');
    }

    public function destroy(Request $request, Integration $integration): JsonResponse
    {
        $this->authorize('delete', $integration);

        $this->auditLogger->log('integration.deleted', $integration, $request->user(), $integration->toArray(), null);

        $integration->delete();

        return $this->successResponse(null, 'Integração removida com sucesso.');
    }

    public function sync(Request $request, Integration $integration): JsonResponse
    {
        abort_unless($integration->tenant_id === app(TenantContext::class)->tenantId(), 404);

        $type = $request->validate([
            'type' => ['sometimes', 'string', Rule::in(['full', 'incremental'])],
        ])['type'] ?? 'full';

        $job = $this->integrationService->sync($integration, $type);

        $this->auditLogger->log('integration.sync_started', $integration, $request->user(), null, ['sync_job_id' => $job->id, 'type' => $type]);

        return $this->successResponse($job->load('integration'), 'Sincronização enfileirada.', 202);
    }
}
