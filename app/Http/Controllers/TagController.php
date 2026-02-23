<?php

namespace App\Http\Controllers;

use App\Services\TagService;
use App\Services\ERetailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogger;

class TagController extends Controller
{
    private TagService $tagService;
    private ERetailService $eRetailService;

    public function __construct(TagService $tagService, ERetailService $eRetailService)
    {
        $this->tagService = $tagService;
        $this->eRetailService = $eRetailService;
    }

    /**
     * Mostrar lista de etiquetas
     */
    public function index()
    {
        $stats = $this->tagService->getTagStats();
        return view('tags.index', compact('stats'));
    }

    /**
     * Obtener datos para DataTable (AJAX)
     */
    public function getData(Request $request)
    {
        try {
            $tags = $this->tagService->getAllTags();

            // Filtrar si hay búsqueda
            $search = $request->get('search');
            if (!empty($search)) {
                $tags = array_filter($tags, function ($tag) use ($search) {
                    $searchLower = strtolower($search);
                    return str_contains(strtolower($tag['tag_id'] ?? ''), $searchLower)
                        || str_contains(strtolower($tag['goods_code'] ?? ''), $searchLower)
                        || str_contains(strtolower($tag['goods_name'] ?? ''), $searchLower);
                });
                $tags = array_values($tags);
            }

            // Filtrar por estado
            $statusFilter = $request->get('status');
            if ($statusFilter !== null && $statusFilter !== '') {
                $tags = array_filter($tags, function ($tag) use ($statusFilter) {
                    if ($statusFilter === 'vinculadas') {
                        return $tag['has_product'];
                    } elseif ($statusFilter === 'sin_vincular') {
                        return !$tag['has_product'];
                    } elseif ($statusFilter === 'bateria_baja') {
                        return $tag['power_percent'] < 30;
                    }
                    return true;
                });
                $tags = array_values($tags);
            }

            return response()->json([
                'success' => true,
                'data' => $tags,
                'total' => count($tags),
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo datos de etiquetas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener etiquetas: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    /**
     * Mostrar detalle de una etiqueta
     */
    public function show(string $tagId)
    {
        $tag = $this->tagService->getTagById($tagId);

        if (!$tag) {
            return redirect()
                ->route('tags.index')
                ->with('error', 'Etiqueta no encontrada');
        }

        return view('tags.show', compact('tag'));
    }

    /**
     * Refrescar una etiqueta específica
     */
    public function refresh(string $tagId)
    {
        try {
            Log::info("Solicitando refresh de etiqueta: {$tagId}");

            // Usar el endpoint de refresh con refreshType=4 (lista de tag IDs)
            $this->eRetailService->login();

            $response = $this->eRetailService->refreshSpecificTagIds([$tagId]);

            if ($response) {
                ActivityLogger::tags('tag_refresh', "Refresh de etiqueta: {$tagId}", [
                    'tag_ids' => [$tagId],
                    'count'   => 1,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud de actualización enviada correctamente',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar actualización',
            ], 500);

        } catch (\Exception $e) {
            Log::error("Error refrescando etiqueta {$tagId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refrescar múltiples etiquetas
     */
    public function refreshMultiple(Request $request)
    {
        try {
            $tagIds = $request->input('tag_ids', []);

            if (empty($tagIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se seleccionaron etiquetas',
                ], 400);
            }

            Log::info("Solicitando refresh de " . count($tagIds) . " etiquetas en bloques de 50");

            $this->eRetailService->login();
            $result = $this->eRetailService->refreshTagsInBatches($tagIds);

            if ($result['exitosos'] > 0) {
                ActivityLogger::tags('tags_refresh_multiple', "Refresh múltiple: {$result['exitosos']} etiquetas actualizadas", [
                    'tag_ids' => array_slice($tagIds, 0, 20),
                    'count'   => count($tagIds),
                    'exitosos'=> $result['exitosos'],
                    'fallidos'=> $result['fallidos'],
                    'batches' => $result['batches'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Actualización enviada: {$result['exitosos']} exitosas, {$result['fallidos']} fallidas ({$result['batches']} bloques)",
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Error al solicitar actualización ({$result['fallidos']} fallidas)",
            ], 500);

        } catch (\Exception $e) {
            Log::error("Error refrescando múltiples etiquetas: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar señal LED flash a una o varias etiquetas
     */
    public function flashLed(Request $request)
    {
        try {
            $tagIds = $request->input('tag_ids', []);
            $rgb    = strtoupper($request->input('rgb', 'G'));
            $times  = (int) $request->input('times', 5);

            if (empty($tagIds)) {
                return response()->json(['success' => false, 'message' => 'No se seleccionaron etiquetas'], 400);
            }
            if (!in_array($rgb, ['R', 'G', 'B'], true)) {
                return response()->json(['success' => false, 'message' => 'Color inválido. Use R, G o B.'], 400);
            }
            if ($times < 1 || $times > 60) {
                return response()->json(['success' => false, 'message' => 'La duración debe estar entre 1 y 60 segundos.'], 400);
            }

            $this->eRetailService->login();
            $result = $this->eRetailService->ledFlashInBatches($tagIds, $rgb, $times);

            $labels = ['R' => 'rojo', 'G' => 'verde', 'B' => 'azul'];
            $label  = $labels[$rgb] ?? $rgb;

            if ($result['fallidos'] === 0) {
                ActivityLogger::tags('led_flash', "LED flash {$label} enviado a {$result['exitosos']} etiqueta(s)", [
                    'tag_ids'  => array_slice($tagIds, 0, 20),
                    'count'    => count($tagIds),
                    'color'    => $rgb,
                    'duration' => $times,
                ]);

                return response()->json([
                    'success'  => true,
                    'message'  => "Señal LED {$label} enviada a {$result['exitosos']} etiqueta(s) por {$times} segundo(s)",
                    'exitosos' => $result['exitosos'],
                    'fallidos' => 0,
                    'batches'  => $result['batches'],
                ]);
            }

            if ($result['exitosos'] > 0) {
                return response()->json([
                    'success'  => true,
                    'message'  => "Señal LED {$label} parcial: {$result['exitosos']} exitosas, {$result['fallidos']} fallidas",
                    'exitosos' => $result['exitosos'],
                    'fallidos' => $result['fallidos'],
                    'batches'  => $result['batches'],
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Error al enviar la señal LED a todas las etiquetas'], 500);

        } catch (\Exception $e) {
            Log::error("Error en flashLed: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
