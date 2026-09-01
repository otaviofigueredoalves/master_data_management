<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MdmEntity;
use App\Models\MdmEntityRelation;
use App\Services\AuditLogger;
use App\Services\MdmNormalizationService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MdmEntityController extends Controller
{
    public function __construct(
        protected MdmNormalizationService $normalizer,
        protected AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $entities = MdmEntity::where('tenant_id', $tenantId)
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('source_system'), fn ($q) => $q->where('source_system', $request->string('source_system')))
            ->when($request->filled('is_master'), fn ($q) => $q->where('is_master', $request->boolean('is_master')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('external_id', 'like', "%{$search}%")
                    ->orWhereJsonContains('data', ['name' => $search]);
            })
            ->with('createdBy:id,name', 'updatedBy:id,name')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse($entities);
    }

    public function store(Request $request): JsonResponse
    {
        $context = app(TenantContext::class);

        $data = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'source_system' => ['required', 'string', 'max:100'],
            'external_id' => ['required', 'string', 'max:255'],
            'data' => ['required', 'array'],
            'normalized_data' => ['nullable', 'array'],
            'is_master' => ['sometimes', 'boolean'],
        ]);

        $data['external_id'] = trim($data['external_id']);

        $exists = MdmEntity::where('tenant_id', $context->tenantId())
            ->where('type', $data['type'])
            ->where('external_id', $data['external_id'])
            ->exists();

        abort_if($exists, 422, 'Já existe uma entidade com este external_id.');

        $entity = MdmEntity::create([
            'tenant_id' => $context->tenantId(),
            'type' => $data['type'],
            'source_system' => $data['source_system'],
            'external_id' => $data['external_id'],
            'data' => $data['data'],
            'is_master' => $data['is_master'] ?? false,
            'version' => 1,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->auditLogger->log('entity.created', $entity, $request->user(), null, $entity->toArray());

        return $this->successResponse($entity->fresh('createdBy', 'updatedBy'), 'Entidade MDM criada com sucesso.', 201);
    }

    public function show(Request $request, MdmEntity $mdmEntity): JsonResponse
    {
        $this->authorize('view', $mdmEntity);

        return $this->successResponse($mdmEntity->load('createdBy', 'updatedBy', 'tenant'));
    }

    public function update(Request $request, MdmEntity $mdmEntity): JsonResponse
    {
        $this->authorize('update', $mdmEntity);

        $data = $request->validate([
            'type' => ['sometimes', 'string', 'max:100', Rule::unique('mdm_entities')->where(fn ($q) => $q->where('tenant_id', $mdmEntity->tenant_id))->ignore($mdmEntity->id)],
            'source_system' => ['sometimes', 'string', 'max:100'],
            'data' => ['sometimes', 'array'],
            'is_master' => ['sometimes', 'boolean'],
        ]);

        $old = $mdmEntity->toArray();

        $mdmEntity->version++;
        $mdmEntity->update(array_merge($data, ['updated_by' => $request->user()->id]));

        $this->auditLogger->log('entity.updated', $mdmEntity, $request->user(), $old, $mdmEntity->toArray());

        return $this->successResponse($mdmEntity->fresh(), 'Entidade MDM atualizada com sucesso.');
    }

    public function destroy(Request $request, MdmEntity $mdmEntity): JsonResponse
    {
        $this->authorize('delete', $mdmEntity);

        $this->auditLogger->log('entity.deleted', $mdmEntity, $request->user(), $mdmEntity->toArray(), null);

        $mdmEntity->delete();

        return $this->successResponse(null, 'Entidade MDM removida com sucesso.');
    }

    /**
     * Runs normalization rules and promotes the record to the golden/master version.
     */
    public function normalize(Request $request, MdmEntity $mdmEntity): JsonResponse
    {
        $this->authorize('update', $mdmEntity);

        $old = $mdmEntity->toArray();

        $this->normalizer->normalize($mdmEntity);

        $mdmEntity->forceFill(['is_master' => true])->save();
        $mdmEntity->refresh();

        $this->auditLogger->log('entity.normalized', $mdmEntity, $request->user(), $old, $mdmEntity->toArray());

        return $this->successResponse($mdmEntity->fresh(), 'Normalização concluída com sucesso.');
    }

    public function relations(Request $request, MdmEntity $mdmEntity): JsonResponse
    {
        $this->authorize('view', $mdmEntity);

        $relations = MdmEntityRelation::where('tenant_id', $mdmEntity->tenant_id)
            ->where(fn ($q) => $q->where('parent_id', $mdmEntity->id)->orWhere('child_id', $mdmEntity->id))
            ->with('parent:id,type,external_id,is_master', 'child:id,type,external_id,is_master')
            ->get();

        return $this->successResponse($relations);
    }
}
