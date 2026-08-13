<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'email', 'token', 'roles', 'permissions', 'invited_by', 'accepted_at', 'expires_at'])]
class TenantInvitation extends Model
{
    use HasFactory;

    protected $table = 'tenant_invitations';

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'permissions' => 'array',
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
