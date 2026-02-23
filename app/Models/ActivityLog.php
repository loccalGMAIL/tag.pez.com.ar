<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'level',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    const TYPES  = ['auth', 'upload', 'tags', 'settings', 'eretail', 'system'];
    const LEVELS = ['info', 'warning', 'error'];

    // ---- Relaciones ----

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ---- Scopes ----

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByOrg($query, int $orgId)
    {
        return $query->where('organization_id', $orgId);
    }

    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to . ' 23:59:59']);
    }
}
