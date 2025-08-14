<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\DashboardService;

class TestERetailConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eretail:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conexión a la base de datos eRetail (TitanDB)';

    /**
     * Execute the console command.
     */
    public function handle(DashboardService $dashboardService)
    {
        $this->info('🔌 Probando conexión a eRetail (TitanDB)...');
        
        try {
            $result = $dashboardService->testERetailConnection();
            
            if ($result['status'] === 'success') {
                $this->info('✅ Conexión exitosa');
                $this->line("📊 Tiempo de respuesta: {$result['response_time']}");
                $this->line("🕐 Timestamp: {$result['timestamp']}");
                
                // Probar métricas básicas con información detallada
                $this->info('📈 Probando consultas de métricas...');
                $this->testIndividualMetrics();
                
            } else {
                $this->error('❌ Error de conexión');
                $this->line("💬 Mensaje: {$result['message']}");
                $this->line("🕐 Timestamp: {$result['timestamp']}");
            }
            
        } catch (\Exception $e) {
            $this->error('💥 Error inesperado: ' . $e->getMessage());
            $this->line('🔍 Verifique la configuración en config/database.php y .env');
        }
        
        return 0;
    }
    
    /**
     * Probar cada métrica individualmente para diagnosticar problemas
     */
    private function testIndividualMetrics()
    {
        try {
            $this->line('🔍 Probando métricas individuales...');
            
            // 1. Total de Etiquetas
            $this->testMetric('Total de Etiquetas', function() {
                $shopCode = env('ERETAIL_DEFAULT_SHOP_CODE', 'TIENDA001');
                $this->line("   🏪 Usando ShopCode: {$shopCode}");
                
                return DB::connection('eretail')
                    ->table('Tag')
                    ->where('ShopCode', $shopCode)
                    ->count();
            });
            
            // 2. Etiquetas Vinculadas
            $this->testMetric('Etiquetas Vinculadas', function() {
                $shopCode = env('ERETAIL_DEFAULT_SHOP_CODE', 'TIENDA001');
                $this->line("   🏪 Usando ShopCode: {$shopCode}");
                $this->line("   📋 Extrayendo ShopCode de los primeros 4 dígitos del GoodsId");
                
                return DB::connection('eretail')
                    ->table('GoodsBind')
                    ->whereRaw('LEFT(GoodsId, 4) = ?', [$shopCode])
                    ->whereNotNull('GoodsId')
                    ->count();
            });
            
            // 3. Total de Productos
            $this->testMetric('Total de Productos', function() {
                $shopCode = env('ERETAIL_DEFAULT_SHOP_CODE', 'TIENDA001');
                $this->line("   🏪 Usando ShopCode: {$shopCode}");
                
                return DB::connection('eretail')
                    ->table('Goods')
                    ->where('ShopCode', $shopCode)
                    ->count();
            });
            
            // 4. AP en Línea
            $this->testMetric('AP en Línea', function() {
                $shopCode = env('ERETAIL_DEFAULT_SHOP_CODE', 'TIENDA001');
                $timeout = config('dashboard.ap_offline_timeout', 5);
                $timeoutMinutesAgo = \Carbon\Carbon::now()->subMinutes($timeout);
                $this->line("   🏪 Usando ShopCode: {$shopCode}");
                $this->line("   ⏰ Timeout AP: {$timeout} minutos");
                
                return DB::connection('eretail')
                    ->table('AP')
                    ->where('ShopCode', $shopCode)
                    ->where('ApStatus', 1)
                    ->where('LastHeartbeatTime', '>', $timeoutMinutesAgo)
                    ->count();
            });
            
        } catch (\Exception $e) {
            $this->error('❌ Error probando métricas: ' . $e->getMessage());
        }
    }
    
    /**
     * Probar una métrica específica
     */
    private function testMetric(string $name, callable $query)
    {
        try {
            $this->line("   📊 Probando: {$name}");
            
            $result = $query();
            
            if ($result !== null && $result !== false) {
                $this->info("      ✅ {$name}: " . number_format($result));
            } else {
                $this->warn("      ⚠️ {$name}: Sin resultado");
            }
            
        } catch (\Exception $e) {
            $this->error("      ❌ {$name}: Error - " . $e->getMessage());
            
            // Mostrar información adicional del error
            if (str_contains($e->getMessage(), 'Table')) {
                $this->line("         💡 Posible problema: Nombre de tabla incorrecto");
            } elseif (str_contains($e->getMessage(), 'Column')) {
                $this->line("         💡 Posible problema: Nombre de columna incorrecto");
            } elseif (str_contains($e->getMessage(), 'Access denied')) {
                $this->line("         💡 Posible problema: Permisos de usuario");
            }
        }
    }
}