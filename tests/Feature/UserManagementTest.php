<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Denial and failure branches of UserController::store/destroy.
 *
 * The happy paths (admin adds a member, updates the role, deactivates a member)
 * are exercised by ApiE2eJourneyTest — these tests cover who is *not* allowed to
 * manage members and how the controller reacts to invalid input/state.
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_cannot_add_user_to_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['viewer'])
            ->postJson('/api/v1/users', [
                'name' => 'Intruso',
                'email' => 'intruso@example.com',
                'password' => 'password-seguro',
                'role' => 'user',
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_manager_cannot_add_user_to_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['manager'])
            ->postJson('/api/v1/users', [
                'name' => 'Gerente Tenta',
                'email' => 'gerente-tenta@example.com',
                'password' => 'password-seguro',
                'role' => 'user',
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_store_rejects_role_that_is_not_a_tenant_role(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson('/api/v1/users', [
                'name' => 'Super Admin Fake',
                'email' => 'fake-sa@example.com',
                'password' => 'password-seguro',
                'role' => 'super-admin',
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Role inválida para membros de tenant.');
    }

    public function test_store_rejects_user_who_already_belongs_to_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson('/api/v1/users', [
                'name' => $fixtures['viewer']->name,
                'email' => $fixtures['viewer']->email,
                'password' => 'password-seguro',
                'role' => 'user',
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Este usuário já pertence ao tenant.');
    }

    public function test_store_requires_password_when_invite_is_not_sent(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson('/api/v1/users', [
                'name' => 'Sem Senha',
                'email' => 'sem-senha@example.com',
                'role' => 'user',
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_viewer_cannot_remove_user_from_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['viewer'])
            ->deleteJson('/api/v1/users/'.$fixtures['manager']->id, [], $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_admin_cannot_remove_self_from_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->deleteJson('/api/v1/users/'.$fixtures['admin']->id, [], $this->tenantHeaders($fixtures['tenant']))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Você não pode remover a si mesmo do tenant.');
    }

    public function test_destroy_rejects_user_who_does_not_belong_to_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();
        $outsider = User::factory()->create();

        $this->actingAsSanctum($fixtures['admin'])
            ->deleteJson('/api/v1/users/'.$outsider->id, [], $this->tenantHeaders($fixtures['tenant']))
            ->assertNotFound()
            ->assertJsonPath('message', 'Usuário não pertence a este tenant.');
    }

    public function test_destroy_deactivates_membership_instead_of_deleting_the_user(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->deleteJson('/api/v1/users/'.$fixtures['viewer']->id, [], $this->tenantHeaders($fixtures['tenant']))
            ->assertOk();

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $fixtures['tenant']->id,
            'user_id' => $fixtures['viewer']->id,
            'is_active' => false,
        ]);

        // The user row itself survives — removal is tenant-scoped deactivation.
        $this->assertDatabaseHas('users', ['id' => $fixtures['viewer']->id]);
    }
}
