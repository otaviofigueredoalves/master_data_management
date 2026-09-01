<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TenantInvitation;
use App\Models\TenantUser;
use App\Models\User;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class InvitationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        return $this->successResponse(
            TenantInvitation::where('tenant_id', $tenantId)
                ->with('invitedBy:id,name')
                ->orderByDesc('id')
                ->paginate($request->integer('per_page', 15))
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = app(TenantContext::class)->tenantId();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        abort_if(! in_array($data['role'], ['admin', 'manager', 'user'], true), 422, 'Role inválida.');

        $role = Role::findByName($data['role'], 'web');

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenantId,
            'email' => strtolower($data['email']),
            'token' => Str::random(64),
            'roles' => [$role->name],
            'permissions' => $role->permissions()->pluck('name')->all(),
            'invited_by' => $request->user()->id,
            'expires_at' => Carbon::parse($data['expires_at'] ?? now()->addDays(7)),
        ]);

        $acceptUrl = url("/invitations/{$invitation->token}/accept");

        return $this->successResponse(
            ['invitation' => $invitation, 'accept_url' => $acceptUrl],
            'Convite criado com sucesso.',
            201,
        );
    }

    public function accept(Request $request, TenantInvitation $invitation): JsonResponse
    {
        if ($invitation->accepted_at) {
            return $this->errorResponse('Este convite já foi aceito.', 422);
        }

        if (now()->greaterThan($invitation->expires_at)) {
            return $this->errorResponse('Este convite expirou.', 422);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['sometimes', 'string', 'min:8'],
            'name' => ['sometimes', 'string', 'max:255'],
        ]);

        if (strtolower($credentials['email']) !== strtolower($invitation->email)) {
            return $this->errorResponse('O e-mail informado não corresponde ao convite.', 422);
        }

        $user = User::where('email', strtolower($credentials['email']))->first();

        if (! $user && ($credentials['password'] ?? null)) {
            $user = User::create([
                'name' => $credentials['name'] ?? str($credentials['email'])->before('@'),
                'email' => strtolower($credentials['email']),
                'password' => $credentials['password'],
            ]);
        }

        abort_if(! $user, 422, 'Crie uma conta com este e-mail antes de aceitar o convite.');

        DB::transaction(function () use ($invitation, $user): void {
            $role = Role::findByName($invitation->roles[0] ?? 'user', 'web');

            TenantUser::firstOrCreate(
                ['tenant_id' => $invitation->tenant_id, 'user_id' => $user->id],
                ['role_id' => $role->id, 'is_active' => true, 'last_active_at' => now()],
            );

            $invitation->update(['accepted_at' => now()]);
        });

        return $this->successResponse(['tenant_id' => $invitation->tenant_id], 'Convite aceito com sucesso.');
    }
}
