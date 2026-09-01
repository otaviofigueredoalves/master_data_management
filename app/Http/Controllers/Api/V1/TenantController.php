<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class TenantController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $tenants = Tenant::withCount('users')
                ->orderBy('name')
                ->paginate($request->integer('per_page', 15));

            return $this->successResponse($tenants);
        }

        $tenants = $user->tenants()
            ->withPivot('role_id', 'is_active', 'last_active_at')
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant) => $tenant->setAttribute('membership_role', $tenant->pivot->role_id ? Role::find($tenant->pivot->role_id)?->name : null));

        return $this->successResponse($tenants);
    }

    /**
     * Self-service tenant creation: the creator becomes the tenant admin.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'settings' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'is_active' => true,
                'settings' => $data['settings'] ?? [],
            ]);

            $adminRole = Role::findOrCreate('admin', 'web');

            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $request->user()->id,
                'role_id' => $adminRole->id,
                'is_active' => true,
                'last_active_at' => now(),
            ]);

            $request->user()->forceFill(['current_tenant_id' => $tenant->id])->save();
            session(['current_tenant_id' => $tenant->id]);
        });

        return $this->successResponse(null, 'Tenant criado com sucesso.', 201);
    }

    public function show(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('view', $tenant);

        return $this->successResponse($tenant->loadCount('users', 'mdmEntities', 'integrations'));
    }

    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('update', $tenant);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', "unique:tenants,slug,{$tenant->id}"],
            'is_active' => ['sometimes', 'boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        $tenant->update($data);

        return $this->successResponse($tenant->fresh(), 'Tenant atualizado com sucesso.');
    }

    public function destroy(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('delete', $tenant);

        $tenant->delete();

        return $this->successResponse(null, 'Tenant removido com sucesso.');
    }

    public function switch(Request $request): JsonResponse
    {
        $tenantId = (int) $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ])['tenant_id'];

        $tenant = Tenant::findOrFail($tenantId);

        $this->authorize('view', $tenant);

        $request->user()->forceFill(['current_tenant_id' => $tenant->id])->save();
        session(['current_tenant_id' => $tenant->id]);

        return $this->successResponse(['current_tenant_id' => $tenant->id], 'Tenant atual alterado.');
    }
}
