<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\UploadProcessLog;
use App\Services\ExcelProcessorService;
use App\Services\ERetailService;
use App\Services\TagService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 3;

    protected Upload $upload;
    protected string $filePath;

    public function __construct(Upload $upload, string $filePath)
    {
        $this->upload = $upload;
        $this->filePath = $filePath;
    }

    public function handle(ExcelProcessorService $processor, TagService $tagService, ERetailService $eRetailService): void
    {
        // Aumentar límites para archivos grandes (18,000+ filas)
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        Log::info("ProcessUploadJob: Iniciando procesamiento del upload {$this->upload->id}");

        // 1. Procesar el Excel
        $processor->processFile($this->filePath, $this->upload->id);

        Log::info("ProcessUploadJob: Procesamiento Excel completado, iniciando refresh de etiquetas");

        // 2. Refresh automático de etiquetas para productos con cambios
        $this->refreshTagsAfterProcessing($tagService, $eRetailService);
    }

    private function refreshTagsAfterProcessing(TagService $tagService, ERetailService $eRetailService): void
    {
        try {
            // Obtener variant IDs con cambios de precio o barcode
            $changedVariantIds = UploadProcessLog::where('upload_id', $this->upload->id)
                ->where('status', 'success')
                ->whereIn('action', ['created', 'updated'])
                ->where(function ($query) {
                    $query->where('price_changed', true)
                          ->orWhere('barcode_changed', true);
                })
                ->with('productVariant')
                ->get()
                ->pluck('productVariant.id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($changedVariantIds)) {
                Log::info("ProcessUploadJob: No hay variantes con cambios para refrescar etiquetas", [
                    'upload_id' => $this->upload->id
                ]);
                return;
            }

            Log::info("ProcessUploadJob: Buscando Tag IDs para variantes con cambios", [
                'upload_id' => $this->upload->id,
                'variant_ids_count' => count($changedVariantIds)
            ]);

            // Obtener Tag IDs desde eRetail DB
            $tagIds = $tagService->getTagIdsByGoodsCodes($changedVariantIds, $this->upload->shop_code);

            if (empty($tagIds)) {
                Log::info("ProcessUploadJob: No se encontraron etiquetas vinculadas", [
                    'upload_id' => $this->upload->id
                ]);
                return;
            }

            Log::info("ProcessUploadJob: Refrescando etiquetas en bloques de 50", [
                'upload_id' => $this->upload->id,
                'total_tags' => count($tagIds)
            ]);

            // Refrescar en bloques de 50
            $result = $eRetailService->refreshTagsInBatches($tagIds, $this->upload->shop_code);

            Log::info("ProcessUploadJob: Refresh de etiquetas completado", [
                'upload_id' => $this->upload->id,
                'resultado' => $result
            ]);

        } catch (\Exception $e) {
            // No relanzar: el procesamiento Excel ya terminó bien,
            // un error en el refresh no debería marcar el job como fallido
            Log::error("ProcessUploadJob: Error en refresh automático de etiquetas", [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessUploadJob: Job fallido para upload {$this->upload->id}", [
            'error' => $exception->getMessage()
        ]);

        $this->upload->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage()
        ]);
    }
}
