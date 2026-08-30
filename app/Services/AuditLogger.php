<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function log(string $action, $entity, ?User $user = null, ?array $oldValues = null, ?array $newValues = null): AuditLog
    {
        $user ??= auth()->user();

        $request = request();

        return AuditLog::create([
            'tenant_id' => $this->resolveTenantId($entity, $user),
            'user_id' => $user?->id,
            'action' => $action,
            'entity_type' => $this->resolveEntityType($entity),
            'entity_id' => $this->resolveEntityId($entity),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    protected function resolveEntityType($entity): string
    {
        if ($entity instanceof Model) {
            return $entity->getMorphClass();
        }

        if (is_string($entity)) {
            return $entity;
        }

        return get_debug_type($entity);
    }

    protected function resolveEntityId($entity): ?int
    {
        return $entity instanceof Model ? $entity->getKey() : null;
    }

    protected function resolveTenantId($entity, ?User $user): ?int
    {
        if ($entity instanceof Model && $entity->getAttribute('tenant_id') !== null) {
            return (int) $entity->getAttribute('tenant_id');
        }

        return $user?->current_tenant_id ?? null;
    }
}
