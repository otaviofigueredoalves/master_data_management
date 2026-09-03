<?php

namespace Tests\Feature;

use App\Models\MdmEntity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_read_entity_from_another_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->memberOf($tenantA, 'admin')->create();
        $entityB = MdmEntity::factory()->create(['tenant_id' => $tenantB->id]);

        $this->actingAsSanctum($userA)
            ->getJson("/api/v1/mdm-entities/{$entityB->id}", $this->tenantHeaders($tenantA))
            ->assertForbidden();
    }

    public function test_admin_can_create_mdm_entity_but_viewer_cannot(): void
    {
        $data = [
            'type' => 'customer',
            'source_system' => 'erp',
            'external_id' => 'CUST-001',
            'data' => ['name' => 'ACME Corp'],
        ];

        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson('/api/v1/mdm-entities', $data, $this->tenantHeaders($fixtures['tenant']))
            ->assertCreated();

        $this->actingAsSanctum($fixtures['viewer'])
            ->postJson('/api/v1/mdm-entities', $data, $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_manager_can_run_sync_but_viewer_cannot(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $integration = $fixtures['tenant']->integrations()->create([
            'name' => 'SAP',
            'type' => 'sap',
            'is_active' => true,
        ]);

        $this->actingAsSanctum($fixtures['manager'])
            ->postJson("/api/v1/integrations/{$integration->id}/sync", ['type' => 'full'], $this->tenantHeaders($fixtures['tenant']))
            ->assertAccepted();

        $this->actingAsSanctum($fixtures['viewer'])
            ->postJson("/api/v1/integrations/{$integration->id}/sync", ['type' => 'full'], $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_audit_logs_are_restricted_to_users_with_permission(): void
    {
        $fixtures = $this->seedTenantWithMembers();

        $this->actingAsSanctum($fixtures['viewer'])
            ->getJson('/api/v1/audit-logs', $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();

        $this->actingAsSanctum($fixtures['admin'])
            ->getJson('/api/v1/audit-logs', $this->tenantHeaders($fixtures['tenant']))
            ->assertOk();
    }
}
