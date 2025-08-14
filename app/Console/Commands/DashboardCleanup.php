<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DashboardCleanup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dashboard:cleanup {--days=30 : Días de logs a mantener}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar logs antiguos del dashboard y optimizar el sistema';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $this->info("🧹 Iniciando limpieza del dashboard...");
        $this->line("📅 Manteniendo logs de los últimos {$days} días");
        
        try {
            // Limpiar logs de Laravel
            $this->cleanLaravelLogs($cutoffDate);
            
            // Limpiar logs de base de datos
            $this->cleanDatabaseLogs($cutoffDate);
            
            // Limpiar archivos temporales
            $this->cleanTempFiles();
            
            // Optimizar base de datos
            $this->optimizeDatabase();
            
            $this->info("✅ Limpieza completada exitosamente");
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Limpiar logs de Laravel
     */
    private function cleanLaravelLogs(Carbon $cutoffDate)
    {
        $this->line("📝 Limpiando logs de Laravel...");
        
        $logPath = storage_path('logs');
        $files = File::glob($logPath . '/*.log');
        
        $deletedCount = 0;
        foreach ($files as $file) {
            $fileTime = Carbon::createFromTimestamp(File::lastModified($file));
            
            if ($fileTime->lt($cutoffDate)) {
                File::delete($file);
                $deletedCount++;
            }
        }
        
        $this->info("   Eliminados {$deletedCount} archivos de log antiguos");
    }
    
    /**
     * Limpiar logs de base de datos
     */
    private function cleanDatabaseLogs(Carbon $cutoffDate)
    {
        $this->line("🗄️ Limpiando logs de base de datos...");
        
        try {
            // Limpiar logs de procesamiento antiguos
            $deletedCount = \App\Models\UploadProcessLog::where('created_at', '<', $cutoffDate)->delete();
            $this->info("   Eliminados {$deletedCount} registros de logs de procesamiento");
            
        } catch (\Exception $e) {
            $this->warn("   ⚠️ No se pudieron limpiar logs de BD: " . $e->getMessage());
        }
    }
    
    /**
     * Limpiar archivos temporales
     */
    private function cleanTempFiles()
    {
        $this->line("🗂️ Limpiando archivos temporales...");
        
        $tempPaths = [
            storage_path('app/temp'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];
        
        $deletedCount = 0;
        foreach ($tempPaths as $path) {
            if (File::exists($path)) {
                $files = File::allFiles($path);
                foreach ($files as $file) {
                    if ($file->getMTime() < (time() - 86400)) { // 24 horas
                        File::delete($file->getPathname());
                        $deletedCount++;
                    }
                }
            }
        }
        
        $this->info("   Eliminados {$deletedCount} archivos temporales");
    }
    
    /**
     * Optimizar base de datos
     */
    private function optimizeDatabase()
    {
        $this->line("⚡ Optimizando base de datos...");
        
        try {
            // Optimizar tablas principales
            $tables = ['uploads', 'upload_process_logs', 'products', 'product_variants'];
            
            foreach ($tables as $table) {
                \DB::statement("OPTIMIZE TABLE {$table}");
            }
            
            $this->info("   Tablas optimizadas: " . implode(', ', $tables));
            
        } catch (\Exception $e) {
            $this->warn("   ⚠️ No se pudo optimizar BD: " . $e->getMessage());
        }
    }
} 