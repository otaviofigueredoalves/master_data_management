<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['tenant_id', 'name', 'type', 'credentials', 'settings', 'is_active', 'last_sync_at'])]
class Integration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'integrations';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_sync_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(SyncJob::class);
    }
}
