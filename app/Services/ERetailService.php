<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\ERetailException;

class ERetailService
{
    private $client;
    private $token;
    private $config;
    private string $tokenCacheKey;

    public function __construct()
    {
        $tenantManager = app(\App\Services\TenantManager::class);
        $tenant = $tenantManager->getTenant();

        if ($tenant) {
            $this->config = $tenant->getERetailConfig();
            $this->tokenCacheKey = "eretail_token_org_{$tenant->id}";
        } else {
            $this->config = config('eretail');
            $this->tokenCacheKey = 'eretail_token';
        }

        $this->client = new Client([
            'base_uri' => $this->config['base_url'],
            'timeout'  => $this->config['timeout'],
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]
        ]);

        $this->token = Cache::get($this->tokenCacheKey);
    }


    /**
     * Autenticación con eRetail
     */
    public function login()
    {
        try {
            $this->token = Cache::get($this->tokenCacheKey);

            if ($this->token) {
                return true;
            }

            $response = $this->client->post('/api/login', [
                'json' => [
                    'userName' => $this->config['username'],
                    'password' => $this->config['password']
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($data['code'] === 0) {
                $this->token = $data['body'];
                Cache::put($this->tokenCacheKey, $this->token, now()->addHours(5));

                Log::info('eRetail login successful');
                return true;
            }

            throw new ERetailException($data['message'] ?? 'Login failed');

        } catch (GuzzleException $e) {
            Log::error('eRetail login error: ' . $e->getMessage());
            throw new ERetailException('Error de conexión con eRetail: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 CREAR O ACTUALIZAR PRODUCTOS - ACTUALIZADO PARA NUEVA ARQUITECTURA
     */
    public function saveProducts($products)
    {
        try {
            // Log del JSON que se enviará
            Log::info('JSON enviado a eRetail - Nueva Arquitectura', [
                'url' => '/api/goods/saveList?NR=false',
                'productos_count' => count($products),
                'arquitectura' => 'ProductVariant',
                'json_preview' => json_encode(array_slice($products, 0, 2), JSON_PRETTY_PRINT)
            ]);

            $response = $this->authenticatedRequest('POST', '/api/goods/saveList?NR=false', [
                'json' => $products
            ]);

            // Log de la respuesta completa
            Log::info('Respuesta completa de eRetail', [
                'response' => $response,
                'arquitectura' => 'ProductVariant'
            ]);

            if ($response['code'] === 0) {
                return [
                    'success' => true,
                    'message' => $response['message'] ?? 'Productos guardados correctamente',
                    'body' => $response['body'] ?? null
                ];
            }

            // Log del error
            Log::error('Error en respuesta de eRetail', [
                'code' => $response['code'],
                'message' => $response['message'] ?? 'Sin mensaje de error',
                'arquitectura' => 'ProductVariant'
            ]);

            throw new ERetailException($response['message'] ?? 'Error al guardar productos');

        } catch (GuzzleException $e) {
            Log::error('Error HTTP enviando productos a eRetail', [
                'status_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'arquitectura' => 'ProductVariant',
                'response_body' => ($e instanceof \GuzzleHttp\Exception\RequestException && $e->getResponse())
                    ? $e->getResponse()->getBody()->getContents()
                    : 'Sin respuesta'
            ]);
            throw new ERetailException('Error al enviar productos: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 CONSTRUIR ARRAY DE PRODUCTO PARA eRETAIL - ACTUALIZADO PARA ProductVariant
     * 
     * CRÍTICO: Ahora recibe ProductVariant.id como 'id' que se usa como goodsCode
     */
    public function buildProductData($productInfo, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        // Validar que tenemos el ID de ProductVariant
        if (!isset($productInfo['id']) || empty($productInfo['id'])) {
            throw new ERetailException('ProductVariant ID es requerido para buildProductData');
        }

        // Construir descripción con código de barras
        $descripcion = $productInfo['descripcion'] ?? '';
        $codigoBarras = $productInfo['cod_barras'] ?? '';

        if (!empty($codigoBarras)) {
            $descripcion = $descripcion . ' - CB: ' . $codigoBarras;
        }

        // 🔥 CRÍTICO: productInfo['id'] ahora es ProductVariant.id (ESTABLE)
        $goodsCode = $productInfo['id']; // ← ProductVariant.id

        Log::debug('Construyendo producto para eRetail', [
            'variant_id_como_goodsCode' => $goodsCode,
            'codigo_interno' => $productInfo['codigo'] ?? 'N/A',
            'cod_barras' => $codigoBarras,
            'descripcion_truncada' => substr($descripcion, 0, 50),
            'precio_original' => $productInfo['precio_original'] ?? 0,
            'precio_promocional' => $productInfo['precio_promocional'] ?? 0
        ]);

        return [
            'shopCode' => $shopCode,
            'template' => 'REG',
            'items' => [
                $shopCode,                                          // [0] Código tienda cliente
                (string) $goodsCode,                               // [1] 🔥 ProductVariant.id como goodsCode
                $descripcion,                                       // [2] Nombre producto + Codigo de barras
                '',                                                // [3] GoodsShortForm
                $productInfo['codigo'] ?? '',                      // [4] UPC1 - Código interno
                $codigoBarras,                                     // [5] UPC2 - Código de barras
                '',                                                // [6] UPC3
                (string) ($productInfo['precio_original'] ?? 0),   // [7] Precio original (SIN descuento)
                (string) ($productInfo['precio_promocional'] ?? 0), // [8] Precio promocional (CON descuento)
                '',                                                // [9] Precio miembro
                'Argentina',                                       // [10] Origen
                'Unidad',                                         // [11] Especificación
                'UN',                                             // [12] Unidad
                '',                                               // [13] Grado
                '',                                               // [14] Fecha inicio promo
                '',                                               // [15] Fecha fin promo
                '',                                               // [16] Código QR
                'Sistema',                                        // [17] Responsable precio
                '0',                                              // [18] Inventario
                '',                                               // [19-26] Campos adicionales vacíos
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ]
        ];
    }

    /**
     * 🚨 CORRECCIÓN CRÍTICA: Reemplazar método authenticatedRequest()
     * 
     * El método getToken() NO EXISTE. Debemos usar $this->token directamente.
     */
    private function authenticatedRequest($method, $endpoint, $options = [])
    {
        // 🔥 CORRECCIÓN 1: Verificar token directamente, no llamar getToken()
        if (!$this->token) {
            Log::info('Token no encontrado, autenticando...');
            $this->login();
        }

        // 🔥 CORRECCIÓN 2: Usar $this->token directamente
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-Type' => 'application/json'
        ]);

        try {
            Log::info('Haciendo petición a eRetail', [
                'method' => $method,
                'endpoint' => $endpoint,
                'has_token' => !empty($this->token)
            ]);

            $response = $this->client->request($method, $endpoint, $options);
            $responseBody = $response->getBody()->getContents();

            Log::info('Respuesta HTTP de eRetail', [
                'status_code' => $response->getStatusCode(),
                'body_preview' => substr($responseBody, 0, 500)
            ]);

            return json_decode($responseBody, true);

        } catch (GuzzleException $e) {
            // Si el error es 401, intentar login de nuevo
            if ($e->getCode() === 401) {
                Log::warning('Token expirado (401), reautenticando...');
                Cache::forget($this->tokenCacheKey);
                $this->token = null;
                $this->login();

                // Reintentar la petición con nuevo token
                $options['headers']['Authorization'] = 'Bearer ' . $this->token;
                $response = $this->client->request($method, $endpoint, $options);
                return json_decode($response->getBody()->getContents(), true);
            }

            Log::error('Error en petición HTTP a eRetail', [
                'method' => $method,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            throw $e;
        }
    }
    /**
     * 🔥 BUSCAR PRODUCTO POR CÓDIGO DE BARRAS - ACTUALIZADO
     */
    public function findProduct($codBarras, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        try {
            Log::info('Buscando producto en eRetail', [
                'cod_barras' => $codBarras,
                'shop_code' => $shopCode
            ]);

            $response = $this->authenticatedRequest('POST', '/api/Goods/getList', [
                'json' => [
                    'pageIndex' => 1,
                    'pageSize' => 1,
                    'shopCode' => $shopCode,
                    'goodsCode' => $codBarras
                ]
            ]);

            if ($response['code'] === 0 && isset($response['body']['itemList']) && count($response['body']['itemList']) > 0) {
                Log::info('Producto encontrado en eRetail', [
                    'cod_barras' => $codBarras,
                    'goodsCode_encontrado' => $response['body']['itemList'][0]['goodsCode'] ?? 'N/A'
                ]);
                return $response['body']['itemList'][0];
            }

            Log::info('Producto no encontrado en eRetail', [
                'cod_barras' => $codBarras
            ]);
            return null;

        } catch (GuzzleException $e) {
            Log::error("Error buscando producto {$codBarras}: " . $e->getMessage());
            throw new ERetailException("Error al buscar producto: " . $e->getMessage());
        }
    }

    /**
     * 🔥 MÉTODO CORREGIDO: findProductByVariantId()
     * 
     * Corrección: Manejo flexible de estructura de respuesta
     */
    public function findProductByVariantId($variantId, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        try {
            Log::info('Buscando producto por ProductVariant.id en eRetail', [
                'variant_id' => $variantId,
                'shop_code' => $shopCode
            ]);

            $response = $this->authenticatedRequest('POST', '/api/Goods/getList', [
                'json' => [
                    'pageIndex' => 1,
                    'pageSize' => 1,
                    'shopCode' => $shopCode,
                    'goodsCode' => (string) $variantId
                ]
            ]);

            // 🔥 CORRECCIÓN: Manejo flexible de estructura de respuesta
            $code = null;
            $itemList = null;

            if (isset($response['code'])) {
                // Estructura directa
                $code = $response['code'];
                $itemList = $response['body']['itemList'] ?? [];
            } elseif (isset($response['value']['code'])) {
                // Estructura anidada
                $code = $response['value']['code'];
                $itemList = $response['value']['body']['itemList'] ?? [];
            }

            if ($code === 0 && !empty($itemList)) {
                Log::info('Producto encontrado por ProductVariant.id', [
                    'variant_id' => $variantId,
                    'goodsCode_encontrado' => $itemList[0]['goodsCode'] ?? 'N/A',
                    'tagID_encontrado' => $itemList[0]['tagID'] ?? 'N/A'
                ]);

                return $itemList[0];
            }

            Log::info('Producto no encontrado por ProductVariant.id', [
                'variant_id' => $variantId,
                'response_code' => $code ?? 'unknown'
            ]);

            return null;

        } catch (GuzzleException $e) {
            Log::error("Error buscando producto por variant ID {$variantId}: " . $e->getMessage());
            throw new ERetailException("Error al buscar producto por variant ID: " . $e->getMessage());
        }
    }

    /**
     * Test de conexión
     */
    public function testConnection()
    {
        try {
            $response = $this->client->get('/api/hello');
            return $response->getBody()->getContents() === 'OK';
        } catch (GuzzleException $e) {
            return false;
        }
    }

    /**
     * 🔥 MÉTODO CORREGIDO: refreshSpecificTags()
     * 
     * Correcciones aplicadas:
     * 1. Usar endpoint /api/esl/tag/Refresh en lugar de pushList
     * 2. Corregir acceso a respuesta: $response['value']['code'] vs $response['code']
     */
    public function refreshSpecificTags($variantIds, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        try {
            Log::info('Refrescando etiquetas específicas', [
                'variant_ids_count' => count($variantIds),
                'shop_code' => $shopCode,
                'primeros_3_ids' => array_slice($variantIds, 0, 3)
            ]);

            // SOLUCIÓN: Usar endpoint /api/esl/tag/Refresh para toda la tienda
            $response = $this->authenticatedRequest('POST', '/api/esl/tag/Refresh', [
                'json' => [
                    'shopCode' => "0010",
                    'refreshType' => 3, // 3 = specified store (toda la tienda)
                    'refreshName' => '',
                    'tags' => []
                ]
            ]);

            // 🔥 CORRECCIÓN 3: Verificar estructura de respuesta correcta
            // eRetail puede devolver tanto {code: 0} como {value: {code: 0}}
            $code = null;
            $message = null;

            if (isset($response['code'])) {
                // Estructura directa: {code: 0, message: "success"}
                $code = $response['code'];
                $message = $response['message'] ?? 'Sin mensaje';
            } elseif (isset($response['value']['code'])) {
                // Estructura anidada: {value: {code: 0, message: "success"}}
                $code = $response['value']['code'];
                $message = $response['value']['message'] ?? 'Sin mensaje';
            }

            if ($code === 0) {
                Log::info('Etiquetas refrescadas exitosamente', [
                    'variant_ids_count' => count($variantIds),
                    'message' => $message
                ]);
                return true;
            }

            Log::error('Error refrescando etiquetas', [
                'code' => $code ?? 'unknown',
                'message' => $message ?? 'Respuesta inválida',
                'full_response' => $response
            ]);

            return false;

        } catch (GuzzleException $e) {
            Log::error('Error HTTP refrescando etiquetas: ' . $e->getMessage());
            throw new ERetailException('Error al refrescar etiquetas: ' . $e->getMessage());
        }
    }


    /**
     * 🔥 ALTERNATIVA: REFRESCAR ETIQUETAS ESPECÍFICAS POR TAG ID
     * 
     * Esta versión obtiene primero los tag IDs y luego los refresca específicamente
     */
    public function refreshSpecificTagsByTagId($variantIds, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        try {
            Log::info('Obteniendo Tag IDs para variantes', [
                'variant_ids_count' => count($variantIds),
                'shop_code' => $shopCode
            ]);

            // Paso 1: Obtener los tag IDs asociados a los productos
            $tagIds = [];
            foreach ($variantIds as $variantId) {
                $product = $this->findProductByVariantId($variantId, $shopCode);
                if ($product && isset($product['tagID']) && !empty($product['tagID'])) {
                    $tagIds[] = $product['tagID'];
                }
            }

            if (empty($tagIds)) {
                Log::warning('No se encontraron tag IDs válidos para las variantes');
                return false;
            }

            Log::info('Tag IDs encontrados', [
                'tag_ids_count' => count($tagIds),
                'primeros_3_tags' => array_slice($tagIds, 0, 3)
            ]);

            // Paso 2: Refrescar etiquetas específicas por tag ID
            $response = $this->authenticatedRequest('POST', '/api/esl/tag/Refresh', [
                'json' => [
                    'shopCode' => $shopCode,
                    'refreshType' => 4, // 4 = tag ID list
                    'refreshName' => '',
                    'tags' => $tagIds
                ]
            ]);

            // Verificar respuesta con estructura correcta
            if (isset($response['value']) && $response['value']['code'] === 0) {
                Log::info('Etiquetas específicas refrescadas exitosamente', [
                    'tag_ids_count' => count($tagIds),
                    'message' => $response['value']['message'] ?? 'Sin mensaje'
                ]);
                return true;
            }

            $errorCode = $response['value']['code'] ?? 'unknown';
            $errorMessage = $response['value']['message'] ?? 'Sin mensaje';

            Log::error('Error refrescando etiquetas específicas', [
                'code' => $errorCode,
                'message' => $errorMessage
            ]);

            return false;

        } catch (GuzzleException $e) {
            Log::error('Error HTTP refrescando etiquetas específicas: ' . $e->getMessage());
            throw new ERetailException('Error al refrescar etiquetas específicas: ' . $e->getMessage());
        }
    }

    /**
     * 🔥 VALIDAR QUE PRODUCTO EXISTE EN eRETAIL POR ProductVariant.id
     */
    public function validateProductExists($variantId, $shopCode = null)
    {
        try {
            $product = $this->findProductByVariantId($variantId, $shopCode);
            return $product !== null;
        } catch (\Exception $e) {
            Log::error("Error validando existencia del producto variant ID {$variantId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Refrescar etiquetas en bloques (batches)
     * Divide los tagIds en chunks y llama a refreshSpecificTagIds por cada uno
     */
    public function refreshTagsInBatches(array $tagIds, $shopCode = null, int $batchSize = 50): array
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];
        $chunks = array_chunk($tagIds, $batchSize);
        $totalSent = 0;
        $totalSuccess = 0;
        $totalFailed = 0;

        Log::info("Iniciando refresh de etiquetas en batches", [
            'total_tags' => count($tagIds),
            'batch_size' => $batchSize,
            'total_batches' => count($chunks)
        ]);

        foreach ($chunks as $index => $chunk) {
            $totalSent += count($chunk);

            Log::info("Enviando batch " . ($index + 1) . "/" . count($chunks), [
                'tags_en_batch' => count($chunk)
            ]);

            try {
                $result = $this->refreshSpecificTagIds($chunk, $shopCode);

                if ($result) {
                    $totalSuccess += count($chunk);
                } else {
                    $totalFailed += count($chunk);
                }
            } catch (\Exception $e) {
                $totalFailed += count($chunk);
                Log::error("Error en batch " . ($index + 1), [
                    'error' => $e->getMessage()
                ]);
            }
        }

        $summary = [
            'total_enviados' => $totalSent,
            'exitosos' => $totalSuccess,
            'fallidos' => $totalFailed,
            'batches' => count($chunks)
        ];

        Log::info("Refresh en batches completado", $summary);

        return $summary;
    }

    /**
     * Refrescar etiquetas específicas por Tag ID (ID físico de la etiqueta)
     * Usa refreshType=4 según documentación de eRetail
     */
    public function refreshSpecificTagIds(array $tagIds, $shopCode = null)
    {
        $shopCode = $shopCode ?? $this->config['default_shop_code'];

        try {
            Log::info('Refrescando etiquetas por Tag ID', [
                'tag_ids_count' => count($tagIds),
                'shop_code' => $shopCode,
                'primeros_3_tags' => array_slice($tagIds, 0, 3)
            ]);

            $response = $this->authenticatedRequest('POST', '/api/esl/tag/Refresh', [
                'json' => [
                    'shopCode' => $shopCode,
                    'refreshType' => 4, // 4 = Price Tag ID List
                    'refreshName' => '',
                    'tags' => $tagIds
                ]
            ]);

            // Verificar estructura de respuesta
            $code = null;
            $message = null;

            if (isset($response['code'])) {
                $code = $response['code'];
                $message = $response['message'] ?? 'Sin mensaje';
            } elseif (isset($response['value']['code'])) {
                $code = $response['value']['code'];
                $message = $response['value']['message'] ?? 'Sin mensaje';
            }

            if ($code === 0) {
                Log::info('Etiquetas refrescadas exitosamente por Tag ID', [
                    'tag_ids_count' => count($tagIds),
                    'message' => $message
                ]);
                return true;
            }

            Log::error('Error refrescando etiquetas por Tag ID', [
                'code' => $code ?? 'unknown',
                'message' => $message ?? 'Respuesta inválida',
                'full_response' => $response
            ]);

            return false;

        } catch (GuzzleException $e) {
            Log::error('Error HTTP refrescando etiquetas por Tag ID: ' . $e->getMessage());
            throw new ERetailException('Error al refrescar etiquetas: ' . $e->getMessage());
        }
    }
}