<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Upload;
use App\Models\UploadProcessLog;
use App\Models\AppSetting;

class DashboardService
{
    /**
     * Obtener el ShopCode desde la configuración
     */
    private function getShopCode(): string
    {
        return AppSetting::get('default_shop_code', env('ERETAIL_DEFAULT_SHOP_CODE', '0010'));
    }
    /**
     * Obtener todas las métricas del dashboard
     */
    public function getDashboardMetrics(): array
    {
        try {
            return [
                'total_etiquetas' => $this->getTotalEtiquetas(),
                'etiquetas_vinculadas' => $this->getEtiquetasVinculadas(),
                'total_productos' => $this->getTotalProductos(),
                'ap_online' => $this->getAPOnline(),
                'last_updated' => now()->format('H:i:s')
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo métricas del dashboard: ' . $e->getMessage());
            return [
                'total_etiquetas' => 0,
                'etiquetas_vinculadas' => 0,
                'total_productos' => 0,
                'ap_online' => 0,
                'last_updated' => now()->format('H:i:s'),
                'error' => 'Error de conexión'
            ];
        }
    }

    /**
     * Obtener total de etiquetas desde Tag
     */
    private function getTotalEtiquetas(): int
    {
        try {
            $shopCode = $this->getShopCode();
            
            $result = DB::connection('eretail')
                ->table('Tag')
                ->where('ShopCode', $shopCode)
                ->count();
            
            return (int) $result;
        } catch (\Exception $e) {
            Log::error('Error obteniendo total de etiquetas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener etiquetas vinculadas desde GoodsBind
     */
    private function getEtiquetasVinculadas(): int
    {
        try {
            $shopCode = $this->getShopCode();
            
            $result = DB::connection('eretail')
                ->table('GoodsBind')
                ->whereRaw('LEFT(GoodsId, 4) = ?', [$shopCode])
                ->whereNotNull('GoodsId')
                ->count();
            
            return (int) $result;
        } catch (\Exception $e) {
            Log::error('Error obteniendo etiquetas vinculadas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener total de productos únicos
     */
    private function getTotalProductos(): int
    {
        try {
            $shopCode = $this->getShopCode();
            
            $result = DB::connection('eretail')
                ->table('Goods')
                ->where('ShopCode', $shopCode)
                ->count();
            
            return (int) $result;
        } catch (\Exception $e) {
            Log::error('Error obteniendo total de productos: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener APs en línea (status=1 y heartbeat reciente)
     */
    private function getAPOnline(): int
    {
        try {
            $shopCode = $this->getShopCode();
            $timeout = config('dashboard.ap_offline_timeout', 5);
            $timeoutMinutesAgo = Carbon::now()->subMinutes($timeout);
            
            $result = DB::connection('eretail')
                ->table('AP')
                ->where('ShopCode', $shopCode)
                ->whereIn('ApStatus', [1, 2]) // 1=Online, 2=Heartbeat (ambos son "en línea")
                ->where('LastHeartbeatTime', '>', $timeoutMinutesAgo)
                ->count();
            
            return (int) $result;
        } catch (\Exception $e) {
            Log::error('Error obteniendo APs en línea: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener actividades recientes combinando Laravel y eRetail
     */
    public function getRecentActivities(): array
    {
        $activities = [];
        
        try {
            // Actividades de Laravel (uploads)
            $laravelActivities = $this->getLaravelActivities();
            $activities = array_merge($activities, $laravelActivities);
            
            // Actividades de eRetail (AP y GoodsBind)
            $eretailActivities = $this->getERetailActivities();
            $activities = array_merge($activities, $eretailActivities);
            
            // Si no hay actividades recientes, buscar en un rango más amplio
            if (empty($activities)) {
                Log::info('No hay actividades recientes, buscando en rango más amplio...');
                $activities = $this->getFallbackActivities();
            }
            
            // Ordenar por timestamp y limitar según configuración
            usort($activities, function($a, $b) {
                return $b['timestamp']->timestamp - $a['timestamp']->timestamp;
            });
            
            $limit = config('dashboard.recent_activities_limit', 10);
            return array_slice($activities, 0, $limit);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividades recientes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener actividades de Laravel (uploads y logs)
     */
    private function getLaravelActivities(): array
    {
        $activities = [];
        
        try {
            $hours = config('dashboard.recent_activities_hours', 24);
            // Si no hay actividades recientes, buscar en los últimos 7 días
            $timeRange = Carbon::now()->subHours($hours);
            
            // Uploads recientes
            $recentUploads = Upload::where('created_at', '>=', $timeRange)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($recentUploads as $upload) {
                $activities[] = [
                    'type' => 'upload',
                    'icon' => $upload->status === 'completed' ? 'fa-check' : 
                              ($upload->status === 'failed' ? 'fa-exclamation-triangle' : 'fa-clock'),
                    'color' => $upload->status === 'completed' ? 'green' : 
                              ($upload->status === 'failed' ? 'red' : 'yellow'),
                    'title' => 'Upload de archivo',
                    'description' => "Archivo: {$upload->original_filename} - Estado: {$upload->status}",
                    'timestamp' => $upload->created_at
                ];
            }
            
            // Logs de procesamiento recientes
            $recentLogs = UploadProcessLog::where('created_at', '>=', Carbon::now()->subHours($hours))
                ->whereIn('status', ['success', 'error'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($recentLogs as $log) {
                $activities[] = [
                    'type' => $log->status === 'success' ? 'processing' : 'error',
                    'icon' => $log->status === 'success' ? 'fa-cog' : 'fa-exclamation-triangle',
                    'color' => $log->status === 'success' ? 'blue' : 'red',
                    'title' => 'Procesamiento de productos',
                    'description' => "Producto: {$log->product_id} - {$log->message}",
                    'timestamp' => $log->created_at
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividades de Laravel: ' . $e->getMessage());
        }
        
        return $activities;
    }

    /**
     * Obtener actividades de eRetail (AP y GoodsBind)
     */
    private function getERetailActivities(): array
    {
        $activities = [];
        
        try {
            $hours = config('dashboard.recent_activities_hours', 24);
            $shopCode = $this->getShopCode();
            
            // Cambios de estado AP recientes - Solo mostrar desconexiones (offline)
            $recentAPChanges = DB::connection('eretail')
                ->table('AP')
                ->where('ShopCode', $shopCode)
                ->whereNotIn('ApStatus', [1, 2]) // Solo offline (excluir Online=1 y Heartbeat=2)
                ->where('LastOfflineTime', '>=', Carbon::now()->subHours($hours))
                ->orderBy('LastOfflineTime', 'desc')
                ->limit(3)
                ->get();

            foreach ($recentAPChanges as $ap) {
                $apName = !empty($ap->Remark) ? $ap->Remark : $ap->APID;
                $activities[] = [
                    'type' => 'ap_status',
                    'icon' => 'fa-wifi',
                    'color' => 'red',
                    'title' => 'AP Desconectado',
                    'description' => "AP {$apName} está offline",
                    'timestamp' => Carbon::parse($ap->LastOfflineTime)
                ];
            }
            
            // Vinculaciones recientes de etiquetas
            $recentBindings = DB::connection('eretail')
                ->table('GoodsBind')
                ->whereRaw('LEFT(GoodsId, 4) = ?', [$shopCode])
                ->where('LastUpdatedTime', '>=', Carbon::now()->subHours($hours))
                ->whereNotNull('GoodsId')
                ->orderBy('LastUpdatedTime', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentBindings as $binding) {
                $activities[] = [
                    'type' => 'binding',
                    'icon' => 'fa-link',
                    'color' => 'blue',
                    'title' => 'Vinculación de etiqueta',
                    'description' => "Producto {$binding->GoodsId} vinculado a dispositivo {$binding->DeviceId}",
                    'timestamp' => Carbon::parse($binding->LastUpdatedTime)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividades de eRetail: ' . $e->getMessage());
        }
        
        return $activities;
    }

    /**
     * Obtener actividades de respaldo cuando no hay datos recientes
     */
    private function getFallbackActivities(): array
    {
        $activities = [];
        
        try {
            // Buscar en los últimos 7 días para Laravel
            $recentUploads = Upload::where('created_at', '>=', Carbon::now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentUploads as $upload) {
                $activities[] = [
                    'type' => 'upload',
                    'icon' => $upload->status === 'completed' ? 'fa-check' : 
                              ($upload->status === 'failed' ? 'fa-exclamation-triangle' : 'fa-clock'),
                    'color' => $upload->status === 'completed' ? 'green' : 
                              ($upload->status === 'failed' ? 'red' : 'yellow'),
                    'title' => 'Upload de archivo',
                    'description' => "Archivo: {$upload->original_filename} - Estado: {$upload->status}",
                    'timestamp' => $upload->created_at
                ];
            }
            
            // Buscar en los últimos 7 días para eRetail
            $shopCode = $this->getShopCode();
            
            $recentBindings = DB::connection('eretail')
                ->table('GoodsBind')
                ->whereRaw('LEFT(GoodsId, 4) = ?', [$shopCode])
                ->where('LastUpdatedTime', '>=', Carbon::now()->subDays(7))
                ->whereNotNull('GoodsId')
                ->orderBy('LastUpdatedTime', 'desc')
                ->limit(3)
                ->get();
            
            foreach ($recentBindings as $binding) {
                $activities[] = [
                    'type' => 'binding',
                    'icon' => 'fa-link',
                    'color' => 'blue',
                    'title' => 'Vinculación de etiqueta',
                    'description' => "Producto {$binding->GoodsId} vinculado a dispositivo {$binding->DeviceId}",
                    'timestamp' => Carbon::parse($binding->LastUpdatedTime)
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo actividades de respaldo: ' . $e->getMessage());
        }
        
        return $activities;
    }

    /**
     * Probar conexión a eRetail
     */
    public function testERetailConnection(): array
    {
        try {
            $start = microtime(true);
            
            DB::connection('eretail')->getPdo();
            
            $responseTime = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'status' => 'success',
                'message' => 'Conexión exitosa',
                'response_time' => $responseTime . 'ms',
                'timestamp' => now()->format('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            Log::error('Error de conexión a eRetail: ' . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Error de conexión: ' . $e->getMessage(),
                'response_time' => null,
                'timestamp' => now()->format('Y-m-d H:i:s')
            ];
        }
    }
} 