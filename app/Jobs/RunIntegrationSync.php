<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\SyncJob;
use App\Services\AuditLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunIntegrationSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public readonly Integration $integration,
        public readonly SyncJob $syncJob,
    ) {
        $this->onQueue('sync');
    }

    public function handle(): void
    {
        $this->syncJob->update(['status' => 'running', 'started_at' => now()]);

        try {
            // Integration drivers would translate the generic payload into
            // provider-specific calls (SAP OData, Salesforce REST, ...).
            $result = [
                'integration_id' => $this->integration->id,
                'type' => $this->syncJob->type,
                'status' => 'completed',
                'processed' => 0,
                'synced_at' => now()->toIso8601String(),
            ];

            $this->integration->update(['last_sync_at' => now()]);

            $this->syncJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result' => $result,
            ]);

            app(AuditLogger::class)->log(
                'integration.sync_completed',
                $this->integration,
                null,
                null,
                $result,
            );
        } catch (\Throwable $e) {
            $this->syncJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            app(AuditLogger::class)->log(
                'integration.sync_failed',
                $this->integration,
                null,
                null,
                ['error' => $e->getMessage()],
            );

            throw $e;
        }
    }
}
