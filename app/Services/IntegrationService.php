<?php

namespace App\Services;

use App\Jobs\RunIntegrationSync;
use App\Models\Integration;
use App\Models\SyncJob;

class IntegrationService
{
    public function sync(Integration $integration, string $type = 'full'): SyncJob
    {
        $job = SyncJob::create([
            'tenant_id' => $integration->tenant_id,
            'integration_id' => $integration->id,
            'type' => $type,
            'status' => 'pending',
            'payload' => [
                'dispatched_at' => now()->toIso8601String(),
                'queue' => 'sync',
            ],
        ]);

        RunIntegrationSync::dispatch($integration, $job);

        return $job;
    }
}
