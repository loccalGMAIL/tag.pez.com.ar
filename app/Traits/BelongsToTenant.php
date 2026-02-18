<?php

namespace App\Traits;

use App\Services\TenantManager;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        // Global scope: filtra automáticamente todas las queries por el tenant activo
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant()) {
                $builder->where(
                    (new static)->getTable() . '.organization_id',
                    $tenantManager->getTenantId()
                );
            }
        });

        // Auto-setea organization_id en la creación de nuevos registros
        static::creating(function ($model) {
            if (empty($model->organization_id)) {
                $tenantManager = app(TenantManager::class);
                if ($tenantManager->hasTenant()) {
                    $model->organization_id = $tenantManager->getTenantId();
                }
            }
        });
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    /**
     * Escape hatch para queries de super-admin que necesitan ver todos los tenants.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
