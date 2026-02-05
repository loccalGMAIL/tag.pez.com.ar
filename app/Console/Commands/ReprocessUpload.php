<?php
// app/Console/Commands/ReprocessUpload.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Upload;
use App\Services\ExcelProcessorService;

class ReprocessUpload extends Command
{
    protected $signature = 'upload:reprocess {id : ID del upload} {--force : Saltar confirmación}';
    protected $description = 'Reprocesar un upload que falló o quedó pendiente';

    public function handle()
    {
        $uploadId = $this->argument('id');
        $upload = Upload::find($uploadId);
        
        if (!$upload) {
            $this->error("Upload #{$uploadId} no encontrado");
            return 1;
        }
        
        $this->info("Reprocesando upload #{$uploadId}");
        $this->info("Archivo: {$upload->original_filename}");
        $this->info("Estado actual: {$upload->status}");
        
        if (!$this->option('force') && !$this->confirm('¿Desea continuar?')) {
            return 0;
        }
        
        try {
            // Resetear contadores
            $upload->update([
                'status' => 'processing',
                'processed_products' => 0,
                'created_products' => 0,
                'updated_products' => 0,
                'failed_products' => 0,
                'skipped_products' => 0,
                'error_message' => null
            ]);
            
            // Limpiar logs anteriores
            $upload->processLogs()->delete();
            
            $processor = app(ExcelProcessorService::class);
            $processor->processFile($upload->filename, $upload->id);
            
            $this->info("Procesamiento completado exitosamente");
            $this->info("Productos procesados: {$upload->processed_products}");
            $this->info("Creados: {$upload->created_products}");
            $this->info("Actualizados: {$upload->updated_products}");
            $this->info("Omitidos: {$upload->skipped_products}");
            $this->info("Errores: {$upload->failed_products}");
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}