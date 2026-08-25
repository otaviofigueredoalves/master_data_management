<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['tenant_id', 'parent_id', 'child_id', 'relation_type', 'metadata'])]
class MdmEntityRelation extends Model
{
    use HasFactory;

    protected $table = 'mdm_entity_relations';

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MdmEntity::class, 'parent_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(MdmEntity::class, 'child_id');
    }
}
