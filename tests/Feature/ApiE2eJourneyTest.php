<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * End-to-end journey through the whole HTTP API using real bearer tokens
 * (login/register endpoints) and the X-Tenant-ID header — exactly like the SPA
 * consumes it. Every legitimate action is expected to answer in the 2xx range.
 *
 * Note: login starts a web (stateful) session, and Sanctum resolves that session
 * before the bearer token. To keep each scenario deterministic we authenticate
 * ONE persona per test method.
 */
class ApiE2eJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function demoTenant(): Tenant
    {
        return Tenant::where('slug', 'acme-corp')->firstOrFail();
    }

    protected function login(string $email, string $password): string
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk();

        return $response->json('data.token');
    }

    protected function api(string $token, ?int $tenantId = null): static
    {
        $this->withToken($token);

        if ($tenantId !== null) {
            $this->withHeader('X-Tenant-ID', (string) $tenantId);
        }

        return $this;
    }

    public function test_new_user_register_login_and_full_workspace_returns_2xx(): void
    {
        $email = 'joana.nova@example.com';
        $password = 'password-seguro-123';

        // 1. Registration (public) and login with the same credentials.
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Joana Nova',
            'email' => $email,
            'password' => $password,
        ]);
        $register->assertCreated();
        $this->assertNotEmpty($register->json('data.token'));

        $token = $this->login($email, $password);
        $this->assertNotEmpty($token);

        // 2. Profile / tenant onboarding endpoints.
        $this->api($token)->getJson('/api/v1/me')->assertOk();
        $this->api($token)->getJson('/api/v1/tenants')->assertOk()
            ->assertJsonPath('data', []);

        // Tenant-scoped endpoints are unreachable before onboarding.
        $this->api($token)->getJson('/api/v1/dashboard')->assertForbidden();

        // 3. Self-service workspace creation (creator becomes tenant admin).
        $this->api($token)->postJson('/api/v1/tenants', [
            'name' => 'Nova Empresa',
            'slug' => 'nova-empresa',
        ])->assertCreated();

        $tenants = $this->api($token)->getJson('/api/v1/tenants')->assertOk();
        $tenantId = $tenants->json('data.0.id');
        $this->assertIsInt($tenantId);
        $tenants->assertJsonPath('data.0.membership_role', 'admin');

        // Tenant switch.
        $this->api($token)->postJson('/api/v1/tenants/switch', ['tenant_id' => $tenantId])->assertOk();

        $this->api($token, $tenantId);

        // 4. Read endpoints.
        $this->getJson('/api/v1/roles')->assertOk();
        $this->getJson('/api/v1/dashboard')->assertOk();
        $this->getJson('/api/v1/users')->assertOk();
        $this->getJson('/api/v1/audit-logs')->assertOk();
        $this->getJson('/api/v1/tenants/'.$tenantId)->assertOk();
        $this->putJson('/api/v1/tenants/'.$tenantId, ['name' => 'Nova Empresa Ltda'])->assertOk();

        // 5. MDM entities CRUD + normalize + relations.
        $entity = $this->postJson('/api/v1/mdm-entities', [
            'type' => 'customer',
            'source_system' => 'erp',
            'external_id' => 'CUST-001',
            'data' => ['name' => '  ACME Corp  ', 'email' => ' FINANCEIRO@acme.com '],
        ])->assertCreated()->json('data');

        $entityId = $entity['id'];
        $this->getJson('/api/v1/mdm-entities')->assertOk();
        $this->getJson("/api/v1/mdm-entities/{$entityId}")->assertOk();
        $this->putJson("/api/v1/mdm-entities/{$entityId}", [
            'data' => ['name' => 'ACME Corp', 'email' => 'financeiro@acme.com', 'active' => true],
        ])->assertOk();
        $this->postJson("/api/v1/mdm-entities/{$entityId}/normalize")->assertOk()
            ->assertJsonPath('data.is_master', true);
        $this->getJson("/api/v1/mdm-entities/{$entityId}/relations")->assertOk();
        $this->deleteJson("/api/v1/mdm-entities/{$entityId}")->assertOk();

        // 6. Integrations CRUD + sync dispatch.
        $integration = $this->postJson('/api/v1/integrations', [
            'name' => 'Salesforce CRM',
            'type' => 'salesforce',
            'is_active' => true,
        ])->assertCreated()->json('data');

        $integrationId = $integration['id'];
        $this->getJson('/api/v1/integrations')->assertOk();
        $this->getJson("/api/v1/integrations/{$integrationId}")->assertOk();
        $this->putJson("/api/v1/integrations/{$integrationId}", ['name' => 'Salesforce (CRM)'])->assertOk();

        $job = $this->postJson("/api/v1/integrations/{$integrationId}/sync", ['type' => 'full'])
            ->assertAccepted()->json('data');
        $this->assertIsInt($job['id']);

        $this->getJson('/api/v1/sync-jobs')->assertOk();
        $this->getJson('/api/v1/sync-jobs/'.$job['id'])->assertOk();
        $this->deleteJson("/api/v1/integrations/{$integrationId}")->assertOk();

        // 7. Member management (admin role).
        $member = $this->postJson('/api/v1/users', [
            'name' => 'Pedro Membro',
            'email' => 'pedro.membro@example.com',
            'password' => 'password-seguro-456',
            'role' => 'manager',
        ])->assertCreated()->json('data');

        $memberId = $member['id'];
        $this->getJson('/api/v1/users')->assertOk();
        $this->putJson("/api/v1/users/{$memberId}", ['role' => 'user'])->assertOk();
        $this->deleteJson("/api/v1/users/{$memberId}")->assertOk();

        // 8. Invitations (create + list).
        $invite = $this->postJson('/api/v1/invitations', [
            'email' => 'convidado@example.com',
            'role' => 'manager',
        ])->assertCreated()->json('data.invitation');

        $this->assertNotEmpty($invite['token']);
        $this->getJson('/api/v1/invitations')->assertOk();

        // 9. API tokens.
        $this->getJson('/api/v1/tokens')->assertOk();
        $apiToken = $this->postJson('/api/v1/tokens', ['name' => 'CI'])->assertCreated()->json('data.token');
        $this->assertNotEmpty($apiToken);

        $owned = $this->getJson('/api/v1/tokens')->json('data');
        $tokenRecordId = collect($owned)->firstWhere('name', 'CI')['id'];
        $this->deleteJson("/api/v1/tokens/{$tokenRecordId}")->assertOk();

        // 10. Profile update.
        $this->putJson('/api/v1/me', [
            'name' => 'Joana N. Atualizada',
            'email' => $email,
        ])->assertOk();

        // 11. Tenant destroy is platform-only — tenant admin receives 403 (correct denial).
        $this->deleteJson("/api/v1/tenants/{$tenantId}")->assertForbidden();

        // 12. Logout ends the session (stateful/TransientToken path).
        $this->postJson('/api/v1/auth/logout')->assertOk();
    }

    public function test_pure_token_logout_revokes_the_token_and_returns_2xx(): void
    {
        // Register does not start a web session, so the guard resolves the bearer
        // token -> logout must revoke it (exactly what the SPA relies on).
        $register = $this->postJson('/api/v1/auth/register', [
            'name' => 'Cliente Token',
            'email' => 'token.client@example.com',
            'password' => 'password-seguro-123',
        ]);
        $register->assertCreated();
        $token = $register->json('data.token');

        $this->api($token)->getJson('/api/v1/me')->assertOk();

        $this->postJson('/api/v1/auth/logout')->assertOk();

        // The token row is gone from the database.
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // Clear the memoized guard so the deleted token is re-checked.
        $this->app['auth']->forgetGuards();

        // Token was revoked -> following request is unauthorized.
        $this->api($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_demo_tenant_admin_login_and_privileged_actions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = $this->demoTenant();

        $adminToken = $this->login('demo@mdmsaas.test', 'password');
        $this->api($adminToken, $tenant->id)->getJson('/api/v1/dashboard')->assertOk();

        $entity = $this->api($adminToken, $tenant->id)->postJson('/api/v1/mdm-entities', [
            'type' => 'supplier',
            'source_system' => 'manual',
            'external_id' => 'SUP-9000',
            'data' => ['name' => 'Fornecedor Demo'],
        ])->assertCreated()->json('data');

        $this->api($adminToken, $tenant->id)->getJson('/api/v1/mdm-entities/'.$entity['id'])->assertOk();
        $this->api($adminToken, $tenant->id)->getJson('/api/v1/audit-logs')->assertOk();
    }

    public function test_demo_manager_login_and_limited_write_actions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = $this->demoTenant();

        $managerToken = $this->login('manager@mdmsaas.test', 'password');
        $this->api($managerToken, $tenant->id)->getJson('/api/v1/dashboard')->assertOk();

        // Manager can create + normalize entities and run sync.
        $entity = $this->api($managerToken, $tenant->id)->postJson('/api/v1/mdm-entities', [
            'type' => 'product',
            'source_system' => 'erp',
            'external_id' => 'PROD-007',
            'data' => ['name' => 'Produto Manager', 'sku' => ' MGT-1 '],
        ])->assertCreated()->json('data');

        $this->api($managerToken, $tenant->id)->postJson('/api/v1/mdm-entities/'.$entity['id'].'/normalize')
            ->assertOk();

        $integration = $tenant->integrations()->first();
        $this->assertNotNull($integration);
        $this->api($managerToken, $tenant->id)
            ->postJson("/api/v1/integrations/{$integration->id}/sync", ['type' => 'incremental'])
            ->assertAccepted();
    }

    public function test_demo_viewer_login_allows_reads_but_denies_writes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = $this->demoTenant();

        $viewerToken = $this->login('viewer@mdmsaas.test', 'password');
        $this->api($viewerToken, $tenant->id)->getJson('/api/v1/dashboard')->assertOk();
        $this->api($viewerToken, $tenant->id)->getJson('/api/v1/roles')->assertOk();
        $this->api($viewerToken, $tenant->id)->getJson('/api/v1/mdm-entities')->assertOk();

        $this->api($viewerToken, $tenant->id)->postJson('/api/v1/mdm-entities', [
            'type' => 'customer',
            'source_system' => 'manual',
            'external_id' => 'CUST-X',
            'data' => ['name' => 'Bloqueado'],
        ])->assertForbidden();

        $this->api($viewerToken, $tenant->id)->getJson('/api/v1/audit-logs')->assertForbidden();
    }

    public function test_guest_accepts_invitation_and_logs_in(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = $this->demoTenant();

        // Admin creates the invitation through the API.
        $adminToken = $this->login('demo@mdmsaas.test', 'password');
        $invite = $this->api($adminToken, $tenant->id)->postJson('/api/v1/invitations', [
            'email' => 'convidado@example.com',
            'role' => 'user',
        ])->assertCreated()->json('data.invitation');

        // Public acceptance: creates the account on the fly.
        $this->postJson("/api/v1/invitations/{$invite['token']}/accept", [
            'email' => 'convidado@example.com',
            'name' => 'Convidado Silva',
            'password' => 'convite-seguro-789',
        ])->assertOk();

        $guestToken = $this->login('convidado@example.com', 'convite-seguro-789');
        $guestTenants = $this->api($guestToken)->getJson('/api/v1/tenants')->assertOk();
        $this->assertSame($tenant->id, $guestTenants->json('data.0.id'));
        $this->api($guestToken, $tenant->id)->getJson('/api/v1/dashboard')->assertOk();
    }

    public function test_platform_super_admin_login_and_global_tenant_access(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenant = $this->demoTenant();

        $saToken = $this->login('platform@mdmsaas.test', 'password');

        // Super-admin sees every tenant (paginated).
        $tenants = $this->api($saToken)->getJson('/api/v1/tenants')->assertOk();
        $this->assertGreaterThanOrEqual(1, $tenants->json('data.total'));
        $this->assertTrue(collect($tenants->json('data.data'))->contains('id', $tenant->id));

        // Acts inside any tenant without a membership.
        $this->api($saToken, $tenant->id)->getJson('/api/v1/dashboard')->assertOk();
        $this->api($saToken, $tenant->id)->getJson('/api/v1/audit-logs')->assertOk();

        $entity = $this->api($saToken, $tenant->id)->postJson('/api/v1/mdm-entities', [
            'type' => 'location',
            'source_system' => 'api',
            'external_id' => 'LOC-SA-1',
            'data' => ['name' => 'Filial Super Admin'],
        ])->assertCreated()->json('data');

        // View of a tenant-scoped record must work for super-admins too.
        $this->api($saToken, $tenant->id)->getJson('/api/v1/mdm-entities/'.$entity['id'])->assertOk();

        $integration = $tenant->integrations()->first();
        if ($integration) {
            $this->api($saToken, $tenant->id)->getJson('/api/v1/integrations/'.$integration->id)->assertOk();
        }

        // Creates and deletes a tenant (platform-only privilege).
        $this->api($saToken)->postJson('/api/v1/tenants', [
            'name' => 'Tenant Super Admin',
            'slug' => 'tenant-super-admin-'.time(),
        ])->assertCreated();

        $created = Tenant::where('slug', 'like', 'tenant-super-admin-%')->firstOrFail();
        $this->api($saToken, $created->id)->getJson('/api/v1/dashboard')->assertOk();
        $this->api($saToken, $created->id)->deleteJson("/api/v1/tenants/{$created->id}")->assertOk();
    }
}
