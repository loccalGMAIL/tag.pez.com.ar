<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    private ?Organization $currentTenant = null;

    public function setTenant(Organization $organization): void
    {
        $this->currentTenant = $organization;
        $this->applyERetailDbConnection();
    }

    public function getTenant(): ?Organization
    {
        return $this->currentTenant;
    }

    public function hasTenant(): bool
    {
        return $this->currentTenant !== null;
    }

    public function getTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    public function clear(): void
    {
        $this->currentTenant = null;
    }

    /**
     * Sobreescribe la conexión 'eretail' en runtime con las credenciales del tenant actual.
     * TagService y DashboardService usan DB::connection('eretail'), que automáticamente
     * usará esta configuración sin necesidad de cambios en esos servicios.
     *
     * IMPORTANTE: siempre sobreescribe aunque no haya host configurado.
     * Si el tenant no tiene DB eRetail, la conexión fallará correctamente
     * en lugar de caer silenciosamente en las credenciales del config/database.php.
     */
    private function applyERetailDbConnection(): void
    {
        if (!$this->currentTenant) {
            return;
        }

        $config = $this->currentTenant->getERetailDbConfig();

        config(['database.connections.eretail' => $config]);

        // Purge descarta la conexión en caché para que la próxima llamada
        // a DB::connection('eretail') use la nueva config.
        // No llamamos reconnect() aquí para no forzar una conexión inmediata
        // (fallaría si no hay host configurado).
        DB::purge('eretail');
    }
}
