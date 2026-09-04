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

    public function test_already_accepted_invitation_cannot_be_accepted_again(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $invite = $fixtures['tenant']->invitations()->create([
            'email' => 'invitee@example.com',
            'token' => 'accepted-token',
            'roles' => ['manager'],
            'permissions' => [],
            'invited_by' => $fixtures['admin']->id,
            'expires_at' => now()->addDays(7),
            'accepted_at' => now(),
        ]);

        $this->postJson("/api/v1/invitations/{$invite->token}/accept", ['email' => 'invitee@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Este convite já foi aceito.');
    }

    public function test_accept_rejects_email_that_does_not_match_the_invitation(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $invite = $fixtures['tenant']->invitations()->create([
            'email' => 'invitee@example.com',
            'token' => 'mismatch-token',
            'roles' => ['manager'],
            'permissions' => [],
            'invited_by' => $fixtures['admin']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->postJson("/api/v1/invitations/{$invite->token}/accept", ['email' => 'outro@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'O e-mail informado não corresponde ao convite.');
    }

    public function test_accept_requires_existing_account_when_no_password_is_sent(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $invite = $fixtures['tenant']->invitations()->create([
            'email' => 'semconta@example.com',
            'token' => 'no-account-token',
            'roles' => ['user'],
            'permissions' => [],
            'invited_by' => $fixtures['admin']->id,
            'expires_at' => now()->addDays(7),
        ]);

        $this->postJson("/api/v1/invitations/{$invite->token}/accept", ['email' => 'semconta@example.com'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Crie uma conta com este e-mail antes de aceitar o convite.');
    }
}
