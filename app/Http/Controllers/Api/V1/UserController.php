<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $members = TenantUser::where('tenant_id', $tenantId)
            ->with('user:id,name,email,last_login_at', 'role:id,name')
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderByDesc('is_active')
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse($members);
    }

    /**
     * Creates (or attaches) a user to the active tenant with a given role.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $data = $request->validate([
            'name' => ['required_without:email', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required_without:send_invite', 'string', 'min:8'],
            'send_invite' => ['sometimes', 'boolean'],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        abort_if($this->isTenantRole($data['role']) === false, 422, 'Role inválida para membros de tenant.');

        return DB::transaction(function () use ($data, $tenantId): JsonResponse {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'] ?? str($data['email'])->before('@'),
                    'password' => Hash::make($data['password'] ?? bin2hex(random_bytes(8))),
                ],
            );

            if (TenantUser::where('tenant_id', $tenantId)->where('user_id', $user->id)->exists()) {
                return $this->errorResponse('Este usuário já pertence ao tenant.', 422);
            }

            $role = Role::findByName($data['role'], 'web');

            TenantUser::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'is_active' => true,
                'last_active_at' => now(),
            ]);

            return $this->successResponse(
                $user->load('tenants'),
                'Usuário adicionado ao tenant com sucesso.',
                201,
            );
        });
    }

    public function updateRole(Request $request, User $user): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $roleName = $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ])['role'];

        abort_if($this->isTenantRole($roleName) === false, 422, 'Role inválida para membros de tenant.');

        $membership = TenantUser::where('tenant_id', $tenantId)->where('user_id', $user->id)->first();

        abort_if(! $membership, 404, 'Usuário não pertence a este tenant.');

        $role = Role::findByName($roleName, 'web');

        $membership->update(['role_id' => $role->id]);

        return $this->successResponse($membership->fresh('role'), 'Role do usuário atualizada com sucesso.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        if ($user->id === $request->user()->id) {
            return $this->errorResponse('Você não pode remover a si mesmo do tenant.', 422);
        }

        $updated = TenantUser::where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->update(['is_active' => false]);

        abort_if(! $updated, 404, 'Usuário não pertence a este tenant.');

        return $this->successResponse(null, 'Usuário desativado neste tenant com sucesso.');
    }

    protected function isTenantRole(string $role): bool
    {
        return in_array($role, ['admin', 'manager', 'user'], true);
    }
}
