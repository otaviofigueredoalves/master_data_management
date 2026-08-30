<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'user_id', 'action', 'entity_type', 'entity_id', 'old_values', 'new_values', 'ip_address', 'user_agent'])]
class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_logs';

    public $timestamps = false;

    /**
     * Friendly, manager-facing names for the audited entity types.
     * Keys are the morph class names stored in the `entity_type` column.
     */
    public const ENTITY_TYPE_LABELS = [
        MdmEntity::class => 'Entidade MDM',
        MdmEntityRelation::class => 'Relacionamento',
        Integration::class => 'Integração',
        SyncJob::class => 'Sincronização',
        Tenant::class => 'Workspace',
        TenantUser::class => 'Membro',
        User::class => 'Usuário',
        TenantInvitation::class => 'Convite',
    ];

    protected $appends = ['entity_label'];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function getEntityLabelAttribute(): string
    {
        $label = self::ENTITY_TYPE_LABELS[$this->entity_type] ?? null;

        if ($label !== null) {
            return $label;
        }

        // Fallback: humanize the class basename for unlisted types.
        $short = class_basename((string) $this->entity_type);

        return $short === '' ? (string) $this->entity_type : preg_replace('/(?<!^)([A-Z])/', ' $1', $short);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
