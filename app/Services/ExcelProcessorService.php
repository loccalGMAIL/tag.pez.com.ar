<?php

namespace App\Services;

use App\Models\Upload;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\UploadProcessLog;
use App\Models\AppSetting;
use App\Services\ERetailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class ExcelProcessorService
{
    private $eRetailService;
    private $upload;
    private $discountPercentage;
    private $skipRows;

    public function __construct(ERetailService $eRetailService)
    {
        $this->eRetailService = $eRetailService;
        $this->discountPercentage = AppSetting::get('discount_percentage', 12);
        $this->skipRows = AppSetting::get('excel_skip_rows', 2);
    }

    /**
     * 🔥 PROCESAR ARCHIVO EXCEL - NUEVA ARQUITECTURA
     */
    public function processFile($filePath, $uploadId)
    {
        // Extender tiempo de ejecución para archivos grandes
        set_time_limit(600);

        Log::info("=== INICIANDO PROCESAMIENTO CON NUEVA ARQUITECTURA ===");
        Log::info("Upload ID: {$uploadId}");
        Log::info("Archivo: {$filePath}");

        $this->upload = Upload::find($uploadId);

        if (!$this->upload) {
            Log::error("Upload {$uploadId} no encontrado en la base de datos");
            throw new \Exception('Upload no encontrado');
        }

        try {
            // Marcar como procesando
            $this->upload->update(['status' => 'processing']);
            Log::info("Estado actualizado a 'processing'");

            // Leer archivo Excel
            $fullPath = storage_path('app/private/' . $filePath);
            Log::info("Ruta completa del archivo: {$fullPath}");

            if (!file_exists($fullPath)) {
                Log::error("Archivo no existe en: {$fullPath}");
                throw new \Exception('Archivo no encontrado: ' . $fullPath);
            }

            Log::info("Cargando archivo Excel...");
            $spreadsheet = IOFactory::load($fullPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray(null, true, true, false);

            Log::info("Archivo cargado. Total de filas: " . count($data));

            if (empty($data)) {
                throw new \Exception('El archivo está vacío');
            }

            // Procesar productos con nueva arquitectura
            $this->processProductsWithNewArchitecture($data);

            // Marcar como completado
            $this->upload->update(['status' => 'completed']);
            Log::info("=== PROCESAMIENTO COMPLETADO ===");

        } catch (\Exception $e) {
            Log::error("=== ERROR EN PROCESAMIENTO ===");
            Log::error("Mensaje: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            $this->upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * 🔥 PROCESAR PRODUCTOS CON NUEVA ARQUITECTURA
     */
    private function processProductsWithNewArchitecture($data)
    {
        // Encontrar índices de columnas
        $indices = $this->findColumnIndices($data);

        $skipRows = $this->skipRows;
        $headerRowIndex = $indices['header_row_index'];

        // Obtener filas de productos
        $rows = array_slice($data, $headerRowIndex + $skipRows + 1);
        $rows = array_values($rows);

        // Filtrar filas vacías
        $validRows = [];
        foreach ($rows as $index => $row) {
            if (!$this->isEmptyRow($row)) {
                $validRows[] = $row;
            } else {
                Log::debug("Fila " . ($index + $skipRows + 1) . " vacía detectada y excluida");
            }
        }

        $totalProducts = count($validRows);
        $this->upload->update(['total_products' => $totalProducts]);

        Log::info("Configuración de procesamiento", [
            'filas_omitidas' => $skipRows,
            'filas_validas_a_procesar' => $totalProducts,
            'nueva_arquitectura' => true
        ]);

        if ($totalProducts === 0) {
            throw new \Exception('No se encontraron productos válidos para procesar');
        }

        // Autenticación con eRetail
        Log::info("Autenticando con eRetail...");
        try {
            $this->eRetailService->login();
            Log::info("Autenticación exitosa con eRetail");
        } catch (\Exception $e) {
            Log::error("Error de autenticación con eRetail: " . $e->getMessage());
            throw new \Exception("No se pudo conectar con eRetail: " . $e->getMessage());
        }

        $variantsBatch = [];
        $processedCount = 0;

        // 🔥 PROCESAR CADA FILA - NUEVA LÓGICA CON VARIANTES
        foreach ($validRows as $index => $row) {
            $rowNumber = $index + $skipRows + 1;

            try {
                // Extraer datos de la fila
                $rowData = $this->extractRowData($row, $indices, $rowNumber);

                // Procesar producto y variante
                $variant = $this->processProductAndVariant($rowData);

                // 🔥 SOLO agregar al batch si la variante se creó exitosamente
                if ($variant && $variant->id) {
                    $variantsBatch[] = $variant;

                    // Procesar en lotes de 50
                    if (count($variantsBatch) >= 50) {
                        Log::info("Enviando batch de " . count($variantsBatch) . " variantes a eRetail");
                        $this->sendVariantsBatchToERetail($variantsBatch);
                        $variantsBatch = [];
                    }
                } else {
                    Log::warning("Variante no creada para fila {$rowNumber}, no se agregará al batch");
                    // El error ya fue loggeado en processProductAndVariant
                    $this->upload->increment('failed_variants');
                }

                $processedCount++;

                // Actualizar progreso cada 10 productos
                if ($processedCount % 10 == 0) {
                    $this->upload->update(['processed_products' => $processedCount]);
                    Log::info("Progreso: {$processedCount}/{$totalProducts} productos procesados");
                }

            } catch (\Exception $e) {
                Log::warning("Error procesando fila {$rowNumber}: " . $e->getMessage());

                // Intentar registrar error en UploadProcessLog (sin variant_id)
                try {
                    UploadProcessLog::create([
                        'upload_id' => $this->upload->id,
                        'product_variant_id' => null,  // 🔥 Null para errores de fila
                        'action' => 'skipped',
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                        'row_number' => $rowNumber
                    ]);
                } catch (\Exception $logError) {
                    Log::error("No se pudo crear log de error para fila {$rowNumber}: " . $logError->getMessage());
                }

                $this->upload->increment('failed_variants');
                $processedCount++;
            }
        }

        // Enviar últimas variantes
        if (!empty($variantsBatch)) {
            Log::info("Enviando último batch de " . count($variantsBatch) . " variantes a eRetail");
            $this->sendVariantsBatchToERetail($variantsBatch);
        }

        // Actualizar conteo final
        $this->upload->update(['processed_products' => $processedCount]);
        Log::info("=== PROCESAMIENTO COMPLETADO - NUEVA ARQUITECTURA ===");
        Log::info("Total procesados: {$processedCount} de {$totalProducts}");
    }

    /**
     * 🔥 PROCESAR PRODUCTO Y VARIANTE - CORREGIDO
     */
    private function processProductAndVariant($rowData)
    {
        DB::beginTransaction();

        try {
            Log::info("🔄 Procesando producto/variante", [
                'codigo_interno' => $rowData['codigo'],
                'descripcion' => substr($rowData['descripcion'], 0, 50)
            ]);

            // 🔥 NUEVO: Capturar valores anteriores ANTES de modificar
            $existingProduct = Product::where('codigo_interno', $rowData['codigo'])->first();
            $existingVariant = ProductVariant::where('codigo_interno', $rowData['codigo'])
                ->where('cod_barras', $rowData['cod_barras'])  // ← CORRECTO
                ->first();

            $oldPrice = $existingProduct ? $existingProduct->precio_final : null;
            $oldBarcode = $existingVariant ? $existingVariant->cod_barras : null;
            $oldDescripcion = $existingVariant ? $existingVariant->descripcion : null;

            // Si viene vacía o solo con espacios, no pisar la descripción existente.
            $rawDescripcion = $rowData['descripcion'] ?? null;
            $newDescripcion = null;
            if ($rawDescripcion !== null) {
                $rawDescripcion = (string) $rawDescripcion;
                if (trim($rawDescripcion) !== '') {
                    // Columna en BD: string(500)
                    $newDescripcion = substr($rawDescripcion, 0, 500);
                }
            }

            Log::debug("🔍 Valores anteriores capturados", [
                'old_price' => $oldPrice,
                'old_barcode' => $oldBarcode,
                'old_descripcion' => $oldDescripcion !== null ? substr($oldDescripcion, 0, 50) : null,
                'existing_product' => $existingProduct ? 'SÍ' : 'NO',
                'existing_variant' => $existingVariant ? 'SÍ' : 'NO'
            ]);

            // 1. Buscar o crear PRODUCTO MAESTRO
            $product = Product::firstOrCreate(
                ['codigo_interno' => $rowData['codigo']],
                [
                    'precio_final' => $rowData['precio_final'],
                    'precio_calculado' => $rowData['precio_descuento'] ?? $rowData['precio_final'],
                    'last_price_update' => $rowData['fec_ul_mo'] ?? now()
                ]
            );

            Log::info("✅ Producto maestro procesado", [
                'product_id' => $product->id,
                'was_created' => $product->wasRecentlyCreated
            ]);

            // 2. Buscar o crear VARIANTE
            $variant = ProductVariant::firstOrCreate(
                [
                    'codigo_interno' => $rowData['codigo'],
                    'cod_barras' => $rowData['cod_barras']  // ← SOLUCIÓN: Usar código de barras como criterio único
                ],
                [
                    'product_id' => $product->id,
                    'descripcion' => $newDescripcion ?? $rowData['descripcion'],
                    'is_active' => true
                ]
            );

            $action = $variant->wasRecentlyCreated ? 'created' : 'updated';

            Log::info("✅ Variante procesada", [
                'variant_id' => $variant->id,
                'action' => $action,
                'was_created' => $variant->wasRecentlyCreated
            ]);

            // 🔥 NUEVO: Determinar si hubo cambios (después de firstOrCreate)
            // Redondear a 2 decimales para evitar falsos positivos por precisión decimal
            $priceChanged = !$product->wasRecentlyCreated && $oldPrice !== null && round((float)$oldPrice, 2) !== round((float)$rowData['precio_final'], 2);
            $barcodeChanged = !$variant->wasRecentlyCreated && $oldBarcode !== null && $oldBarcode != $rowData['cod_barras'];
            $descripcionChanged = !$variant->wasRecentlyCreated && $newDescripcion !== null && $variant->descripcion !== $newDescripcion;

            Log::info("📊 Cambios detectados", [
                'price_changed' => $priceChanged,
                'barcode_changed' => $barcodeChanged,
                'descripcion_changed' => $descripcionChanged,
                'old_price' => $oldPrice,
                'new_price' => $rowData['precio_final'],
                'old_barcode' => $oldBarcode,
                'new_barcode' => $rowData['cod_barras'],
                'old_descripcion' => $oldDescripcion !== null ? substr($oldDescripcion, 0, 50) : null,
                'new_descripcion' => $newDescripcion !== null ? substr($newDescripcion, 0, 50) : null,
            ]);

            // 3. Procesar cambios para productos/variantes existentes
            if (!$variant->wasRecentlyCreated) {
                // 🔥 Log de cambio de precio (mantener log existente)
                if ($priceChanged) {
                    Log::info("💰 Registrando cambio de precio", [
                        'variant_id' => $variant->id,
                        'precio_anterior' => $oldPrice,
                        'precio_nuevo' => $rowData['precio_final']
                    ]);
                }

                // 🔥 NUEVO: Actualización de código de barras si cambió
                if ($barcodeChanged) {
                    $variant->update(['cod_barras' => $rowData['cod_barras']]);
                    Log::info("🔄 Código de barras actualizado", [
                        'variant_id' => $variant->id,
                        'codigo_anterior' => $oldBarcode,
                        'codigo_nuevo' => $rowData['cod_barras']
                    ]);
                }

                // 🔥 NUEVO: Actualización de descripción si cambió (salteando vacías/solo espacios)
                if ($descripcionChanged) {
                    $variant->update(['descripcion' => $newDescripcion]);
                    Log::info("📝 Descripción actualizada", [
                        'variant_id' => $variant->id,
                        'descripcion_anterior' => $oldDescripcion !== null ? substr($oldDescripcion, 0, 50) : null,
                        'descripcion_nueva' => substr($newDescripcion, 0, 50)
                    ]);
                }
            }

            // 4. Actualizar precio del producto maestro (siempre)
            $product->update([
                'precio_final' => $rowData['precio_final'],
                'precio_calculado' => $rowData['precio_descuento'] ?? $rowData['precio_final'],
                'last_price_update' => now()
            ]);

            // 5. Crear log de procesamiento - 🔥 CON CAMPOS DE CAMBIOS
            if ($variant && $variant->id) {
                UploadProcessLog::create([
                    'upload_id' => $this->upload->id,
                    'product_variant_id' => $variant->id,
                    'action' => $action,
                    'status' => 'pending',
                    'price_changed' => $priceChanged,      // 🔥 NUEVO
                    'barcode_changed' => $barcodeChanged,  // 🔥 NUEVO
                    'row_number' => $rowData['row_number'] ?? null
                ]);

                Log::info("✅ Log de procesamiento creado", [
                    'variant_id' => $variant->id,
                    'action' => $action,
                    'price_changed' => $priceChanged,
                    'barcode_changed' => $barcodeChanged
                ]);
            } else {
                Log::error("❌ No se pudo crear log - variant_id es null");
                throw new \Exception("Error: ProductVariant no se creó correctamente");
            }

            DB::commit();

            Log::info("✅ Producto y variante procesados exitosamente", [
                'codigo_interno' => $rowData['codigo'],
                'variant_id' => $variant->id,
                'action' => $action,
                'price_changed' => $priceChanged,
                'barcode_changed' => $barcodeChanged
            ]);

            return $variant;

        } catch (\Exception $e) {
            DB::rollback();

            Log::error("❌ Error procesando producto/variante", [
                'codigo' => $rowData['codigo'] ?? 'N/A',
                'descripcion' => isset($rowData['descripcion']) ? substr($rowData['descripcion'], 0, 50) : 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // 🔥 CREAR LOG DE ERROR SOLO CON CAMPOS OBLIGATORIOS
            try {
                UploadProcessLog::create([
                    'upload_id' => $this->upload->id,
                    'product_variant_id' => null,  // 🔥 Permitir null para errores
                    'action' => 'skipped',
                    'status' => 'failed',
                    'price_changed' => false,  // 🔥 NUEVO: Default false para errores
                    'barcode_changed' => false, // 🔥 NUEVO: Default false para errores
                    'error_message' => $e->getMessage(),
                    'row_number' => $rowData['row_number'] ?? null
                ]);
            } catch (\Exception $logError) {
                Log::error("❌ Error creando log de error", [
                    'log_error' => $logError->getMessage(),
                    'original_error' => $e->getMessage()
                ]);
            }

            // No devolver la variante si hubo error
            return null;
        }
    }

    /**
     * 🔥 ENVIAR BATCH DE VARIANTES A eRETAIL
     */
    private function sendVariantsBatchToERetail($variantsBatch)
    {
        Log::info("=== ENVIANDO BATCH DE VARIANTES A ERETAIL ===");
        Log::info("Cantidad de variantes en batch: " . count($variantsBatch));

        try {
            // 🔥 CORRECCIÓN: $variantsBatch es un array, no Collection
            // Obtener IDs de las variantes del batch
            $variantIds = [];
            foreach ($variantsBatch as $variant) {
                if ($variant && isset($variant->id)) {
                    $variantIds[] = $variant->id;
                }
            }

            Log::info("IDs de variantes extraídos: " . implode(', ', $variantIds));

            // 🔥 DEDUPLICAR IDs para evitar productos duplicados
            $variantIds = array_unique($variantIds);
            $variantIds = array_values($variantIds); // Reindexar array

            Log::info("IDs únicos después de deduplicar: " . implode(', ', $variantIds));

            $pendingLogs = UploadProcessLog::where('upload_id', $this->upload->id)
                ->whereIn('product_variant_id', $variantIds)
                ->where('status', 'pending')
                ->with('productVariant')
                ->get();

            if ($pendingLogs->isEmpty()) {
                Log::warning("No se encontraron logs pendientes para las variantes");
                return false;
            }

            Log::info("Logs pendientes obtenidos: " . $pendingLogs->count());

            // 🔥 CONSTRUIR ARRAY PARA eRETAIL CON IDs ESTABLES - DEDUPLICADO POR VARIANTE
            $eRetailProducts = [];
            $processedVariantIds = []; // 🔥 NUEVO: Tracking de variantes ya procesadas
            $logsByVariant = []; // 🔥 NUEVO: Agrupar logs por variante para después marcarlos

            foreach ($pendingLogs as $log) {
                $variant = $log->productVariant;

                if (!$variant) {
                    Log::error("Variante no encontrada para log ID: " . $log->id);
                    continue;
                }

                // 🔥 NUEVO: Verificar si ya procesamos esta variante específica
                if (in_array($variant->id, $processedVariantIds)) {
                    Log::warning("Variante ya procesada en este batch, omitiendo duplicado", [
                        'variant_id' => $variant->id,
                        'codigo_interno' => $variant->codigo_interno,
                        'log_id' => $log->id
                    ]);

                    // 🔥 IMPORTANTE: Agregar el log al grupo para marcarlo después
                    if (!isset($logsByVariant[$variant->id])) {
                        $logsByVariant[$variant->id] = [];
                    }
                    $logsByVariant[$variant->id][] = $log;
                    continue;
                }

                // 🔥 MARCAR variante como procesada
                $processedVariantIds[] = $variant->id;

                // 🔥 Inicializar array de logs para esta variante
                if (!isset($logsByVariant[$variant->id])) {
                    $logsByVariant[$variant->id] = [];
                }
                $logsByVariant[$variant->id][] = $log;

                // 🔥 CRÍTICO: Usar ProductVariant.id como goodsCode (SOLO UNA VEZ POR VARIANTE)
                $eRetailProducts[] = $this->eRetailService->buildProductData([
                    'id' => $variant->id,                    // ← ID ESTABLE de ProductVariant
                    'codigo' => $variant->codigo_interno,
                    'cod_barras' => $variant->cod_barras,
                    'descripcion' => $variant->descripcion,
                    'precio_original' => $variant->product->precio_final,
                    'precio_promocional' => $variant->product->precio_calculado
                ]);
            }

            if (empty($eRetailProducts)) {
                Log::error("No se pudieron construir productos para eRetail");
                return false;
            }

            // 🔥 NUEVO: Log detallado de deduplicación
            Log::info("Deduplicación completada", [
                'total_logs' => $pendingLogs->count(),
                'variantes_unicas' => count($processedVariantIds),
                'productos_enviados' => count($eRetailProducts),
                'logs_agrupados_por_variante' => array_map('count', $logsByVariant)
            ]);

            // Log para verificar estructura
            Log::info('Enviando a eRetail con IDs estables de ProductVariant', [
                'productos_count' => count($eRetailProducts),
                'primera_estructura' => [
                    'goodsCode_variant_id' => $eRetailProducts[0]['items'][1] ?? 'N/A',
                    'codigo_interno' => $eRetailProducts[0]['items'][4] ?? 'N/A',
                    'cod_barras' => $eRetailProducts[0]['items'][5] ?? 'N/A'
                ],
                'variant_ids_enviados' => array_column(array_column($eRetailProducts, 'items'), 1)
            ]);

            // 🔥 VERIFICAR NO HAY DUPLICADOS EN EL ARRAY FINAL
            $sentVariantIds = array_column(array_column($eRetailProducts, 'items'), 1);
            $uniqueSentIds = array_unique($sentVariantIds);

            if (count($sentVariantIds) !== count($uniqueSentIds)) {
                Log::error("¡DUPLICADOS DETECTADOS EN ARRAY FINAL!", [
                    'total_productos' => count($sentVariantIds),
                    'productos_unicos' => count($uniqueSentIds),
                    'duplicados' => array_diff_assoc($sentVariantIds, $uniqueSentIds)
                ]);
                throw new \Exception("Duplicados detectados en array final antes de enviar a eRetail");
            }

            // 🔥 ENVIAR A eRETAIL
            $result = $this->eRetailService->saveProducts($eRetailProducts);

            // Procesar resultado
            $isSuccess = isset($result['success']) && $result['success'] === true;

            Log::info('Respuesta de eRetail', [
                'success' => $isSuccess,
                'message' => $result['message'] ?? 'Sin mensaje'
            ]);

            if ($isSuccess) {
                // 🔥 MARCAR TODOS LOS LOGS COMO EXITOSOS (incluyendo duplicados)
                foreach ($logsByVariant as $variantId => $logs) {
                    foreach ($logs as $log) {
                        $log->update(['status' => 'success']);
                    }
                }

                // Incrementar contadores por acción
                $createdCount = $pendingLogs->where('action', 'created')->count();
                $updatedCount = $pendingLogs->where('action', 'updated')->count();

                if ($createdCount > 0) {
                    $this->upload->increment('created_variants', $createdCount);
                }
                if ($updatedCount > 0) {
                    $this->upload->increment('updated_variants', $updatedCount);
                }

                Log::info("✅ Batch enviado exitosamente", [
                    'total_logs' => $pendingLogs->count(),
                    'variantes_unicas_enviadas' => count($processedVariantIds),
                    'created' => $createdCount,
                    'updated' => $updatedCount
                ]);

            } else {
                // 🔥 MARCAR TODOS LOS LOGS COMO FALLIDOS
                foreach ($logsByVariant as $variantId => $logs) {
                    foreach ($logs as $log) {
                        $log->update([
                            'status' => 'failed',
                            'error_message' => $result['message'] ?? 'Error desconocido en eRetail'
                        ]);
                    }
                }

                $this->upload->increment('failed_variants', $pendingLogs->count());
                Log::error("❌ Error enviando batch: " . ($result['message'] ?? 'Error desconocido'));
            }

            return $isSuccess;

        } catch (\Exception $e) {
            Log::error("❌ Excepción enviando batch a eRetail", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Marcar logs como fallidos
            if (isset($variantIds)) {
                UploadProcessLog::where('upload_id', $this->upload->id)
                    ->whereIn('product_variant_id', $variantIds)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'error_message' => 'Excepción: ' . $e->getMessage()
                    ]);

                $this->upload->increment('failed_variants', count($variantIds));
            }

            return false;
        }
    }

    /**
     * 🔥 EXTRAER DATOS DE FILA
     */
    private function extractRowData($row, $indices, $rowNumber)
    {
        $codBarrasRaw = $indices['cod_barras'] !== false ? $this->cleanValue($row[$indices['cod_barras']] ?? '') : '';
        $codigoRaw = $indices['codigo'] !== false ? $this->cleanValue($row[$indices['codigo']] ?? '') : '';

        $identificadorPrincipal = $this->determineIdentifier($codBarrasRaw, $codigoRaw, $rowNumber);

        $precioFinal = $this->parsePrice($row[$indices['precio_final']] ?? 0);

        // 🔥 CALCULAR PRECIO CON DESCUENTO
        $precioDescuento = $precioFinal * (1 - ($this->discountPercentage / 100));

        return [
            'cod_barras_original' => $codBarrasRaw,
            'codigo' => $codigoRaw,
            'cod_barras' => $identificadorPrincipal,
            'identificador_principal' => $identificadorPrincipal,
            'descripcion' => $this->cleanValue($row[$indices['descripcion']] ?? ''),
            'precio_final' => $precioFinal,
            'precio_descuento' => $precioDescuento, // 🔥 AGREGADO
            'fec_ul_mo' => $this->parseDate($row[$indices['fec_ul_mo']] ?? ''),
            'row_number' => $rowNumber
        ];
    }

    /**
     * 🔥 ENCONTRAR ÍNDICES DE COLUMNAS - NOMBRES ESPECÍFICOS
     */
    private function findColumnIndices($data)
    {
        Log::info("🔍 BUSCANDO COLUMNAS CON NOMBRES ESPECÍFICOS");

        // 🔥 ESPECIFICA AQUÍ LOS NOMBRES EXACTOS DE TUS COLUMNAS
        $expectedColumns = [
            'cod_barras' => 'Cód.Barras',      // 🔧 CAMBIAR POR TU NOMBRE EXACTO
            'codigo' => 'Código',              // 🔧 CAMBIAR POR TU NOMBRE EXACTO  
            'descripcion' => 'Descripción',    // 🔧 CAMBIAR POR TU NOMBRE EXACTO
            'precio_final' => 'Fina ($)',     // 🔧 CAMBIAR POR TU NOMBRE EXACTO
            'fec_ul_mo' => 'UltModif'          // 🔧 CAMBIAR POR TU NOMBRE EXACTO (o null si no existe)
        ];

        Log::info("📋 Columnas que buscaremos:", $expectedColumns);

        for ($i = 0; $i < min(5, count($data)); $i++) {
            $row = $data[$i];

            // 🔥 DEBUGGING: Mostrar contenido de la fila
            Log::info("📋 Fila {$i} completa:", [
                'contenido' => $row,
                'cantidad_columnas' => count($row)
            ]);

            // Crear mapeo de índices
            $indices = [
                'header_row_index' => $i,
                'cod_barras' => false,
                'codigo' => false,
                'descripcion' => false,
                'precio_final' => false,
                'fec_ul_mo' => false
            ];

            // Buscar cada columna por nombre exacto
            foreach ($row as $index => $cellValue) {
                $cellValue = trim($cellValue ?? '');

                Log::info("🔍 Celda [{$index}]: '{$cellValue}'");

                // Comparar con nombres exactos
                foreach ($expectedColumns as $key => $expectedName) {
                    if ($expectedName && $cellValue === $expectedName) {
                        $indices[$key] = $index;
                        Log::info("✅ Encontrada columna '{$key}' en índice {$index}: '{$cellValue}'");
                    }
                }
            }

            Log::info("📍 Índices encontrados:", $indices);

            // ✅ VERIFICAR QUE TENEMOS LO MÍNIMO NECESARIO
            $hasDescripcion = $indices['descripcion'] !== false;
            $hasPrecio = $indices['precio_final'] !== false;
            $hasIdentificador = $indices['cod_barras'] !== false || $indices['codigo'] !== false;

            Log::info("🔍 Validación:", [
                'tiene_descripcion' => $hasDescripcion,
                'tiene_precio' => $hasPrecio,
                'tiene_identificador' => $hasIdentificador
            ]);

            if ($hasDescripcion && $hasPrecio && $hasIdentificador) {
                Log::info("✅ COLUMNAS VÁLIDAS ENCONTRADAS EN FILA {$i}");
                return $indices;
            }
        }

        // 🔥 ERROR DETALLADO
        Log::error("❌ NO SE ENCONTRARON TODAS LAS COLUMNAS NECESARIAS");
        Log::error("📋 Columnas buscadas:", $expectedColumns);
        Log::error("💡 Asegúrate de que los nombres en \$expectedColumns coincidan EXACTAMENTE con tu Excel");

        throw new \Exception('No se encontraron las columnas necesarias. Verifica que los nombres en el código coincidan exactamente con el Excel.');
    }

    private function isEmptyRow($row)
    {
        foreach ($row as $cell) {
            if (!empty(trim($cell ?? ''))) {
                return false;
            }
        }
        return true;
    }

    private function cleanValue($value)
    {
        return trim($value ?? '');
    }

    private function parsePrice($value)
    {
        $cleanValue = preg_replace('/[^\d.,]/', '', $value ?? '');
        $cleanValue = str_replace(',', '.', $cleanValue);
        return (float) $cleanValue;
    }

    private function parseDate($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function determineIdentifier($codBarras, $codigo, $rowNumber)
    {
        // Si hay código de barras (no vacío), usarlo
        if (!empty($codBarras) && trim($codBarras) !== '') {
            return $codBarras;
        }

        // Si no, usar código interno
        if (!empty($codigo)) {
            Log::warning("Usando código interno como identificador en fila {$rowNumber}", [
                'codigo' => $codigo,
                'razon' => 'codigo_barras_vacio'
            ]);
            return $codigo;
        }

        throw new \Exception("Fila {$rowNumber}: No tiene código de barras ni código válido");
    }
}
