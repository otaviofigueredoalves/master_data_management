<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'type', 'source_system', 'external_id', 'data', 'normalized_data', 'is_master', 'version', 'created_by', 'updated_by'])]
class MdmEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mdm_entities';

    protected function casts(): array
    {
        return [
            'is_master' => 'boolean',
            'version' => 'integer',
            'data' => 'array',
            'normalized_data' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
