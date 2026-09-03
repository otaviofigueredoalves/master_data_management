<?php

namespace Tests\Feature;

use App\Jobs\RunIntegrationSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IntegrationSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_dispatches_job_and_creates_sync_job_record(): void
    {
        Queue::fake([RunIntegrationSync::class]);

        $fixtures = $this->seedTenantWithMembers();

        $integration = $fixtures['tenant']->integrations()->create([
            'name' => 'Salesforce',
            'type' => 'salesforce',
            'is_active' => true,
        ]);

        $this->actingAsSanctum($fixtures['admin'])
            ->postJson("/api/v1/integrations/{$integration->id}/sync", ['type' => 'incremental'], $this->tenantHeaders($fixtures['tenant']))
            ->assertAccepted();

        $this->assertDatabaseHas('sync_jobs', [
            'tenant_id' => $fixtures['tenant']->id,
            'integration_id' => $integration->id,
            'type' => 'incremental',
            'status' => 'pending',
        ]);

        Queue::assertPushed(RunIntegrationSync::class);
    }
}
