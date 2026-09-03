<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_user_to_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $response = $this->actingAsSanctum($fixtures['admin'])
            ->postJson('/api/v1/invitations', [
                'email' => 'invitee@example.com',
                'role' => 'manager',
            ], $this->tenantHeaders($fixtures['tenant']));

        $response->assertCreated()->assertJsonPath('data.invitation.email', 'invitee@example.com');

        $this->assertDatabaseHas('tenant_invitations', [
            'tenant_id' => $fixtures['tenant']->id,
            'email' => 'invitee@example.com',
        ]);
    }

    public function test_invitee_can_accept_invitation(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $invite = $fixtures['tenant']->invitations()->create([
            'email' => 'invitee@example.com',
            'token' => 'valid-token-123',
            'roles' => ['manager'],
            'permissions' => [],
            'invited_by' => $fixtures['admin']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAsSanctum($invitee)
            ->postJson("/api/v1/invitations/{$invite->token}/accept", [
                'email' => 'invitee@example.com',
            ])
            ->assertOk();

        $this->assertDatabaseHas('tenant_users', [
            'tenant_id' => $fixtures['tenant']->id,
            'user_id' => $invitee->id,
        ]);

        $this->assertNotNull($invite->fresh()->accepted_at);
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $invite = $fixtures['tenant']->invitations()->create([
            'email' => 'invitee@example.com',
            'token' => 'expired-token',
            'roles' => ['manager'],
            'permissions' => [],
            'invited_by' => $fixtures['admin']->id,
            'expires_at' => now()->subDay(),
        ]);

        $invitee = User::factory()->create(['email' => 'invitee@example.com']);

        $this->actingAsSanctum($invitee)
            ->postJson("/api/v1/invitations/{$invite->token}/accept", ['email' => 'invitee@example.com'])
            ->assertUnprocessable();
    }
}
