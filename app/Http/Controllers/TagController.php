<?php

namespace App\Http\Controllers;

use App\Services\TagService;
use App\Services\ERetailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            Log::info("Solicitando refresh de " . count($tagIds) . " etiquetas");

            $this->eRetailService->login();
            $response = $this->eRetailService->refreshSpecificTagIds($tagIds);

            if ($response) {
                return response()->json([
                    'success' => true,
                    'message' => 'Solicitud de actualización enviada para ' . count($tagIds) . ' etiquetas',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al solicitar actualización',
            ], 500);

        } catch (\Exception $e) {
            Log::error("Error refrescando múltiples etiquetas: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
