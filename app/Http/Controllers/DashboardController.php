<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    private $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Mostrar vista principal del dashboard
     */
    public function index()
    {
        $metrics = $this->dashboardService->getDashboardMetrics();
        $activities = $this->dashboardService->getRecentActivities();
        
        return view('dashboard.index', compact('metrics', 'activities'));
    }

    /**
     * API: Obtener métricas del dashboard
     */
    public function getMetrics(): JsonResponse
    {
        try {
            $metrics = $this->dashboardService->getDashboardMetrics();
            
            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo métricas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Obtener actividades recientes
     */
    public function getActivities(): JsonResponse
    {
        try {
            $activities = $this->dashboardService->getRecentActivities();
            
            return response()->json([
                'success' => true,
                'data' => $activities
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo actividades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Probar conexión a eRetail
     */
    public function testConnection(): JsonResponse
    {
        try {
            $result = $this->dashboardService->testERetailConnection();
            
            return response()->json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en test de conexión: ' . $e->getMessage()
            ], 500);
        }
    }
} 