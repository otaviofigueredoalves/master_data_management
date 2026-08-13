<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantUser;

/**
 * Holds the tenant and membership resolved for the current request.
 *
 * A user may belong to many tenants, so every tenant-aware operation must
 * run against a single "active" tenant. On the API this is resolved from the
 * X-Tenant-ID header; on the web it falls back to the value stored on the
 * session and the user's current_tenant_id column.
 */
class TenantContext
{
    public function __construct(
        public readonly ?Tenant $tenant,
        public readonly ?TenantUser $membership,
    ) {}

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function tenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function membershipRoleName(): ?string
    {
        return $this->membership?->role?->name;
    }

    public function membershipHasPermission(string $permission): bool
    {
        return (bool) $this->membership?->hasPermissionTo($permission);
    }
}
