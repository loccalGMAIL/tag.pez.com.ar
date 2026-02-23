<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\UploadProcessLog;  // 🔥 NUEVO: Usar UploadProcessLog en lugar de ProductUpdateLog
use App\Models\ProductVariant;    // 🔥 NUEVO: Para obtener variantes
use App\Jobs\ProcessUploadJob;
use App\Services\ExcelProcessorService;
use App\Services\ERetailService;
use App\Services\TagService;
use App\Services\UploadLogService;  // 🔥 NUEVO: Servicio especializado
use App\Services\ActivityLogger;
use App\Models\AppSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class UploadController extends Controller
{
    private $uploadLogService;

    public function __construct(UploadLogService $uploadLogService)
    {
        $this->uploadLogService = $uploadLogService;
    }

    /**
     * Mostrar lista de uploads
     */
    public function index()
    {
        $uploads = Upload::orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('uploads.index', compact('uploads'));
    }
    
    /**
     * Mostrar formulario de carga
     */
    public function create()
    {
        return view('uploads.create');
    }

    /**
     * Almacenar archivo y procesar
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'shop_code' => 'nullable|string'
        ]);
        
        DB::beginTransaction();
        
        try {
            // Guardar archivo
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads', $filename);
            
            Log::info("Archivo guardado en: {$path}");
            
            // Crear registro de upload
            $upload = Upload::create([
                'filename' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'shop_code' => $request->shop_code ?? config('eretail.default_shop_code'),
                'user_id' => auth()->id() ?? null,
                'status' => 'pending'
            ]);
            
            Log::info("Upload creado con ID: {$upload->id}");
            
            DB::commit();

            // Contar filas del Excel para validar límite
            $maxProducts = (int) AppSetting::get('upload_max_products', 5000);
            $fullPath = storage_path('app/private/' . $path);
            $spreadsheet = IOFactory::load($fullPath);
            $rowCount = $spreadsheet->getActiveSheet()->getHighestDataRow() - 1; // Sin header
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            if ($rowCount > $maxProducts) {
                $upload->update([
                    'status' => 'pending_approval',
                    'total_products' => $rowCount,
                ]);

                Log::warning("Upload {$upload->id} excede el límite: {$rowCount} filas (máx {$maxProducts})");

                return redirect()
                    ->route('uploads.index')
                    ->with('warning', "El archivo contiene {$rowCount} productos (límite: {$maxProducts}). Requiere aprobación del administrador para procesarse.");
            }

            // Despachar Job para procesamiento asíncrono
            $upload->update(['status' => 'processing']);
            ProcessUploadJob::dispatch($upload, $path);

            Log::info("Job despachado para upload {$upload->id}");

            ActivityLogger::upload('upload_created', "Upload creado: {$file->getClientOriginalName()} ({$rowCount} productos)", $upload, [
                'total_products' => $rowCount,
            ]);

            return redirect()
                ->route('uploads.show', $upload)
                ->with('info', 'Archivo recibido. El procesamiento se ejecuta en segundo plano.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al cargar archivo: " . $e->getMessage());
            
            return redirect()
                ->back()
                ->with('error', 'Error al cargar archivo: ' . $e->getMessage())
                ->withInput();
        }
    }

/**
 * 🔥 MOSTRAR DETALLES DE UN UPLOAD - NUEVA ARQUITECTURA
 */
public function show(Upload $upload)
{
    try {
        // Obtener mapeo completo variantId → tagId para todos los productos del upload
        $allVariantIds = UploadProcessLog::where('upload_id', $upload->id)
            ->whereNotNull('product_variant_id')
            ->pluck('product_variant_id')
            ->unique()
            ->values()
            ->toArray();

        $tagMapping = [];
        if (!empty($allVariantIds)) {
            $tagService = app(TagService::class);
            $tagMapping = $tagService->getTagMappingByGoodsCodes($allVariantIds, $upload->shop_code);
        }

        // Construir query con filtros
        $logsQuery = UploadProcessLog::where('upload_id', $upload->id)
            ->with(['productVariant.product'])
            ->orderBy('created_at', 'desc');

        if (request('status')) {
            $logsQuery->where('status', request('status'));
        }
        if (request('action')) {
            $logsQuery->where('action', request('action'));
        }
        if (request('esl') === 'vinculados') {
            $logsQuery->whereIn('product_variant_id', array_keys($tagMapping) ?: [0]);
        } elseif (request('esl') === 'sin_vincular') {
            $logsQuery->whereNotIn('product_variant_id', array_keys($tagMapping) ?: [0]);
        }

        $logs = $logsQuery->paginate(20)->withQueryString();

        $statsFromService = $this->uploadLogService->getUploadStats($upload->id);
        $statistics = [
            'total' => $statsFromService['total_logs'] ?? 0,
            'procesados' => ($statsFromService['success'] ?? 0) + ($statsFromService['failed'] ?? 0),
            'creados' => $statsFromService['created'] ?? 0,
            'actualizados' => $statsFromService['updated'] ?? 0,
            'omitidos' => $statsFromService['skipped'] ?? 0,
            'errores' => $statsFromService['failed'] ?? 0,
            'progreso' => $statsFromService['success_rate'] ?? 0
        ];

        return view('uploads.show', compact('upload', 'logs', 'statistics', 'tagMapping'));

    } catch (\Exception $e) {
        \Log::error('Error in show method:', [
            'error' => $e->getMessage(),
            'upload_id' => $upload->id
        ]);

        $statistics = [
            'total' => 0,
            'procesados' => 0,
            'creados' => 0,
            'actualizados' => 0,
            'omitidos' => 0,
            'errores' => 0,
            'progreso' => 0
        ];

        $logs = collect([]);
        $tagMapping = [];

        return view('uploads.show', compact('upload', 'logs', 'statistics', 'tagMapping'))
            ->with('error', 'Error cargando estadísticas: ' . $e->getMessage());
    }
}




    /**
     * Descargar archivo original
     */
    public function download(Upload $upload)
    {
        if (!Storage::exists($upload->filename)) {
            abort(404, 'Archivo no encontrado');
        }
        
        return Storage::download($upload->filename, $upload->original_filename);
    }
    
    /**
     * 🔥 REPORTE DE PROCESAMIENTO - NUEVA ARQUITECTURA
     */
    public function report(Upload $upload)
    {
        // 🔥 USAR UploadProcessLog con relaciones
        $logs = UploadProcessLog::where('upload_id', $upload->id)
            ->with(['productVariant.product'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 🔥 OBTENER estadísticas detalladas
        $detailedStats = $this->uploadLogService->getDetailedErrorSummary($upload->id);
        $uploadProgress = $this->uploadLogService->getProcessingProgress($upload->id);
            
        return view('uploads.report', compact('upload', 'logs', 'detailedStats', 'uploadProgress'));
    }
    
    /**
     * REFRESCAR ETIQUETAS - Todos los productos procesados exitosamente del upload
     */
    public function refreshTags(Upload $upload)
    {
        try {
            // Todos los variants procesados exitosamente
            $changedVariantIds = UploadProcessLog::where('upload_id', $upload->id)
                ->where('status', 'success')
                ->whereIn('action', ['created', 'updated'])
                ->with('productVariant')
                ->get()
                ->pluck('productVariant.id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (empty($changedVariantIds)) {
                return redirect()
                    ->back()
                    ->with('info', 'No hay productos procesados para actualizar etiquetas');
            }

            Log::info("Buscando Tag IDs para variantes con cambios", [
                'upload_id' => $upload->id,
                'variant_ids' => $changedVariantIds,
                'count' => count($changedVariantIds)
            ]);

            // 🔥 Obtener Tag IDs desde eRetail DB
            $tagService = app(TagService::class);
            $tagIds = $tagService->getTagIdsByGoodsCodes($changedVariantIds, $upload->shop_code);

            if (empty($tagIds)) {
                return redirect()
                    ->back()
                    ->with('warning', 'No se encontraron etiquetas vinculadas para los productos modificados');
            }

            Log::info("Tag IDs encontrados para actualización", [
                'upload_id' => $upload->id,
                'tag_ids_count' => count($tagIds),
                'primeros_3_tags' => array_slice($tagIds, 0, 3)
            ]);

            // 🔥 Refrescar en bloques de 50 tags (refreshType=4)
            $eRetailService = app(ERetailService::class);
            $result = $eRetailService->refreshTagsInBatches($tagIds, $upload->shop_code);

            if ($result['exitosos'] > 0) {
                ActivityLogger::upload('upload_tags_manual_refresh', "Refresh manual de etiquetas: {$result['exitosos']} enviadas", $upload, [
                    'tags_refreshed'  => $result['exitosos'],
                    'total_enviados'  => $result['total_enviados'],
                    'batches'         => $result['batches'],
                ]);

                return redirect()
                    ->back()
                    ->with('success', "Actualización iniciada para {$result['exitosos']} de {$result['total_enviados']} etiquetas en {$result['batches']} bloques");
            }

            return redirect()
                ->back()
                ->with('error', 'Error al solicitar actualización de etiquetas');

        } catch (\Exception $e) {
            Log::error("Error refrescando etiquetas", [
                'upload_id' => $upload->id,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Obtener progreso de procesamiento vía AJAX
     * Usa campos del modelo Upload (actualizados cada 10 productos) para respuesta rápida
     */
    public function getProgress(Upload $upload)
    {
        try {
            $upload->refresh();

            $total = $upload->total_products ?? 0;
            $processed = $upload->processed_products ?? 0;
            $percentage = $total > 0 ? round(($processed / $total) * 100, 2) : 0;

            $isComplete = in_array($upload->status, ['completed', 'failed']);

            return response()->json([
                'progress_percentage' => $percentage,
                'processed' => $processed,
                'total_products' => $total,
                'status' => $upload->status,
                'is_complete' => $isComplete,
                'error_message' => $upload->error_message,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 🔥 NUEVO: Reintentar productos fallidos
     */
    public function retry(Upload $upload)
    {
        try {
            $retryCount = $this->uploadLogService->retryFailedLogs($upload->id);
            
            if ($retryCount > 0) {
                return redirect()
                    ->back()
                    ->with('success', "Se marcaron {$retryCount} productos para reintento.");
            } else {
                return redirect()
                    ->back()
                    ->with('info', 'No hay productos fallidos para reintentar.');
            }
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al reintentar: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 NUEVO: Exportar logs a CSV
     */
    public function exportLogs(Upload $upload, Request $request)
    {
        try {
            $status = $request->get('status'); // 'failed', 'success', null (todos)
            
            $csvData = $this->uploadLogService->exportLogsToCSV($upload->id, $status);
            
            $filename = "upload_{$upload->id}_logs";
            if ($status) {
                $filename .= "_{$status}";
            }
            $filename .= "_" . date('Y-m-d_H-i-s') . ".csv";
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];
            
            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            };
            
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    /**
     * Procesar upload (método privado) - MANTENIDO IGUAL
     */
    private function processUpload($uploadId)
    {
        try {
            $upload = Upload::find($uploadId);
            $processor = app(ExcelProcessorService::class);
            
            $processor->processFile(
                storage_path('app/private/' . $upload->filename),
                $uploadId
            );
            
        } catch (\Exception $e) {
            Log::error("Error procesando upload {$uploadId}: " . $e->getMessage());
        }
    }
}