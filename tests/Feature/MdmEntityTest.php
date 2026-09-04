<?php

namespace Tests\Feature;

use App\Models\MdmEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MdmEntityTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_entities_from_active_tenant(): void
    {
        $fixtures = $this->seedTenantWithMembers();
        $other = $this->seedTenantWithMembers()['tenant'];

        MdmEntity::factory()->count(3)->create(['tenant_id' => $fixtures['tenant']->id]);
        MdmEntity::factory()->count(5)->create(['tenant_id' => $other->id]);

        $response = $this->actingAsSanctum($fixtures['admin'])
            ->getJson('/api/v1/mdm-entities', $this->tenantHeaders($fixtures['tenant']));

        $response->assertOk()->assertJsonCount(3, 'data.data');
    }

    public function test_update_increments_version_and_audits(): void
    {
        $fixtures = $this->seedTenantWithMembers();
        $entity = MdmEntity::factory()->create([
            'tenant_id' => $fixtures['tenant']->id,
            'version' => 1,
        ]);

        $this->actingAsSanctum($fixtures['admin'])
            ->putJson("/api/v1/mdm-entities/{$entity->id}", [
                'data' => ['name' => 'Renamed'],
            ], $this->tenantHeaders($fixtures['tenant']))
            ->assertOk();

        $this->assertDatabaseHas('mdm_entities', [
            'id' => $entity->id,
            'version' => 2,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $fixtures['tenant']->id,
            'action' => 'entity.updated',
        ]);
    }

    public function test_normalize_promotes_entity_to_master(): void
    {
        $fixtures = $this->seedTenantWithMembers();
        $entity = MdmEntity::factory()->create([
            'tenant_id' => $fixtures['tenant']->id,
            'data' => ['name' => '  ACME  ', 'email' => ' CONTACT@acme.COM '],
            'is_master' => false,
        ]);

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson("/api/v1/mdm-entities/{$entity->id}/normalize", [], $this->tenantHeaders($fixtures['tenant']))
            ->assertOk()
            ->assertJsonPath('data.is_master', true);

        $fresh = $entity->fresh();

        $this->assertSame('ACME', $fresh->normalized_data['name']);
        $this->assertSame('contact@acme.com', $fresh->normalized_data['email']);
    }

    public function test_viewer_cannot_normalize_entity(): void
    {
        $fixtures = $this->seedTenantWithMembers();
        $entity = MdmEntity::factory()->create([
            'tenant_id' => $fixtures['tenant']->id,
            'is_master' => false,
        ]);

        $this->actingAsSanctum($fixtures['viewer'])
            ->postJson("/api/v1/mdm-entities/{$entity->id}/normalize", [], $this->tenantHeaders($fixtures['tenant']))
            ->assertForbidden();
    }

    public function test_normalize_rejects_entity_from_another_tenant(): void
    {
        $tenantA = $this->seedTenantWithMembers();
        $tenantB = $this->seedTenantWithMembers()['tenant'];
        $entityB = MdmEntity::factory()->create([
            'tenant_id' => $tenantB->id,
            'is_master' => false,
        ]);

        $this->actingAsSanctum($tenantA['admin'])
            ->postJson("/api/v1/mdm-entities/{$entityB->id}/normalize", [], $this->tenantHeaders($tenantA['tenant']))
            ->assertForbidden();
    }
}
